@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <p>Overview of your cinema system performance.</p>
</div>

{{-- STAT CARDS --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-red">
            <div class="stat-icon red"><i class="bi bi-people"></i></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value">{{ $totalUsers }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-green">
            <div class="stat-icon green"><i class="bi bi-currency-dollar"></i></div>
            <div class="stat-label">Revenue</div>
            <div class="stat-value">₱{{ number_format($revenue, 0) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-blue">
            <div class="stat-icon blue"><i class="bi bi-activity"></i></div>
            <div class="stat-label">Active Sessions</div>
            <div class="stat-value">{{ $activeSessions }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-gold">
            <div class="stat-icon gold"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-label">Error Rate</div>
            <div class="stat-value">{{ $errorRate }}%</div>
        </div>
    </div>
</div>

{{-- CHARTS ROW --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Revenue Trend</h5>
                <span style="font-size:0.75rem; color:var(--muted);">Last 7 days</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="280"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Booking Status</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- RECENT ACTIVITY + TOP MOVIES --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Recent Activity</h5></div>
            <div class="card-body p-0">
                @forelse($recentActivity as $activity)
                    <div class="d-flex align-items-center gap-3 px-4 py-3" style="border-bottom: 1px solid var(--border);">
                        <div style="width:36px;height:36px;border-radius:50%;background:rgba(232,52,10,0.1);border:1px solid rgba(232,52,10,0.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi {{ $activity['icon'] }}" style="color:var(--accent2);"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div style="font-weight:600;font-size:0.88rem;">{{ $activity['title'] }}</div>
                            <div style="color:var(--muted);font-size:0.78rem;">{{ $activity['description'] }}</div>
                        </div>
                        <small style="color:var(--muted);font-size:0.75rem;white-space:nowrap;">{{ $activity['time'] }}</small>
                    </div>
                @empty
                    <div class="text-center py-5" style="color:var(--muted);">
                        <i class="bi bi-clock-history" style="font-size:2rem;opacity:0.3;"></i>
                        <p class="mt-2 mb-0">No recent activity</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Top Movies</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Movie</th>
                            <th>Tickets Sold</th>
                            <th class="text-end pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topMovies as $movie)
                            <tr>
                                <td class="ps-4"><strong>{{ $movie->title }}</strong></td>
                                <td>{{ number_format($movie->bookings_count) }}</td>
                                <td class="text-end pe-4">
                                    @if($movie->bookings_count > 10)
                                        <span class="badge bg-success">Popular</span>
                                    @else
                                        <span class="badge bg-warning">Trending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4" style="color:var(--muted);">No movies found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- QUICK ACTIONS --}}
<div class="card">
    <div class="card-header"><h5 class="mb-0">Quick Actions</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary w-100">
                    <i class="bi bi-person-plus me-2"></i>Create User
                </a>
            </div>
            <div class="col-sm-6 col-md-3">
                <a href="{{ route('admin.movies.create') }}" class="btn btn-primary w-100">
                    <i class="bi bi-film me-2"></i>Add Movie
                </a>
            </div>
            <div class="col-sm-6 col-md-3">
                <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-ticket-perforated me-2"></i>View Bookings
                </a>
            </div>
            <div class="col-sm-6 col-md-3">
                <a href="{{ route('admin.analytics') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-graph-up me-2"></i>Analytics
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const gridColor = 'rgba(240,239,244,0.06)';
    const tickColor = 'rgba(240,239,244,0.4)';

    // Revenue Chart
    new Chart(document.getElementById('revenueChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Revenue',
                data: [12000, 19000, 3000, 5000, 2000, 3000, 15000],
                borderColor: '#e8340a',
                backgroundColor: 'rgba(232,52,10,0.08)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#e8340a',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: tickColor }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: tickColor }
                }
            }
        }
    });

    // Booking Status Doughnut
    const bookingData = @json($bookingStatus);
    new Chart(document.getElementById('trafficChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Confirmed', 'Pending', 'Canceled'],
            datasets: [{
                data: [bookingData.confirmed, bookingData.pending, bookingData.canceled],
                backgroundColor: ['rgba(34,197,94,0.8)', 'rgba(245,197,24,0.8)', 'rgba(220,53,69,0.8)'],
                borderColor: ['#22c55e', '#f5c518', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: tickColor, padding: 12, font: { size: 12 } }
                }
            }
        }
    });
});
</script>
@endsection
