<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Roles;
use App\Models\Bookings;
use App\Models\Payments;
use App\Models\Movies;
use App\Models\Genres;
use App\Models\Cinemas;
use App\Models\Halls;
use App\Models\Seats;
use App\Models\Showtimes;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


class AdminController extends Controller
{
    // DASHBOARD
    public function dashboard()
    {
        $totalUsers = User::count();
        $revenue = Payments::where('status', 'paid')->sum('amount');
        $activeSessions = User::where('last_seen', '>=', now()->subHour())->count();
        
        // Dynamic error rate based on failed payments vs total payments
        $totalPayments = Payments::count();
        $failedPayments = Payments::where('status', 'failed')->count();
        $errorRate = $totalPayments > 0 ? round(($failedPayments / $totalPayments) * 100, 1) : 0;

        // Revenue Trend (Default 7 Days)
        $chartData = $this->getRevenueChartData(7);
        $days = $chartData['labels'];
        $revenueTrend = $chartData['data'];

        // Top Movies (by tickets sold, all time)
        $topMovies = $this->topMoviesByTickets();

        // Booking Status Distribution
        $bookingStatus = [
            'confirmed' => Bookings::where('status', 'confirmed')->count(),
            'pending' => Bookings::where('status', 'pending')->count(),
            'canceled' => Bookings::where('status', 'canceled')->count(),
        ];

        // Recent Activity
        $recentBookings = Bookings::with(['user', 'showtime.movie'])->latest('date')->take(5)->get()->map(function($b) {
            return [
                'type' => 'booking',
                'title' => 'New booking created',
                'description' => ($b->user ? $b->user->name : 'Unknown User') . ' booked ' . ($b->showtime && $b->showtime->movie ? $b->showtime->movie->title : 'Unknown Movie'),
                'time' => $b->date ? $b->date->diffForHumans() : 'Unknown',
                'raw_time' => $b->date,
                'icon' => 'bi-ticket-fill',
                'color' => '#0d6efd',
                'bg' => 'rgba(13,110,253,0.1)'
            ];
        });

        $recentUsers = User::latest()->take(5)->get()->map(function($u) {
            return [
                'type' => 'user',
                'title' => 'New user registered',
                'description' => $u->name . ' signed up',
                'time' => $u->created_at->diffForHumans(),
                'raw_time' => $u->created_at,
                'icon' => 'bi-person-plus-fill',
                'color' => '#198754',
                'bg' => 'rgba(25,135,84,0.1)'
            ];
        });

        $recentActivity = $recentBookings->concat($recentUsers)->sortByDesc('raw_time')->take(5);

        return view('admin.dashboard', compact(
            'totalUsers', 
            'revenue', 
            'activeSessions', 
            'errorRate',
            'days',
            'revenueTrend',
            'topMovies',
            'bookingStatus',
            'recentActivity'
        ));
    }

    public function getRevenueChartData($daysCount = 7)
    {
        $daysCount = max(1, min((int) $daysCount, 365));

        $days = collect(range($daysCount - 1, 0))->map(function($i) {
            return now()->subDays($i)->format('Y-m-d');
        });

        $revenuePerDay = Payments::where('status', 'paid')
            ->where('date', '>=', now()->subDays($daysCount - 1)->startOfDay())
            ->groupByRaw('DATE(date)')
            ->selectRaw('DATE(date) as day, SUM(amount) as total')
            ->pluck('total', 'day');

        $revenueTrend = $days->map(fn ($date) => (float) ($revenuePerDay[$date] ?? 0));

        return [
            'labels' => $days,
            'data' => $revenueTrend
        ];
    }

    public function getChartData(Request $request)
    {
        $days = $request->get('days', 7);
        return response()->json($this->getRevenueChartData($days));
    }

