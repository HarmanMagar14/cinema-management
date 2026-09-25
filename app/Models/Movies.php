<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Movies extends Model
{
    protected $fillable = ['title', 'description', 'duration', 'release_date', 'show_time_id', 'genre_id', 'poster', 'trailer_url'];
    
    protected $casts = [
        'release_date' => 'date',
        'duration' => 'integer',
    ];

    public function showtime()
    {
        return $this->belongsTo(Showtimes::class, 'show_time_id');
    }

    /**
     * The next upcoming (not yet ended) showtime for this movie, ordered soonest first.
     */
    public function nextShowtime()
    {
        return $this->hasOne(Showtimes::class, 'movie_id')
            ->where('end_time', '>', now())
            ->orderBy('start_time');
    }

    public function genre()
    {
        return $this->belongsTo(Genres::class, 'genre_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtimes::class, 'movie_id');
    }

    public function reviews()
    {
        return $this->hasMany(Reviews::class, 'movie_id');
    }

    public function getPosterUrlAttribute(): string
    {
        if (empty($this->poster)) {
            return asset('images/no-poster.svg');
        }

        if (Str::startsWith($this->poster, ['http://', 'https://'])) {
            return $this->poster;
        }

        $normalizedPath = str_replace('\\', '/', $this->poster);
        $normalizedPath = preg_replace('#^(storage/app/public/|public/)#', '', $normalizedPath);
        $normalizedPath = preg_replace('#^storage/#', '', $normalizedPath);
        $normalizedPath = ltrim($normalizedPath, '/');

        if (Storage::disk('public')->exists($normalizedPath)) {
            return $this->publicStorageUrl($normalizedPath);
        }

        $fallbackCandidates = [
            'posters/' . $this->title . '.jpg',
            'posters/' . Str::slug($this->title) . '.jpg',
            'posters/' . Str::lower($this->title) . '.jpg',
        ];

        foreach ($fallbackCandidates as $candidatePath) {
            if (Storage::disk('public')->exists($candidatePath)) {
                return $this->publicStorageUrl($candidatePath);
            }
        }

        return asset('images/no-poster.svg');
    }

    public function getTrailerEmbedUrlAttribute(): string
    {
        if (!empty($this->trailer_url)) {
            $videoId = $this->extractYoutubeVideoId($this->trailer_url);
            if ($videoId) {
                return 'https://www.youtube.com/embed/' . $videoId;
            }
        }

        return 'https://www.youtube.com/embed?listType=search&list=' . urlencode($this->title . ' official trailer');
    }

    private function extractYoutubeVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        if (str_contains($host, 'youtu.be')) {
            $path = trim($parts['path'] ?? '', '/');
            return $path !== '' ? $path : null;
        }

        if (str_contains($host, 'youtube.com')) {
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
                if (!empty($query['v'])) {
                    return $query['v'];
                }
            }

            $path = $parts['path'] ?? '';
            if (str_starts_with($path, '/embed/')) {
                return trim(str_replace('/embed/', '', $path), '/');
            }
        }

        return null;
    }

    private function publicStorageUrl(string $path): string
    {
        $segments = array_map('rawurlencode', explode('/', ltrim($path, '/')));
        return '/storage/' . implode('/', $segments);
    }

    public $timestamps = false;
}
