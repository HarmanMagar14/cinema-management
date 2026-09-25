<?php

namespace Database\Seeders;

use App\Models\Genres;
use App\Models\Halls;
use App\Models\Movies;
use App\Models\Roles;
use App\Models\Showtimes;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dummy customers and movies (with conflict-free upcoming showtimes) for local testing.
 *
 * Safe to run more than once: users are matched by email and movies by title,
 * and showtimes are only scheduled for movies this run created.
 *
 *   php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    private const AVATAR_COLORS = ['#e8340a', '#ff6b35', '#f5c518', '#22c55e', '#3b82f6', '#a855f7', '#ec4899', '#14b8a6'];

    /** Gap between screenings in the same hall, for cleaning and seating. */
    private const TURNOVER_MINUTES = 30;

    /** Poster colours per genre: [sky top, sky bottom, accent, text]. */
    private const POSTER_PALETTES = [
        'Sci-Fi'    => ['#1b0b3a', '#05030f', '#22d3ee', '#f0f9ff'],
        'Drama'     => ['#1e2a3a', '#07090d', '#f5b041', '#fdf6e3'],
        'Action'    => ['#3a0a06', '#080303', '#ff5a1f', '#fff7ed'],
        'Romance'   => ['#3d0f2c', '#0d0309', '#ff8fb1', '#fff1f5'],
        'Horror'    => ['#101a14', '#010302', '#d61f1f', '#e7e5e4'],
        'Fantasy'   => ['#241047', '#07030f', '#d8b4fe', '#faf5ff'],
        'Comedy'    => ['#ffd23f', '#ff7b1c', '#1c1917', '#1c1917'],
        'Animation' => ['#38bdf8', '#6d28d9', '#fde047', '#ffffff'],
        'Thriller'  => ['#1f2937', '#030712', '#e5e7eb', '#f9fafb'],
        'Adventure' => ['#f97316', '#1e1b4b', '#fde68a', '#fffbeb'],
    ];

    /** Artwork for movies that would otherwise look the same as another movie of their genre. */
    private const POSTER_ART = [
        'Starlight Express' => 'Space',
        'Hometown Heroes'   => 'Basketball',
    ];

    public function run(): void
    {
        $this->seedUsers();
        $this->seedMovies();
    }

    private function seedUsers(): void
    {
        $customerRole = Roles::firstOrCreate(['name' => 'customer']);

        $customers = [
            ['Maria Santos', 'active'], ['Juan Dela Cruz', 'active'], ['Angela Reyes', 'active'],
            ['Mark Villanueva', 'active'], ['Kristine Bautista', 'active'], ['Paolo Mendoza', 'active'],
            ['Camille Garcia', 'active'], ['Rafael Aquino', 'active'], ['Bea Castillo', 'active'],
            ['Miguel Ramos', 'active'], ['Isabel Navarro', 'active'], ['Carlo Fernandez', 'active'],
            ['Andrea Lim', 'active'], ['Joshua Tan', 'active'], ['Patricia Cruz', 'active'],
            ['Daniel Soriano', 'active'], ['Nicole Pascual', 'active'], ['Gabriel Torres', 'active'],
            ['Sofia Morales', 'active'], ['Adrian Salazar', 'active'],
            ['Liza Manalo', 'pending'], ['Kevin Ocampo', 'pending'], ['Jasmine Rivera', 'pending'],
            ['Ramon Aguilar', 'banned'], ['Trisha Domingo', 'banned'],
        ];

        foreach ($customers as $i => [$name, $status]) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';

            $user = User::firstOrNew(['email' => $email]);
            if ($user->exists) {
                continue;
            }

            $joined = now()->subDays(rand(0, 90))->setTime(rand(8, 22), rand(0, 59));

            $user->forceFill([
                'name'         => $name,
                'password'     => Hash::make('password'),
                'role_id'      => $customerRole->id,
                'status'       => $status,
                'avatar_color' => self::AVATAR_COLORS[$i % count(self::AVATAR_COLORS)],
                'last_seen'    => $status === 'active' ? $joined->copy()->addDays(rand(0, 5)) : null,
                'created_at'   => $joined,
                'updated_at'   => $joined,
            ])->save();
        }

        $this->command?->info('Customers: ' . count($customers) . ' dummy accounts (password: "password").');
    }

    private function seedMovies(): void
    {
        $genreId = fn (string $name) => Genres::firstOrCreate(['name' => $name])->id;

        // [title, genre, minutes, description, days until first screening (null = showing now), poster tagline]
        $movies = [
            ['Neon Skyline', 'Sci-Fi', 128, 'In a city that never sleeps, a courier discovers the data chip she carries could switch off every machine in the megacity.', null, 'The city never sleeps. Neither can she.'],
            ['The Last Ferry', 'Drama', 114, 'Strangers stranded on the final ferry of the night confront the secrets that brought each of them aboard.', null, 'Every passenger is running from something.'],
            ['Iron Monsoon', 'Action', 135, 'A disgraced coast guard captain leads an impossible rescue as a super typhoon closes in on a small island town.', null, 'One captain. One storm. No way back.'],
            ['Midnight at Manila Bay', 'Romance', 106, 'Two rival food-truck owners fall for each other one late-night service at a time.', null, 'Love is best served late.'],
            ['Whispers in the Walls', 'Horror', 98, 'A family moves into an old ancestral house where the walls remember everyone who ever lived there.', null, 'This house remembers.'],
            ['Kingdom of Paper', 'Fantasy', 142, 'An apprentice mapmaker learns that whatever she draws becomes real, and that someone wants her maps.', null, 'Draw carefully.'],
            ['Laugh Track', 'Comedy', 101, 'A washed-up sitcom star fakes his own comeback, and it goes accidentally, wildly viral.', null, 'His comeback is a total fake. Nobody can tell.'],
            ['Pixel & Pip', 'Animation', 92, 'A tiny robot and a runaway puppy cross a sprawling toy city to find their way home.', null, 'Small friends. Big adventure.'],
            ['Cold Signal', 'Thriller', 119, 'A night-shift radio operator picks up a distress call from a ship that sank twenty years ago.', 10, 'Some calls should never be answered.'],
            ['Summit Seven', 'Adventure', 131, 'Seven climbers, one unclimbed peak, and a storm that gives them only one way down.', 14, 'Seven went up. The mountain decides the rest.'],
            ['Starlight Express', 'Sci-Fi', 124, 'Passengers on the first commercial flight to the Moon realise the crew is not who they seem.', 18, 'Next stop: the Moon. Final stop: unknown.'],
            ['Hometown Heroes', 'Comedy', 97, 'A barangay basketball team of misfits somehow reaches the national finals.', 21, 'Nobody believed in them. Fair enough.'],
        ];

        $halls = Halls::orderBy('id')->get();
        if ($halls->isEmpty()) {
            $this->command?->warn('No halls found. Run the main DatabaseSeeder first; skipping movies.');
            return;
        }

        $created = 0;
        foreach ($movies as $i => [$title, $genre, $minutes, $description, $startsInDays, $tagline]) {
            $poster = $this->generatePoster($title, $genre, $tagline, $startsInDays, $i);

            // Existing dummy movies only get their poster refreshed
            if ($existing = Movies::where('title', $title)->first()) {
                $existing->update(['poster' => $poster]);
                continue;
            }

            $movie = Movies::create([
                'title'        => $title,
                'description'  => $description,
                'genre_id'     => $genreId($genre),
                'duration'     => $minutes,
                'release_date' => $startsInDays === null ? now()->subDays(rand(3, 20))->toDateString() : now()->addDays($startsInDays)->toDateString(),
                'poster'       => $poster,
                'trailer_url'  => null,
            ]);

            $hall = $halls[$i % $halls->count()];
            $this->scheduleShowtimes($movie, $hall, $startsInDays);
            $created++;
        }

        $this->command?->info("Movies: {$created} dummy movies created with showtimes.");
    }

    /**
     * Draw a poster as an SVG (so no PHP image extension is needed), save it to
     * storage/app/public/posters and return the path for the movies.poster column.
     */
    private function generatePoster(string $title, string $genre, string $tagline, ?int $startsInDays, int $seed): string
    {
        [$top, $bottom, $accent, $ink] = self::POSTER_PALETTES[$genre] ?? self::POSTER_PALETTES['Drama'];
        $e = fn (string $text) => htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $display = "Impact, 'Bebas Neue', Oswald, 'Arial Narrow', sans-serif";
        $body = "'Segoe UI', Arial, sans-serif";

        // Title: wrap to ~11 characters per line and size it to fit the poster width
        $lines = [];
        foreach (explode(' ', mb_strtoupper($title)) as $word) {
            $last = count($lines) - 1;
            if ($last >= 0 && mb_strlen($lines[$last] . ' ' . $word) <= 11) {
                $lines[$last] .= ' ' . $word;
            } else {
                $lines[] = $word;
            }
        }
        $longest = max(array_map('mb_strlen', $lines));
        $fontSize = (int) min(130, 520 / ($longest * 0.6));
        $lineHeight = (int) round($fontSize * 0.95);
        $firstBaseline = 745 - $lineHeight * (count($lines) - 1);

        $titleSvg = '';
        foreach ($lines as $n => $line) {
            $y = $firstBaseline + $n * $lineHeight;
            $titleSvg .= '<text x="300" y="' . $y . '" font-family="' . $display . '" font-size="' . $fontSize . '" fill="' . $ink . '" text-anchor="middle" letter-spacing="2">' . $e($line) . '</text>';
        }

        $release = $startsInDays === null ? 'NOW SHOWING' : 'IN CINEMAS ' . strtoupper(now()->addDays($startsInDays)->format('M j'));

        // Small text shrinks to fit: width ≈ characters × (0.6 × size + letter-spacing)
        $fitSize = fn (string $text, float $max, float $spacing, int $width) => round(min($max, ($width / mb_strlen($text) - $spacing) / 0.6), 1);
        $credits = 'CINEMAX PICTURES PRESENTS · A ' . mb_strtoupper($genre) . ' FEATURE · ' . mb_strtoupper($title);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="900" viewBox="0 0 600 900">'
            . '<defs>'
            . '<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $top . '"/><stop offset="1" stop-color="' . $bottom . '"/></linearGradient>'
            . '<radialGradient id="glow" cx="0.5" cy="0.5" r="0.5"><stop offset="0" stop-color="' . $accent . '" stop-opacity="0.55"/><stop offset="1" stop-color="' . $accent . '" stop-opacity="0"/></radialGradient>'
            . '<linearGradient id="fade" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . $bottom . '" stop-opacity="0"/><stop offset="1" stop-color="' . $bottom . '" stop-opacity="0.95"/></linearGradient>'
            . '</defs>'
            . '<rect width="600" height="900" fill="url(#sky)"/>'
            . $this->posterArtwork(self::POSTER_ART[$title] ?? $genre, $accent, $bottom, $seed)
            . '<rect y="480" width="600" height="420" fill="url(#fade)"/>'
            . '<text x="300" y="72" font-family="' . $body . '" font-size="' . $fitSize($tagline, 17, 3, 530) . '" fill="' . $ink . '" fill-opacity="0.85" text-anchor="middle" letter-spacing="3">' . $e(mb_strtoupper($tagline)) . '</text>'
            . $titleSvg
            . '<rect x="250" y="' . (745 + 26) . '" width="100" height="4" fill="' . $accent . '"/>'
            . '<text x="300" y="812" font-family="' . $display . '" font-size="26" fill="' . $accent . '" text-anchor="middle" letter-spacing="6">' . $release . '</text>'
            . '<text x="300" y="848" font-family="' . $body . '" font-size="' . $fitSize($credits, 10, 1.5, 540) . '" fill="' . $ink . '" fill-opacity="0.55" text-anchor="middle" letter-spacing="1.5">' . $e($credits) . '</text>'
            . '<text x="300" y="868" font-family="' . $body . '" font-size="10" fill="' . $ink . '" fill-opacity="0.4" text-anchor="middle" letter-spacing="1.5">MUSIC BY R. SANTOS · EDITED BY J. REYES · DIRECTED BY A. MENDOZA</text>'
            . '</svg>';

        $path = 'posters/' . Str::slug($title) . '.svg';
        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    /** Genre artwork drawn behind the title. */
    private function posterArtwork(string $genre, string $accent, string $dark, int $seed): string
    {
        mt_srand($seed + 1);
        $glow = '<circle cx="300" cy="360" r="260" fill="url(#glow)"/>';

        // A jagged silhouette along the bottom of the artwork (skyline, hills or peaks)
        $silhouette = function (int $baseY, int $minH, int $maxH, int $step, bool $flatTops) use ($dark) {
            $points = "0,900 0,$baseY";
            for ($x = 0; $x <= 600; $x += $step) {
                $y = $baseY - mt_rand($minH, $maxH);
                $points .= $flatTops ? " $x,$y " . ($x + $step) . ",$y" : " $x,$y";
            }
            return '<polygon points="' . $points . ' 600,' . $baseY . ' 600,900" fill="' . $dark . '"/>';
        };

        switch ($genre) {
            case 'Sci-Fi': // synthwave sun over a perspective grid
                $grid = '';
                for ($i = 0; $i <= 12; $i++) {
                    $x = -300 + $i * 100;
                    $grid .= '<line x1="300" y1="470" x2="' . $x . '" y2="900" stroke="' . $accent . '" stroke-opacity="0.45" stroke-width="2"/>';
                }
                foreach ([490, 520, 565, 630, 720, 840] as $y) {
                    $grid .= '<line x1="0" y1="' . $y . '" x2="600" y2="' . $y . '" stroke="' . $accent . '" stroke-opacity="0.35" stroke-width="2"/>';
                }
                $stripes = '';
                foreach ([380, 405, 428, 448] as $n => $y) {
                    $stripes .= '<rect x="150" y="' . $y . '" width="300" height="' . (5 + $n * 3) . '" fill="' . $dark . '"/>';
                }
                return $glow . '<circle cx="300" cy="380" r="150" fill="' . $accent . '"/>' . $stripes
                    . '<rect y="470" width="600" height="430" fill="' . $dark . '"/>' . $grid;

            case 'Drama': // moon over dark water with a lone ferry
                $ripples = '';
                for ($i = 0; $i < 9; $i++) {
                    $w = 160 - $i * 12;
                    $ripples .= '<rect x="' . (300 - $w / 2) . '" y="' . (520 + $i * 22) . '" width="' . $w . '" height="3" fill="' . $accent . '" fill-opacity="' . (0.6 - $i * 0.05) . '"/>';
                }
                return $glow . '<circle cx="300" cy="330" r="110" fill="' . $accent . '"/>'
                    . '<rect y="500" width="600" height="400" fill="' . $dark . '" fill-opacity="0.85"/>' . $ripples
                    . '<path d="M215 500 L385 500 L365 522 L235 522 Z M250 500 L250 482 L335 482 L335 500 Z M300 482 L300 462 L312 462 L312 482 Z" fill="' . $dark . '"/>';

            case 'Action': // storm clouds, lightning and rain
                $rain = '';
                for ($i = 0; $i < 70; $i++) {
                    $x = mt_rand(-100, 650);
                    $y = mt_rand(120, 800);
                    $rain .= '<line x1="' . $x . '" y1="' . $y . '" x2="' . ($x - 18) . '" y2="' . ($y + 46) . '" stroke="#ffffff" stroke-opacity="0.18" stroke-width="2"/>';
                }
                $clouds = '';
                foreach ([[120, 150, 130], [300, 120, 160], [480, 160, 130], [210, 210, 110], [420, 230, 120]] as [$cx, $cy, $r]) {
                    $clouds .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $dark . '" fill-opacity="0.75"/>';
                }
                return $glow . $clouds
                    . '<polygon points="330,230 250,430 305,430 262,600 380,380 322,380 372,230" fill="' . $accent . '"/>'
                    . $rain . $silhouette(640, 20, 70, 50, true);

            case 'Romance': // big moon behind a city skyline
                return $glow . '<circle cx="300" cy="360" r="170" fill="' . $accent . '" fill-opacity="0.9"/>'
                    . '<circle cx="300" cy="360" r="170" fill="none" stroke="#ffffff" stroke-opacity="0.35" stroke-width="3"/>'
                    . $silhouette(600, 30, 190, 38, true);

            case 'Horror': // a lone house with one lit window under a pale moon
                return '<circle cx="430" cy="230" r="80" fill="' . $accent . '" fill-opacity="0.18"/>'
                    . '<circle cx="430" cy="230" r="58" fill="#e7e5e4" fill-opacity="0.85"/>'
                    . $silhouette(620, 0, 25, 60, false)
                    . '<polygon points="170,620 170,430 300,330 430,430 430,620" fill="' . $dark . '"/>'
                    . '<polygon points="150,440 300,315 450,440 430,440 300,338 170,440" fill="' . $dark . '"/>'
                    . '<rect x="345" y="370" width="22" height="80" fill="' . $dark . '"/>'
                    . '<rect x="280" y="470" width="40" height="52" fill="' . $accent . '"/>'
                    . '<rect x="298" y="470" width="4" height="52" fill="' . $dark . '"/><rect x="280" y="494" width="40" height="4" fill="' . $dark . '"/>';

            case 'Fantasy': // castle on a hill under a crescent moon and stars
                $stars = '';
                for ($i = 0; $i < 45; $i++) {
                    $stars .= '<circle cx="' . mt_rand(10, 590) . '" cy="' . mt_rand(100, 460) . '" r="' . (mt_rand(8, 26) / 10) . '" fill="#ffffff" fill-opacity="' . (mt_rand(4, 10) / 10) . '"/>';
                }
                return $glow . $stars
                    . '<mask id="crescent"><circle cx="300" cy="300" r="95" fill="#fff"/><circle cx="338" cy="275" r="88" fill="#000"/></mask>'
                    . '<circle cx="300" cy="300" r="95" fill="' . $accent . '" mask="url(#crescent)"/>'
                    . '<ellipse cx="300" cy="700" rx="420" ry="170" fill="' . $dark . '"/>'
                    . '<path d="M200 560 L200 450 L186 450 L215 400 L244 450 L230 450 L230 500 L270 500 L270 400 L256 400 L300 320 L344 400 L330 400 L330 500 L370 500 L370 450 L356 450 L385 400 L414 450 L400 450 L400 560 Z" fill="' . $dark . '"/>'
                    . '<rect x="292" y="420" width="16" height="24" fill="' . $accent . '"/>';

            case 'Comedy': // spotlight burst behind a microphone
                $rays = '';
                for ($i = 0; $i < 18; $i++) {
                    $a1 = deg2rad($i * 20);
                    $a2 = deg2rad($i * 20 + 10);
                    $rays .= '<polygon points="300,380 ' . round(300 + 700 * cos($a1)) . ',' . round(380 + 700 * sin($a1)) . ' ' . round(300 + 700 * cos($a2)) . ',' . round(380 + 700 * sin($a2)) . '" fill="#ffffff" fill-opacity="0.18"/>';
                }
                return $rays . '<circle cx="300" cy="380" r="120" fill="#ffffff" fill-opacity="0.35"/>'
                    . '<rect x="270" y="300" width="60" height="100" rx="30" fill="' . $accent . '"/>'
                    . '<rect x="296" y="400" width="8" height="120" fill="' . $accent . '"/><rect x="255" y="515" width="90" height="10" rx="5" fill="' . $accent . '"/>';

            case 'Animation': // bubbles and a friendly robot
                $bubbles = '';
                for ($i = 0; $i < 16; $i++) {
                    $bubbles .= '<circle cx="' . mt_rand(0, 600) . '" cy="' . mt_rand(110, 620) . '" r="' . mt_rand(10, 46) . '" fill="#ffffff" fill-opacity="' . (mt_rand(8, 22) / 100) . '"/>';
                }
                return $bubbles
                    . '<line x1="300" y1="240" x2="300" y2="280" stroke="#ffffff" stroke-width="6"/><circle cx="300" cy="232" r="12" fill="' . $accent . '"/>'
                    . '<rect x="200" y="280" width="200" height="160" rx="36" fill="#ffffff"/>'
                    . '<circle cx="255" cy="355" r="24" fill="#1e1b4b"/><circle cx="345" cy="355" r="24" fill="#1e1b4b"/>'
                    . '<circle cx="262" cy="347" r="8" fill="#ffffff"/><circle cx="352" cy="347" r="8" fill="#ffffff"/>'
                    . '<path d="M265 400 Q300 425 335 400" stroke="#1e1b4b" stroke-width="7" fill="none" stroke-linecap="round"/>'
                    . '<rect x="230" y="450" width="140" height="110" rx="24" fill="#ffffff" fill-opacity="0.92"/>'
                    . '<circle cx="300" cy="505" r="20" fill="' . $accent . '"/>';

            case 'Thriller': // radio mast sending out rings
                $rings = '';
                for ($i = 1; $i <= 7; $i++) {
                    $rings .= '<circle cx="300" cy="300" r="' . ($i * 55) . '" fill="none" stroke="' . $accent . '" stroke-opacity="' . (0.55 - $i * 0.06) . '" stroke-width="3"/>';
                }
                return $rings . '<circle cx="300" cy="300" r="12" fill="#ef4444"/>'
                    . '<polygon points="300,300 250,640 350,640" fill="none" stroke="' . $accent . '" stroke-width="5"/>'
                    . '<path d="M288 380 L312 380 M280 440 L320 440 M272 500 L328 500 M264 560 L336 560 M288 380 L320 440 M312 380 L280 440 M280 440 L328 500 M320 440 L272 500 M272 500 L336 560 M328 500 L264 560" stroke="' . $accent . '" stroke-width="3"/>'
                    . $silhouette(660, 0, 20, 80, false);

            case 'Adventure': // snowy peaks at sunset
                return $glow . '<circle cx="300" cy="420" r="110" fill="' . $accent . '"/>'
                    . '<polygon points="-40,640 120,390 190,470 300,250 420,450 470,400 640,640" fill="' . $dark . '"/>'
                    . '<polygon points="300,250 262,318 285,305 300,330 318,300 340,318" fill="#ffffff" fill-opacity="0.9"/>'
                    . '<polygon points="120,390 98,425 118,415 130,432 142,410" fill="#ffffff" fill-opacity="0.85"/>'
                    . '<rect y="630" width="600" height="270" fill="' . $dark . '"/>';

            case 'Space': // ringed planet, stars and a rocket heading for the Moon
                $stars = '';
                for ($i = 0; $i < 60; $i++) {
                    $stars .= '<circle cx="' . mt_rand(5, 595) . '" cy="' . mt_rand(95, 640) . '" r="' . (mt_rand(6, 22) / 10) . '" fill="#ffffff" fill-opacity="' . (mt_rand(3, 10) / 10) . '"/>';
                }
                return $stars . $glow
                    . '<circle cx="300" cy="360" r="120" fill="' . $accent . '"/>'
                    . '<circle cx="265" cy="325" r="22" fill="' . $dark . '" fill-opacity="0.25"/><circle cx="335" cy="400" r="14" fill="' . $dark . '" fill-opacity="0.25"/>'
                    . '<ellipse cx="300" cy="360" rx="215" ry="42" fill="none" stroke="#ffffff" stroke-opacity="0.75" stroke-width="7" transform="rotate(-16 300 360)"/>'
                    . '<g transform="translate(470 175) rotate(35)"><path d="M0 -44 Q16 -22 16 14 L-16 14 Q-16 -22 0 -44 Z" fill="#ffffff"/>'
                    . '<circle cx="0" cy="-10" r="6" fill="' . $accent . '"/><path d="M-16 4 L-28 24 L-16 18 Z M16 4 L28 24 L16 18 Z" fill="#ffffff"/>'
                    . '<path d="M-9 16 L0 44 L9 16 Z" fill="#fbbf24"/></g>';

            case 'Basketball': // ball swishing through a hoop under stadium lights
                $rays = '';
                for ($i = 0; $i < 14; $i++) {
                    $a1 = deg2rad(200 + $i * 10);
                    $a2 = deg2rad(204 + $i * 10);
                    $rays .= '<polygon points="300,700 ' . round(300 + 900 * cos($a1)) . ',' . round(700 + 900 * sin($a1)) . ' ' . round(300 + 900 * cos($a2)) . ',' . round(700 + 900 * sin($a2)) . '" fill="#ffffff" fill-opacity="0.16"/>';
                }
                return $rays
                    . '<rect x="185" y="170" width="230" height="150" rx="6" fill="#ffffff" fill-opacity="0.9" stroke="' . $accent . '" stroke-width="5"/>'
                    . '<rect x="255" y="225" width="90" height="70" fill="none" stroke="' . $accent . '" stroke-width="5"/>'
                    . '<circle cx="300" cy="270" r="92" fill="#ea580c" stroke="' . $accent . '" stroke-width="5"/>'
                    . '<path d="M208 270 L392 270 M300 178 L300 362 M236 205 Q280 270 236 335 M364 205 Q320 270 364 335" stroke="' . $accent . '" stroke-width="5" fill="none"/>'
                    . '<ellipse cx="300" cy="352" rx="78" ry="12" fill="none" stroke="#dc2626" stroke-width="7"/>'
                    . '<path d="M226 356 L250 440 L270 358 L286 442 L300 360 L314 442 L330 358 L350 440 L374 356" stroke="#ffffff" stroke-width="3" fill="none"/>';

            default:
                return $glow . $silhouette(620, 20, 120, 40, false);
        }
    }

    /**
     * Now-showing movies get a screening tonight (or tomorrow, if it's too late) and
     * two a day for the next week; coming-soon movies get a week of screenings
     * starting on their release day. Every slot is checked against the hall's schedule.
     */
    private function scheduleShowtimes(Movies $movie, Halls $hall, ?int $startsInDays): void
    {
        $price = match ($movie->genre->name ?? null) {
            'Action', 'Adventure', 'Sci-Fi', 'Fantasy' => 420,
            'Animation' => 350,
            'Horror', 'Thriller' => 330,
            default => 300,
        };

        $firstDay = $startsInDays ?? 0;
        $created = [];

        for ($day = $firstDay; $day < $firstDay + 7; $day++) {
            foreach (['13:00', '19:00'] as $preferred) {
                $start = $this->nextFreeSlot($hall, Carbon::parse(now()->addDays($day)->toDateString() . ' ' . $preferred), $movie->duration);
                if ($start) {
                    $created[] = Showtimes::create([
                        'movie_id'   => $movie->id,
                        'hall_id'    => $hall->id,
                        'start_time' => $start,
                        'end_time'   => $start->copy()->addMinutes($movie->duration),
                        'price'      => $price,
                    ]);
                }
            }
        }

        // The home page's "Now Showing" / "Coming Soon" use show_time_id: point it at the first screening
        $movie->update(['show_time_id' => collect($created)->sortBy('start_time')->first()?->id]);
    }

    /**
     * First start time at or after $from (on the same day, not in the past, starting
     * no later than 23:00) where the hall is free for the whole screening.
     */
    private function nextFreeSlot(Halls $hall, Carbon $from, int $duration): ?Carbon
    {
        $earliest = now()->addMinutes(30);
        $start = ($from->lt($earliest) ? $earliest->copy() : $from->copy())->ceilMinutes(15); // round up to a quarter hour

        $lastStart = $from->copy()->setTime(23, 0);

        while ($start->lte($lastStart)) {
            $end = $start->copy()->addMinutes($duration);

            $clash = Showtimes::where('hall_id', $hall->id)
                ->where('start_time', '<', $end->copy()->addMinutes(self::TURNOVER_MINUTES))
                ->where('end_time', '>', $start->copy()->subMinutes(self::TURNOVER_MINUTES))
                ->orderByDesc('end_time')
                ->first();

            if (!$clash) {
                return $start;
            }

            // Try again right after the clashing screening. ceilMinutes() also accounts for
            // seconds, so the new start is never earlier than the clash's end + turnover.
            $start = $clash->end_time->copy()->addMinutes(self::TURNOVER_MINUTES)->ceilMinutes(15);
        }

        return null;
    }
}