    public function analytics(Request $request)
    {
        $rangeOptions = [7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days', 365 => 'Last Year'];
        $range = (int) $request->query('days', 7);
        if (!array_key_exists($range, $rangeOptions)) {
            $range = 7;
        }
        $from = now()->subDays($range - 1)->startOfDay();

        // Tickets = booked seats of confirmed bookings made in the range
        $ticketsSold = fn () => DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->where('bookings.status', 'confirmed')
            ->where('bookings.date', '>=', $from);

        // KPI Data
        $totalBookings = Bookings::where('status', 'confirmed')->where('date', '>=', $from)->count();
        $totalTickets = $ticketsSold()->count();
        $totalRevenue = (float) Payments::where('status', 'paid')->where('date', '>=', $from)->sum('amount');
        $avgTicketPrice = $totalTickets > 0 ? round($totalRevenue / $totalTickets, 2) : 0;

        // Occupancy: seats sold vs. seats available, for screenings in the range
        $capacityByCinema = DB::table('showtimes')
            ->join('halls', 'halls.id', '=', 'showtimes.hall_id')
            ->where('showtimes.start_time', '>=', $from)
            ->groupBy('halls.cinema_id')
            ->selectRaw('halls.cinema_id, SUM(halls.capacity) as capacity')
            ->pluck('capacity', 'cinema_id');

        $seatsSoldByCinema = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->join('halls', 'halls.id', '=', 'showtimes.hall_id')
            ->where('bookings.status', 'confirmed')
            ->where('showtimes.start_time', '>=', $from)
            ->groupBy('halls.cinema_id')
            ->selectRaw('halls.cinema_id, COUNT(*) as seats')
            ->pluck('seats', 'cinema_id');

        $totalCapacity = $capacityByCinema->sum();
        $occupancyRate = $totalCapacity > 0 ? round($seatsSoldByCinema->sum() / $totalCapacity * 100, 1) : 0;

        // Cinema Performance
        $bookingsByCinema = DB::table('bookings')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->join('halls', 'halls.id', '=', 'showtimes.hall_id')
            ->where('bookings.status', 'confirmed')
            ->where('bookings.date', '>=', $from)
            ->groupBy('halls.cinema_id')
            ->selectRaw('halls.cinema_id, COUNT(*) as bookings')
            ->pluck('bookings', 'cinema_id');

        $revenueByCinema = DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->join('halls', 'halls.id', '=', 'showtimes.hall_id')
            ->where('payments.status', 'paid')
            ->where('payments.date', '>=', $from)
            ->groupBy('halls.cinema_id')
            ->selectRaw('halls.cinema_id, SUM(payments.amount) as revenue')
            ->pluck('revenue', 'cinema_id');

        $cinemaPerformance = Cinemas::orderBy('name')->get()->map(function ($cinema) use ($bookingsByCinema, $revenueByCinema, $capacityByCinema, $seatsSoldByCinema) {
            $capacity = (int) ($capacityByCinema[$cinema->id] ?? 0);
            return [
                'name' => $cinema->name,
                'bookings' => (int) ($bookingsByCinema[$cinema->id] ?? 0),
                'revenue' => (float) ($revenueByCinema[$cinema->id] ?? 0),
                'occupancy' => $capacity > 0 ? round(($seatsSoldByCinema[$cinema->id] ?? 0) / $capacity * 100, 1) : 0,
            ];
        });

        // Top Movies (by tickets sold)
        $topMovies = $this->topMoviesByTickets($from);

        // Trend Data
        $dates = collect(range($range - 1, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));
        $days = $dates->map(fn ($date) => Carbon::parse($date)->format('M j'));

        $bookingsPerDay = Bookings::where('status', 'confirmed')->where('date', '>=', $from)
            ->groupByRaw('DATE(date)')->selectRaw('DATE(date) as day, COUNT(*) as total')->pluck('total', 'day');
        $revenuePerDay = Payments::where('status', 'paid')->where('date', '>=', $from)
            ->groupByRaw('DATE(date)')->selectRaw('DATE(date) as day, SUM(amount) as total')->pluck('total', 'day');
        $usersPerDay = User::where('created_at', '>=', $from)
            ->groupByRaw('DATE(created_at)')->selectRaw('DATE(created_at) as day, COUNT(*) as total')->pluck('total', 'day');

        $bookingsTrend = $dates->map(fn ($date) => (int) ($bookingsPerDay[$date] ?? 0));
        $revenueTrend = $dates->map(fn ($date) => (float) ($revenuePerDay[$date] ?? 0));
        $userGrowth = $dates->map(fn ($date) => (int) ($usersPerDay[$date] ?? 0));

        // Genre Popularity (tickets sold per genre)
        $ticketsByGenre = $ticketsSold()
            ->join('movies', 'movies.id', '=', 'showtimes.movie_id')
            ->groupBy('movies.genre_id')
            ->selectRaw('movies.genre_id, COUNT(*) as tickets')
            ->pluck('tickets', 'genre_id');

        $genrePopularity = Genres::orderBy('name')->get()->each(function ($genre) use ($ticketsByGenre) {
            $genre->bookings_count = (int) ($ticketsByGenre[$genre->id] ?? 0);
        })->sortByDesc('bookings_count')->values();

        // Payment Methods
        $paymentMethods = Payments::select('method', DB::raw('count(*) as count'))
            ->where('status', 'paid')
            ->where('date', '>=', $from)
            ->groupBy('method')
            ->get();

        // User Status
        $userStatus = User::groupBy('status')->selectRaw('status, COUNT(*) as total')->pluck('total', 'status');
        $userStatus = collect(['active', 'pending', 'banned'])->mapWithKeys(fn ($s) => [$s => (int) ($userStatus[$s] ?? 0)]);

        // Repeat Customers (all time): share of customers with more than one confirmed booking
        $bookingsPerCustomer = Bookings::where('status', 'confirmed')
            ->groupBy('user_id')->selectRaw('COUNT(*) as total')->pluck('total');
        $customerCount = $bookingsPerCustomer->count();
        $percentOfCustomers = fn ($n) => $customerCount > 0 ? round($n / $customerCount * 100) : 0;
        $repeatCustomers = [
            'customers' => $customerCount,
            'repeat_rate' => $percentOfCustomers($bookingsPerCustomer->filter(fn ($n) => $n > 1)->count()),
            'one' => $percentOfCustomers($bookingsPerCustomer->filter(fn ($n) => $n == 1)->count()),
            'two_to_four' => $percentOfCustomers($bookingsPerCustomer->filter(fn ($n) => $n >= 2 && $n <= 4)->count()),
            'five_plus' => $percentOfCustomers($bookingsPerCustomer->filter(fn ($n) => $n >= 5)->count()),
        ];

        return view('admin.analytics', compact(
            'range',
            'rangeOptions',
            'totalBookings',
            'totalTickets',
            'totalRevenue',
            'avgTicketPrice',
            'occupancyRate',
            'cinemaPerformance',
            'topMovies',
            'days',
            'bookingsTrend',
            'revenueTrend',
            'genrePopularity',
            'paymentMethods',
            'userGrowth',
            'userStatus',
            'repeatCustomers'
        ));
    }

    /**
     * Movies ranked by tickets sold (booked seats of confirmed bookings).
     * Each movie gets a `bookings_count` attribute holding its ticket count.
     */
    private function topMoviesByTickets(?Carbon $from = null, int $limit = 5)
    {
        $ticketsByMovie = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
            ->where('bookings.status', 'confirmed')
            ->when($from, fn ($q) => $q->where('bookings.date', '>=', $from))
            ->groupBy('showtimes.movie_id')
            ->selectRaw('showtimes.movie_id, COUNT(*) as tickets')
            ->orderByDesc('tickets')
            ->limit($limit)
            ->pluck('tickets', 'movie_id');

        return Movies::with('genre')->whereIn('id', $ticketsByMovie->keys())->get()
            ->each(fn ($movie) => $movie->bookings_count = (int) $ticketsByMovie[$movie->id])
            ->sortByDesc('bookings_count')
            ->values();
    }

    // USERS - LIST
    public function usersList(Request $request)
    {
        $query = User::with('role');

        // Search
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
        }

        // Status filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = $request->per_page ?? 10;
        $users = $query->paginate($perPage);

        return view('admin.users.index', compact('users'));
    }

