<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile — CineMax</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:       #0d0d0f;
            --surface:  #141417;
            --surface2: #1c1c21;
            --surface3: #232329;
            --accent:   #e8340a;
            --accent2:  #ff6b35;
            --gold:     #f5c518;
            --text:     #f0eff4;
            --muted:    rgba(240,239,244,0.5);
            --border:   rgba(240,239,244,0.08);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: rgba(13,13,15,0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 0.9rem 0;
            position: sticky; top: 0; z-index: 200;
        }
        .navbar-brand {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            color: var(--accent) !important;
            letter-spacing: 2px;
            text-decoration: none;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .brand-dot { width: 8px; height: 8px; background: var(--accent); border-radius: 50%; display: inline-block; animation: pulse-dot 2s ease-in-out infinite; }
        @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)} }
        .nav-link { color: var(--muted) !important; font-size: .88rem; font-weight: 500; letter-spacing: .5px; padding: .4rem 1rem !important; transition: color .2s; }
        .nav-link:hover { color: var(--text) !important; }
        .dropdown-menu { background: var(--surface2); border: 1px solid var(--border); border-radius: .5rem; }
        .dropdown-item { color: var(--muted); font-size: .88rem; }
        .dropdown-item:hover, .dropdown-item.active { color: var(--text); background: rgba(232,52,10,.2); }

        /* ── LAYOUT ── */
        .profile-wrap { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        /* ── HERO BANNER ── */
        .profile-hero {
            position: relative;
            background: linear-gradient(135deg, var(--surface) 0%, #1a0a08 100%);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            padding: 2.5rem 2rem 1.5rem;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .profile-hero::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(ellipse 80% 60% at 70% 50%, rgba(232,52,10,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-inner { display: flex; align-items: center; gap: 2rem; flex-wrap: wrap; position: relative; }

        /* Avatar */
        .avatar-wrap { position: relative; flex-shrink: 0; }
        .avatar-img {
            width: 100px; height: 100px; border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
            display: block;
        }
        .avatar-initials {
            width: 100px; height: 100px; border-radius: 50%;
            background: var(--accent);
            color: #fff;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.5rem;
            display: flex; align-items: center; justify-content: center;
            border: 3px solid rgba(232,52,10,.4);
        }
        .avatar-upload-btn {
            position: absolute; bottom: 0; right: 0;
            width: 30px; height: 30px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--muted); font-size: .85rem;
            transition: background .2s, color .2s;
        }
        .avatar-upload-btn:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
        #avatarInput { display: none; }

        .hero-info { flex: 1; min-width: 200px; }
        .hero-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.2rem; letter-spacing: 2px;
            color: var(--text); line-height: 1; margin-bottom: .3rem;
        }
        .hero-email { color: var(--muted); font-size: .9rem; margin-bottom: .75rem; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: .35rem;
            padding: .25rem .65rem; border-radius: 999px;
            font-size: .75rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase;
        }
        .badge-active   { background: rgba(34,197,94,.15); color: #4ade80; border: 1px solid rgba(34,197,94,.3); }
        .badge-pending  { background: rgba(245,197,24,.15); color: #f5c518; border: 1px solid rgba(245,197,24,.3); }
        .badge-banned   { background: rgba(220,53,69,.15);  color: #f87171; border: 1px solid rgba(220,53,69,.3); }

        .hero-stats { display: flex; gap: 2rem; flex-wrap: wrap; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border); }
        .stat-item { text-align: center; }
        .stat-num { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; color: var(--accent); letter-spacing: 1px; line-height: 1; }
        .stat-lbl { font-size: .72rem; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--muted); margin-top: .15rem; }

        /* ── TABS ── */
        .tab-nav {
            display: flex; gap: .5rem; margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
        }
        .tab-btn {
            background: none; border: none; border-bottom: 2px solid transparent;
            padding: .6rem 1.1rem .7rem;
            color: var(--muted); font-family: 'DM Sans', sans-serif;
            font-size: .88rem; font-weight: 600; letter-spacing: .3px;
            cursor: pointer; margin-bottom: -1px;
            transition: color .2s, border-color .2s;
        }
        .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }
        .tab-btn:hover:not(.active) { color: var(--text); }

        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── CARDS ── */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
        }

        /* Booking item */
        .booking-row {
            display: flex; gap: 1rem; align-items: flex-start;
            padding: .9rem 0;
            border-bottom: 1px solid var(--border);
        }
        .booking-row:last-child { border-bottom: none; }
        .booking-poster {
            width: 52px; height: 72px; border-radius: .4rem;
            object-fit: cover; background: var(--surface2); flex-shrink: 0;
        }
        .booking-poster-placeholder {
            width: 52px; height: 72px; border-radius: .4rem;
            background: var(--surface2); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            color: var(--muted); font-size: 1.4rem;
        }
        .booking-info { flex: 1; min-width: 0; }
        .booking-title { font-weight: 600; font-size: 1rem; margin-bottom: .25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .booking-meta { color: var(--muted); font-size: .82rem; display: flex; flex-wrap: wrap; gap: .4rem .75rem; }
        .chip {
            display: inline-flex; align-items: center; gap: .25rem;
            padding: .2rem .55rem; border-radius: 999px;
            font-size: .75rem; font-weight: 600;
        }
        .chip-red    { background: rgba(232,52,10,.15); color: var(--accent2); }
        .chip-green  { background: rgba(34,197,94,.15); color: #4ade80; }
        .chip-yellow { background: rgba(245,197,24,.15); color: #f5c518; }
        .chip-grey   { background: rgba(240,239,244,.08); color: var(--muted); }

        /* Review item */
        .review-row {
            padding: 1rem 0; border-bottom: 1px solid var(--border);
        }
        .review-row:last-child { border-bottom: none; }
        .review-movie { font-weight: 600; margin-bottom: .3rem; }
        .review-stars { color: #f5c518; font-size: .9rem; margin-bottom: .4rem; }
        .review-comment { color: var(--muted); font-size: .88rem; line-height: 1.5; }

        /* Empty state */
        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--muted); }
        .empty-state i { font-size: 2.5rem; margin-bottom: 1rem; display: block; }

        /* ── FORM (Edit Profile) ── */
        .form-section-title {
            font-size: .75rem; font-weight: 700; letter-spacing: 1.5px;
            text-transform: uppercase; color: var(--muted);
            margin-bottom: 1rem; padding-bottom: .5rem;
            border-bottom: 1px solid var(--border);
        }
        .field-group { margin-bottom: 1.25rem; }
        .field-label { font-size: .82rem; font-weight: 600; color: var(--muted); margin-bottom: .35rem; }
        .field-input {
            width: 100%; padding: .65rem .85rem;
            background: var(--surface2); border: 1px solid var(--border);
            border-radius: .45rem; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: .9rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .field-input:focus {
            outline: none; border-color: rgba(232,52,10,.5);
            box-shadow: 0 0 0 3px rgba(232,52,10,.1);
        }
        .field-input::placeholder { color: var(--muted); }
        .btn-save {
            background: var(--accent); color: #fff; border: none;
            padding: .65rem 1.5rem; border-radius: .45rem;
            font-family: 'DM Sans', sans-serif; font-weight: 700;
            cursor: pointer; transition: background .2s, transform .15s;
        }
        .btn-save:hover { background: #c42908; transform: translateY(-1px); }

        /* ── ALERTS ── */
        .alert-custom {
            border-radius: .6rem; padding: .8rem 1rem;
            font-size: .875rem; margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: .6rem;
        }
        .alert-success-c { background: rgba(34,197,94,.1); border: 1px solid rgba(34,197,94,.3); color: #4ade80; }
        .alert-error-c   { background: rgba(220,53,69,.1);  border: 1px solid rgba(220,53,69,.3);  color: #f87171; }
    </style>
</head>
<body>

{{-- NAVBAR --}}
<nav class="navbar">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand" href="/"><i class="bi bi-play-circle-fill"></i> CINEMAX <span class="brand-dot"></span></a>
            <a class="nav-link" href="/">Home</a>
            <a class="nav-link" href="{{ route('cinemas.index') }}">Cinema</a>
            <div class="dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Movies</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('movies.index', ['status' => 'now-showing']) }}">Now Showing</a></li>
                    <li><a class="dropdown-item" href="{{ route('movies.index', ['status' => 'coming-soon']) }}">Coming Soon</a></li>
                </ul>
            </div>
            @auth
                <a class="nav-link" href="{{ route('bookings.index') }}">My Bookings</a>
            @endauth
        </div>
        <div class="d-flex align-items-center">
            @include('partials.user-menu')
        </div>
    </div>
</nav>

<div class="profile-wrap">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert-custom alert-success-c"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert-custom alert-error-c"><i class="bi bi-exclamation-triangle-fill"></i> {{ $errors->first() }}</div>
    @endif

    {{-- HERO --}}
    <div class="profile-hero">
        <div class="hero-inner">
            {{-- Avatar --}}
            <div class="avatar-wrap">
                @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}" alt="Avatar" class="avatar-img">
                @else
                    <div class="avatar-initials">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                @endif
                <label class="avatar-upload-btn" for="avatarInput" title="Change photo">
                    <i class="bi bi-camera-fill"></i>
                </label>
                <form id="avatarForm" method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="document.getElementById('avatarForm').submit()">
                </form>
            </div>

            {{-- Info --}}
            <div class="hero-info">
                <div class="hero-name">{{ $user->name }}</div>
                <div class="hero-email"><i class="bi bi-envelope me-1"></i>{{ $user->email }}</div>
                <span class="hero-badge badge-{{ $user->status ?? 'active' }}">
                    <i class="bi bi-circle-fill" style="font-size:.45rem;"></i>
                    {{ ucfirst($user->status ?? 'active') }}
                </span>
            </div>
        </div>

        {{-- Stats --}}
        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-num">{{ $bookedMovies->count() }}</div>
                <div class="stat-lbl">Upcoming</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">{{ $watchedMovies->count() }}</div>
                <div class="stat-lbl">Watched</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">{{ $reviews->count() }}</div>
                <div class="stat-lbl">Reviews</div>
            </div>
            @if($reviews->count())
            <div class="stat-item">
                <div class="stat-num" style="color:var(--gold);">{{ number_format($reviews->avg('rating'), 1) }}</div>
                <div class="stat-lbl">Avg Rating</div>
            </div>
            @endif
        </div>
    </div>

    {{-- TABS --}}
    <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab(event,'tab-booked')"><i class="bi bi-ticket-perforated me-1"></i>Upcoming</button>
        <button class="tab-btn" onclick="switchTab(event,'tab-watched')"><i class="bi bi-eye me-1"></i>Watched</button>
        <button class="tab-btn" onclick="switchTab(event,'tab-reviews')"><i class="bi bi-star me-1"></i>Reviews</button>
        <button class="tab-btn" onclick="switchTab(event,'tab-edit')"><i class="bi bi-pencil me-1"></i>Edit Profile</button>
    </div>

    {{-- ── TAB: UPCOMING ── --}}
    <div id="tab-booked" class="tab-pane active">
        <div class="section-card">
            @forelse($bookedMovies as $booking)
                <div class="booking-row">
                    @if($booking->showtime->movie->poster ?? null)
                        <img src="{{ $booking->showtime->movie->poster_url }}" alt="Poster" class="booking-poster">
                    @else
                        <div class="booking-poster-placeholder"><i class="bi bi-film"></i></div>
                    @endif
                    <div class="booking-info">
                        <div class="booking-title">{{ $booking->showtime->movie->title ?? 'Unknown Movie' }}</div>
                        <div class="booking-meta">
                            <span><i class="bi bi-calendar3"></i> {{ optional($booking->showtime->start_time)->format('M d, Y · g:i A') }}</span>
                            <span><i class="bi bi-building"></i> {{ $booking->showtime->hall->cinema->name ?? 'N/A' }} — {{ $booking->showtime->hall->name ?? '' }}</span>
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <span class="chip chip-{{ $booking->status === 'confirmed' ? 'green' : ($booking->status === 'canceled' ? 'red' : 'yellow') }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                            <span class="chip chip-grey"><i class="bi bi-clock"></i> {{ optional($booking->showtime->start_time)->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-ticket"></i>
                    <p>No upcoming bookings yet.</p>
                    <a href="{{ route('movies.index', ['status' => 'now-showing']) }}" class="btn-save mt-2" style="text-decoration:none;display:inline-block;">Browse Movies</a>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── TAB: WATCHED ── --}}
    <div id="tab-watched" class="tab-pane">
        <div class="section-card">
            @forelse($watchedMovies as $booking)
                <div class="booking-row">
                    @if($booking->showtime->movie->poster ?? null)
                        <img src="{{ $booking->showtime->movie->poster_url }}" alt="Poster" class="booking-poster">
                    @else
                        <div class="booking-poster-placeholder"><i class="bi bi-film"></i></div>
                    @endif
                    <div class="booking-info">
                        <div class="booking-title">{{ $booking->showtime->movie->title ?? 'Unknown Movie' }}</div>
                        <div class="booking-meta">
                            <span><i class="bi bi-calendar-check"></i> Watched {{ optional($booking->showtime->start_time)->format('M d, Y') }}</span>
                            <span><i class="bi bi-building"></i> {{ $booking->showtime->hall->cinema->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <span class="chip chip-green"><i class="bi bi-check-circle"></i> Watched</span>
                            @php
                                $alreadyReviewed = $reviews->where('movie_id', $booking->showtime->movie_id)->isNotEmpty();
                            @endphp
                            @if(!$alreadyReviewed)
                                <a href="{{ route('movies.show', $booking->showtime->movie_id) }}" class="chip chip-red" style="text-decoration:none;">
                                    <i class="bi bi-star"></i> Leave Review
                                </a>
                            @else
                                <span class="chip chip-yellow"><i class="bi bi-star-fill"></i> Reviewed</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-camera-reels"></i>
                    <p>No watched movies yet. Book a ticket and enjoy a show!</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── TAB: REVIEWS ── --}}
    <div id="tab-reviews" class="tab-pane">
        <div class="section-card">
            @forelse($reviews as $review)
                <div class="review-row">
                    <div class="review-movie">{{ $review->movie->title ?? 'Unknown Movie' }}</div>
                    <div class="review-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                        @endfor
                        <span style="color:var(--muted);font-size:.78rem;margin-left:.35rem;">{{ $review->rating }}/5</span>
                    </div>
                    @if($review->comment)
                        <div class="review-comment">"{{ $review->comment }}"</div>
                    @endif
                    <div style="color:var(--muted);font-size:.75rem;margin-top:.35rem;">
                        {{ $review->created_at->format('M d, Y') }}
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-star"></i>
                    <p>You haven't written any reviews yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── TAB: EDIT PROFILE ── --}}
    <div id="tab-edit" class="tab-pane">
        <div class="section-card">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                <div class="form-section-title">Account Information</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="field-group">
                            <div class="field-label">Full Name</div>
                            <input type="text" name="name" class="field-input" value="{{ old('name', $user->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-group">
                            <div class="field-label">Email Address</div>
                            <input type="email" name="email" class="field-input" value="{{ old('email', $user->email) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-section-title">Change Password <span style="font-size:.75rem;font-weight:400;text-transform:none;">(leave blank to keep current)</span></div>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="field-group">
                            <div class="field-label">Current Password</div>
                            <input type="password" name="current_password" class="field-input" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-group">
                            <div class="field-label">New Password</div>
                            <input type="password" name="password" class="field-input" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-group">
                            <div class="field-label">Confirm New Password</div>
                            <input type="password" name="password_confirmation" class="field-input" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-save"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
            </form>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    function switchTab(e, id) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        e.currentTarget.classList.add('active');
        document.getElementById(id).classList.add('active');
    }

    // If there's an error from the edit form, auto-open that tab
    @if($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.tab-btn')[3]?.click();
        });
    @endif
</script>
</body>
</html>