    // USERS - CREATE VIEW
    public function createUser()
    {
        $roles = Roles::all();
        return view('admin.users.form', compact('roles'));
    }

    // USERS - STORE
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,pending,banned',
            'email_verified' => 'nullable',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->role_id = $validated['role_id'];
        $user->status = $validated['status'];

        if ($request->email_verified) {
            $user->email_verified_at = now();
        }

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return redirect()->route('admin.users.index')
                       ->with('success', 'User created successfully');
    }

    // USERS - EDIT VIEW
    public function editUser(User $user)
    {
        $roles = Roles::all();
        return view('admin.users.form', compact('user', 'roles'));
    }

    // USERS - UPDATE
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,pending,banned',
            'email_verified' => 'nullable',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role_id = $validated['role_id'];
        $user->status = $validated['status'];

        if ($validated['password'] ?? null) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->email_verified) {
            $user->email_verified_at = now();
        } else {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return redirect()->route('admin.users.index')
                       ->with('success', 'User updated successfully');
    }

    // USERS - DELETE
    public function deleteUser(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')
                       ->with('success', 'User deleted successfully');
    }

    

    // MOVIES MANAGEMENT
    public function moviesList(Request $request)
    {
        $query = Movies::with(['genre', 'showtime.hall.cinema'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('genre', function($g) use ($search) {
                      $g->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Pagination
        $perPage = $request->per_page ?? 10;
        $movies = $query->paginate($perPage);

        return view('admin.movies.index', compact('movies'));
    }

    // MOVIES - CREATE VIEW
    // STEP 1 — show movie details form (no showtimes yet)
    public function createMovie()
    {
        $genres  = Genres::all();
        return view('admin.movies.create', compact('genres'));
    }

    // STEP 2 — show scheduling form for an already-saved movie
    public function showMovieShowtimes(Movies $movie)
    {
        $movie->load('genre');
        $movie->setRelation('showtimes', $this->showtimesWithBookingCounts($movie));
        $cinemas = Cinemas::with(['halls' => fn ($q) => $q->orderBy('name')])->orderBy('name')->get();

        $allShowtimes = Showtimes::with('movie:id,title')
            ->where('movie_id', '!=', $movie->id)
            ->where('end_time', '>=', now())
            ->orderBy('start_time')
            ->get()
            ->map(fn($st) => [
                'hall_id'    => $st->hall_id,
                'movie'      => $st->movie->title ?? 'Unknown',
                'start_time' => $st->start_time->format('Y-m-d H:i'),
                'end_time'   => $st->end_time->format('Y-m-d H:i'),
                'start_ts'   => $st->start_time->timestamp,
                'end_ts'     => $st->end_time->timestamp,
            ]);

        return view('admin.movies.showtimes', compact('movie', 'cinemas', 'allShowtimes'));
    }
    private function hasConflict(
        int $hallId,
        string $startTime,
        int $durationMinutes,
        ?int $excludeId = null,
        ?int $excludeMovieId = null
    ): bool {
        $start = Carbon::parse($startTime);
        $end   = $start->copy()->addMinutes($durationMinutes);

        $query = Showtimes::where('hall_id', $hallId)
            ->where(function ($q) use ($start, $end) {
                // Standard interval overlap:
                // existing.start < new.end  AND  existing.end > new.start
                $q->where('start_time', '<', $end)
                ->where('end_time',   '>', $start);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($excludeMovieId) {
            $query->where('movie_id', '!=', $excludeMovieId);
        }

        return $query->exists();
    }

    /**
     * Load a movie's showtimes with how many live (non-canceled) bookings each has.
     * Deleting a showtime cascades to its bookings, payments and tickets, and
     * seats belong to a hall, so booked showtimes must not be removed or moved.
     */
    private function showtimesWithBookingCounts(Movies $movie)
    {
        return $movie->showtimes()
            ->with('hall')
            ->withCount(['bookings as active_bookings_count' => fn ($q) => $q->where('status', '!=', 'canceled')])
            ->orderBy('start_time')
            ->get();
    }
    // STEP 2 — save/update showtimes for an existing movie
    public function storeShowtimes(Request $request, Movies $movie)
    {
        $request->validate([
            'showtimes'                  => 'required|array|min:1',
            'showtimes.*.hall_id'        => 'required|exists:halls,id',
            'showtimes.*.start_time'     => 'required|date',
            'showtimes.*.price'          => 'required|numeric|min:0',
            'showtimes.*.id'             => 'nullable|exists:showtimes,id',
        ]);

        $existing = $this->showtimesWithBookingCounts($movie)->keyBy('id');

        // ── Check 0: protect showtimes that already have bookings ─────────────
        foreach ($request->showtimes as $index => $stData) {
            if (empty($stData['id'])) continue;

            $current = $existing->get((int) $stData['id']);
            if (!$current) {
                return back()->withInput()->withErrors([
                    "showtimes.{$index}.start_time" => 'This showtime does not belong to "' . $movie->title . '". Please reload the page.',
                ]);
            }

            // Booked seats belong to the hall, so a booked showtime can't change hall
            if ($current->active_bookings_count > 0 && (int) $stData['hall_id'] !== (int) $current->hall_id) {
                return back()->withInput()->withErrors([
                    "showtimes.{$index}.hall_id" => 'The ' . $current->start_time->format('M j, g:i A')
                        . ' showtime already has bookings, so it must stay in hall "' . $current->hall->name . '".',
                ]);
            }
        }

        // Removing a showtime deletes its bookings, payments and tickets
        $submittedIds = collect($request->showtimes)->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $removedWithBookings = $existing->filter(
            fn ($st) => !$submittedIds->contains($st->id) && $st->active_bookings_count > 0
        );

        if ($removedWithBookings->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'showtimes' => 'These showtimes already have bookings and cannot be removed: '
                    . $removedWithBookings->map(fn ($st) => $st->start_time->format('M j, Y g:i A') . ' (' . $st->hall->name . ')')->implode(', ')
                    . '. Cancel their bookings first.',
            ]);
        }

        // ── Check 1: conflicts within the submitted rows themselves ────────────
        // This catches overlaps between two NEW rows in the same submission
        // before anything is saved to the database.
        $submitted = collect($request->showtimes)->map(function ($st) use ($movie) {
            return [
                'hall_id'  => (int) $st['hall_id'],
                'start_ts' => Carbon::parse($st['start_time'])->timestamp,
                'end_ts'   => Carbon::parse($st['start_time'])->addMinutes($movie->duration)->timestamp,
                'id'       => !empty($st['id']) ? (int) $st['id'] : null,
            ];
        }); // keep the form's row keys so errors point at the right row

        foreach ($submitted as $i => $rowA) {
            foreach ($submitted as $j => $rowB) {
                if ($i >= $j) continue; // only check each pair once
                if ($rowA['hall_id'] !== $rowB['hall_id']) continue; // different halls, no conflict

                $overlaps = $rowA['start_ts'] < $rowB['end_ts']
                         && $rowA['end_ts']   > $rowB['start_ts'];

                if ($overlaps) {
                    $hall = Halls::find($rowA['hall_id']);
                    return back()->withInput()->withErrors([
                        "showtimes.{$j}.start_time" =>
                            'This showtime overlaps with another showtime you have added in the same hall ("'
                            . ($hall->name ?? 'selected') . '"). Please space them further apart.',
                    ]);
                }
            }
        }

        // ── Check 2: conflicts against other movies' showtimes in the DB ─────
        // This movie's own showtimes are either in this submission (Check 1)
        // or about to be removed, so their old times must not count as conflicts.
        foreach ($request->showtimes as $index => $stData) {
            if ($this->hasConflict((int) $stData['hall_id'], $stData['start_time'], $movie->duration, null, $movie->id)) {
                $hall = Halls::find($stData['hall_id']);
                return back()->withInput()->withErrors([
                    "showtimes.{$index}.start_time" =>
                        'Hall "' . ($hall->name ?? 'selected') . '" is already booked during this time.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $movie, $existing) {
            $keepIds = [];

            foreach ($request->showtimes as $stData) {
                $attributes = [
                    'hall_id'    => $stData['hall_id'],
                    'start_time' => $stData['start_time'],
                    'end_time'   => Carbon::parse($stData['start_time'])->addMinutes($movie->duration),
                    'price'      => $stData['price'],
                ];

                if (!empty($stData['id'])) {
                    $showtime = $existing->get((int) $stData['id']);
                    $showtime->update($attributes);
                } else {
                    $showtime = Showtimes::create($attributes + ['movie_id' => $movie->id]);
                }

                $keepIds[] = $showtime->id;
            }

            // Delete removed showtimes (Check 0 guarantees none of them have bookings)
            Showtimes::where('movie_id', $movie->id)
                ->whereNotIn('id', $keepIds)
                ->delete();

            // Keep legacy show_time_id pointing at the movie's earliest showtime
            $movie->show_time_id = Showtimes::whereIn('id', $keepIds)->orderBy('start_time')->value('id');
            $movie->save();
        });

        return redirect()->route('admin.movies.index')
            ->with('success', 'Showtimes saved for "' . $movie->title . '".');
    }

    // MOVIES - STORE
    // STEP 1 - save movie details only, then redirect to scheduling
    public function storeMovie(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'genre_id'     => 'required|exists:genres,id',
            'release_date' => 'required|date',
            'duration'     => 'required|integer|min:1',
                'poster'       => 'nullable|image|max:2048',
            'trailer_url'  => 'nullable|url|max:255',
        ]);

        $movie = new Movies();
        $movie->title        = $validated['title'];
        $movie->description  = $validated['description'];
        $movie->genre_id     = $validated['genre_id'];
        $movie->release_date = $validated['release_date'];
        $movie->duration     = $validated['duration'];
            $movie->trailer_url  = $validated['trailer_url'] ?? null;

        if ($request->hasFile('poster')) {
            $movie->poster = $request->file('poster')->store('posters', 'public');
        }

        $movie->save();

        return redirect()->route('admin.movies.showtimes', $movie)
            ->with('success', 'Movie details saved. Now add your showtimes below.');
    }

    // MOVIES - EDIT VIEW (details only)
    public function editMovie(Movies $movie)
    {
        $genres = Genres::all();
        return view('admin.movies.edit', compact('movie', 'genres'));
    }

    // MOVIES - UPDATE
    public function updateMovie(Request $request, Movies $movie)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'genre_id' => 'required|exists:genres,id',
            'release_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'poster' => 'nullable|image|max:2048',
            'trailer_url' => 'nullable|url|max:255',
        ]);

        // Showtimes are managed on the separate scheduling page. A new duration
        // changes when this movie's upcoming screenings end, so make sure the
        // longer runtime doesn't run into the next screening in the same hall.
        $durationChanged = (int) $validated['duration'] !== (int) $movie->duration;
        $upcomingShowtimes = $durationChanged
            ? $movie->showtimes()->with('hall')->where('end_time', '>', now())->get()
            : collect();

        foreach ($upcomingShowtimes as $st) {
            if ($this->hasConflict($st->hall_id, $st->start_time->format('Y-m-d H:i:s'), (int) $validated['duration'], $st->id)) {
                return back()->withInput()->withErrors([
                    'duration' => 'With a ' . $validated['duration'] . '-minute runtime, the '
                        . $st->start_time->format('M j, g:i A') . ' screening in hall "' . $st->hall->name
                        . '" would overlap the next screening. Reschedule it first.',
                ]);
            }
        }

        $movie->title = $validated['title'];
        $movie->description = $validated['description'];
        $movie->genre_id = $validated['genre_id'];
        $movie->release_date = $validated['release_date'];
        $movie->duration = $validated['duration'];
        $movie->trailer_url = $validated['trailer_url'] ?? null;

        if ($request->hasFile('poster')) {
            if ($movie->poster) {
                Storage::disk('public')->delete($movie->poster);
            }
            $movie->poster = $request->file('poster')->store('posters', 'public');
        }

        DB::transaction(function () use ($movie, $upcomingShowtimes) {
            $movie->save();

            foreach ($upcomingShowtimes as $st) {
                $st->update(['end_time' => $st->start_time->copy()->addMinutes($movie->duration)]);
            }
        });

        return redirect()->route('admin.movies.index')
                       ->with('success', 'Movie updated successfully');
    }

    // MOVIES - DELETE
    public function deleteMovie(Movies $movie)
    {
        if ($movie->poster) {
            Storage::disk('public')->delete($movie->poster);
        }
        $movie->delete();
        return redirect()->route('admin.movies.index')
                       ->with('success', 'Movie deleted successfully');
    }

    // CINEMAS MANAGEMENT
    public function cinemasList(Request $request)
    {
        $query = Cinemas::query();

        // Search
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
        }

        // Pagination
        $perPage = $request->per_page ?? 10;
        $cinemas = $query->paginate($perPage);

        return view('admin.cinemas.index', compact('cinemas'));
    }

    // CINEMAS - CREATE VIEW
    public function createCinema()
    {
        return view('admin.cinemas.form');
    }

    // CINEMAS - STORE
    public function storeCinema(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $cinema = new Cinemas();
        $cinema->name = $validated['name'];
        $cinema->location = $validated['location'];
        $cinema->save();

        return redirect()->route('admin.cinemas.index')
                       ->with('success', 'Cinema created successfully');
    }

    // CINEMAS - EDIT VIEW
    public function editCinema(Cinemas $cinema)
    {
        return view('admin.cinemas.form', compact('cinema'));
    }

    // CINEMAS - UPDATE
    public function updateCinema(Request $request, Cinemas $cinema)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $cinema->name = $validated['name'];
        $cinema->location = $validated['location'];
        $cinema->save();

        return redirect()->route('admin.cinemas.index')
                       ->with('success', 'Cinema updated successfully');
    }

    // CINEMAS - DELETE
    public function deleteCinema(Cinemas $cinema)
    {
        $cinema->delete();
        return redirect()->route('admin.cinemas.index')
                       ->with('success', 'Cinema deleted successfully');
    }

    // HALLS MANAGEMENT
    public function storeHall(Request $request)
    {
        $validated = $request->validate([
            'cinema_id'       => 'required|exists:cinemas,id',
            'name'            => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = Halls::where('cinema_id', $request->cinema_id)
                        ->where('name', $value)
                        ->exists();
                    if ($exists) {
                        $fail('A hall with this name already exists in this cinema.');
                    }
                },
            ],
            'capacity'        => 'required|integer|min:1|max:500',
            'projection_type' => 'required|in:2D,3D,IMAX',
        ]);

        $hall = Halls::create([
            'cinema_id'       => $validated['cinema_id'],
            'name'            => $validated['name'],
            'capacity'        => $validated['capacity'],
            'projection_type' => $validated['projection_type'],
        ]);

        // Auto-generate seats for the hall
        $this->generateSeats($hall);

        return back()->with('success', 'Hall "' . $hall->name . '" created with ' . $hall->capacity . ' seats.');
    }

    public function updateHall(Request $request, Halls $hall)
    {
        $oldCapacity = $hall->capacity;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($hall) {
                    $exists = Halls::where('cinema_id', $hall->cinema_id)
                        ->where('name', $value)
                        ->where('id', '!=', $hall->id)
                        ->exists();
                    if ($exists) {
                        $fail('A hall with this name already exists in this cinema.');
                    }
                },
            ],
            'capacity'        => 'required|integer|min:1|max:500',
            'projection_type' => 'required|in:2D,3D,IMAX',
        ]);

        $hall->update([
            'name'            => $validated['name'],
            'capacity'        => $validated['capacity'],
            'projection_type' => $validated['projection_type'],
        ]);

        // If capacity changed, regenerate all seats
        if ((int)$validated['capacity'] !== (int)$oldCapacity) {
            $hall->seats()->delete();
            $this->generateSeats($hall);
        }

        return back()->with('success', 'Hall updated successfully.');
    }

    /**
     * Generate seat rows for a hall based on its capacity.
     * Layout: fill rows A, B, C… with up to 10 seats each.
     * e.g. capacity=20 → A1–A10, B1–B10
     *       capacity=15 → A1–A10, B1–B5
     */
    private function generateSeats(Halls $hall): void
    {
        $capacity    = $hall->capacity;
        $seatsPerRow = 10;
        $rows        = range('A', 'Z'); // up to 260 seats
        $rowIndex    = 0;
        $inserted    = 0;

        while ($inserted < $capacity) {
            $rowLabel    = $rows[$rowIndex] ?? chr(65 + $rowIndex); // fallback
            $seatsInRow  = min($seatsPerRow, $capacity - $inserted);

            for ($seatNum = 1; $seatNum <= $seatsInRow; $seatNum++) {
                Seats::create([
                    'hall_id'    => $hall->id,
                    'row_number' => $rowLabel,
                    'number'     => $seatNum,
                ]);
            }

            $inserted += $seatsInRow;
            $rowIndex++;
        }
    }

    public function deleteHall(Halls $hall)
    {
        $hall->delete();
        return back()->with('success', 'Hall deleted successfully');
    }

    public function getHallsByCinema(Cinemas $cinema)
    {
        return response()->json($cinema->halls);
    }

    public function getShowtimesByHall($hallId)
    {
        $showtimes = Showtimes::where('hall_id', $hallId)
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->get();
        return response()->json($showtimes);
    }


    // BOOKINGS MANAGEMENT
    public function bookingsList(Request $request)
    {
        $query = Bookings::with([
                'user',
                'showtime.movie',
                'showtime.hall.cinema',
                'payment',
                'bookings_seats',
            ])
            ->orderByDesc('id');

        // Search by customer name or movie title
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('showtime.movie', fn($m) => $m->where('title', 'like', "%{$search}%"));
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(20)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function updateBookingStatus(Request $request, Bookings $booking)
    {
        $request->validate(['status' => 'required|in:confirmed,pending,canceled']);
        $booking->update(['status' => $request->status]);
        return back()->with('success', "Booking #{$booking->id} status updated to {$request->status}.");
    }

    // SETTINGS
    public function settings()
    {
        return view('admin.settings');
    }
}