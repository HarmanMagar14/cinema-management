@extends('admin.layouts.app')
@section('title', 'Schedule — ' . $movie->title)

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.movies.index') }}">Movies</a></li>
        <li class="breadcrumb-item active">Scheduling</li>
    </ol>
</nav>
@endsection

@section('content')

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h1>Scheduling</h1>
        <p>Step 2 of 2 — Add showtimes for <strong style="color:var(--text);">{{ $movie->title }}</strong></p>
    </div>
    <a href="{{ route('admin.movies.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Movies
    </a>
</div>

{{-- Step indicator --}}
<div class="d-flex mb-4" style="gap:0;">
    <div class="d-flex align-items-center gap-2 px-4 py-2"
         style="background:rgba(25,135,84,0.1);border:1px solid rgba(25,135,84,0.35);border-radius:0.5rem 0 0 0.5rem;">
        <span class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
              style="width:22px;height:22px;font-size:0.7rem;background:#198754;">
            <i class="bi bi-check" style="font-size:0.7rem;"></i>
        </span>
        <span class="fw-semibold" style="color:#198754;font-size:0.9rem;">Movie Details</span>
    </div>
    <div class="d-flex align-items-center gap-2 px-4 py-2"
         style="background:rgba(232,52,10,0.12);border:1px solid rgba(232,52,10,0.35);border-left:none;border-radius:0 0.5rem 0.5rem 0;">
        <span class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
              style="width:22px;height:22px;font-size:0.7rem;background:var(--accent);">2</span>
        <span class="fw-semibold" style="color:var(--text);font-size:0.9rem;">Scheduling</span>
    </div>
</div>

{{-- Movie info bar --}}
<div class="card mb-4">
    <div class="card-body d-flex align-items-center gap-3 py-3">
        @if($movie->poster_url)
        <img src="{{ $movie->poster_url }}" alt="{{ $movie->title }}"
             style="width:44px;height:60px;object-fit:cover;border-radius:0.4rem;border:1px solid var(--border);flex-shrink:0;">
        @endif
        <div class="flex-grow-1">
            <div class="fw-semibold" style="color:var(--text);">{{ $movie->title }}</div>
            <div class="d-flex flex-wrap gap-3 mt-1" style="font-size:0.8rem;color:var(--muted);">
                <span><i class="bi bi-tag-fill me-1"></i>{{ $movie->genre->name ?? 'N/A' }}</span>
                <span><i class="bi bi-clock me-1"></i>{{ $movie->duration }} mins</span>
                <span><i class="bi bi-calendar me-1"></i>{{ optional($movie->release_date)->format('M j, Y') }}</span>
            </div>
        </div>
        <a href="{{ route('admin.movies.edit', $movie) }}"
           class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="bi bi-pencil me-1"></i>Edit Details
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-4">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Please fix the following errors:
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('admin.movies.showtimes.store', $movie) }}" method="POST">
    @csrf

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">
                <i class="bi bi-calendar-event me-2" style="color:var(--accent);"></i>
                Showtimes
                <span class="ms-2" style="font-weight:400;font-size:0.8rem;color:var(--muted);">
                    Duration: <strong style="color:var(--text);">{{ $movie->duration }} mins</strong> per screening
                </span>
            </h5>
            <button type="button" class="btn btn-sm btn-primary" id="add-showtime">
                <i class="bi bi-plus-lg me-1"></i>Add Showtime
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="showtimes-table">
                    <thead>
                        <tr>
                            <th style="width:24%;">Cinema</th>
                            <th style="width:18%;">Hall</th>
                            <th style="width:20%;">Start Time</th>
                            <th style="width:8%;">End Time</th>
                            <th style="width:10%;">Price (₱)</th>
                            <th style="width:12%;">Hall Schedule</th>
                            <th style="width:8%;text-align:center;">Action</th>
                        </tr>
                    </thead>
                    @php
                        // After a failed save, redisplay exactly what was submitted (including
                        // rows that were added but not saved yet); otherwise show saved showtimes.
                        $savedShowtimes = $movie->showtimes->keyBy('id');
                        $hallsById = $cinemas->flatMap->halls->keyBy('id');
                        $rows = old('showtimes') !== null
                            ? collect(old('showtimes'))
                            : $movie->showtimes->values()->map(fn ($st) => [
                                'id'         => $st->id,
                                'cinema_id'  => $st->hall->cinema_id,
                                'hall_id'    => $st->hall_id,
                                'start_time' => $st->start_time->format('Y-m-d\TH:i'),
                                'price'      => $st->price,
                            ]);
                        $nextRowIndex = $rows->isEmpty() ? 0 : $rows->keys()->max() + 1;

                        // Booked showtimes can't be removed, so put back any the submission left out
                        $shownIds = $rows->pluck('id')->filter()->map(fn ($id) => (int) $id);
                        foreach ($movie->showtimes as $st) {
                            if ($st->active_bookings_count > 0 && !$shownIds->contains($st->id)) {
                                $rows->put($nextRowIndex++, [
                                    'id'         => $st->id,
                                    'cinema_id'  => $st->hall->cinema_id,
                                    'hall_id'    => $st->hall_id,
                                    'start_time' => $st->start_time->format('Y-m-d\TH:i'),
                                    'price'      => $st->price,
                                ]);
                            }
                        }
                    @endphp
                    <tbody id="showtimes-body">
                        @foreach($rows as $index => $row)
                        @php
                            $saved       = !empty($row['id']) ? $savedShowtimes->get((int) $row['id']) : null;
                            $bookedCount = $saved->active_bookings_count ?? 0;
                            $rowCinemaId = $row['cinema_id'] ?? optional($hallsById->get($row['hall_id'] ?? null))->cinema_id;
                            $rowHalls    = optional($cinemas->firstWhere('id', $rowCinemaId))->halls ?? collect();
                        @endphp
                        <tr class="showtime-row" data-booked="{{ $bookedCount }}">
                            <td>
                                <input type="hidden" name="showtimes[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                @if($bookedCount > 0)
                                    {{-- Booked seats belong to this hall, so the cinema/hall is locked --}}
                                    <input type="hidden" name="showtimes[{{ $index }}][cinema_id]" value="{{ $saved->hall->cinema_id }}">
                                    <div style="font-size:0.85rem;color:var(--text);">{{ optional($cinemas->firstWhere('id', $saved->hall->cinema_id))->name }}</div>
                                @else
                                    <select name="showtimes[{{ $index }}][cinema_id]"
                                            class="form-select form-select-sm row-cinema-select" required>
                                        <option value="">Select Cinema</option>
                                        @foreach($cinemas as $cinema)
                                        <option value="{{ $cinema->id }}" @selected($rowCinemaId == $cinema->id)>
                                            {{ $cinema->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                @if($bookedCount > 0)
                                    <input type="hidden" name="showtimes[{{ $index }}][hall_id]" class="row-hall-select" value="{{ $saved->hall_id }}">
                                    <div style="font-size:0.85rem;color:var(--text);">{{ $saved->hall->name }}</div>
                                    <span class="badge bg-warning text-dark mt-1" title="Showtimes with bookings can't be moved or removed">
                                        <i class="bi bi-lock-fill"></i> {{ $bookedCount }} {{ Str::plural('booking', $bookedCount) }}
                                    </span>
                                @else
                                    <select name="showtimes[{{ $index }}][hall_id]"
                                            class="form-select form-select-sm row-hall-select" required>
                                        @if($rowHalls->isEmpty())
                                            <option value="">Select Cinema First</option>
                                        @else
                                            <option value="">Select Hall</option>
                                            @foreach($rowHalls as $hall)
                                            <option value="{{ $hall->id }}" @selected(($row['hall_id'] ?? null) == $hall->id)>{{ $hall->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                @endif
                                @error('showtimes.' . $index . '.hall_id')
                                <div class="text-danger" style="font-size:0.75rem;margin-top:2px;">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </div>
                                @enderror
                            </td>
                            <td>
                                <input type="datetime-local"
                                       name="showtimes[{{ $index }}][start_time]"
                                       class="form-control form-control-sm showtime-dt"
                                       value="{{ $row['start_time'] ?? '' }}" required>
                                @error('showtimes.' . $index . '.start_time')
                                <div class="text-danger" style="font-size:0.75rem;margin-top:2px;">
                                    <i class="bi bi-exclamation-circle"></i> {{ $message }}
                                </div>
                                @enderror
                            </td>
                            <td>
                                <span class="end-time-label" style="font-size:0.8rem;color:var(--muted);">—</span>
                            </td>
                            <td>
                                <input type="number"
                                       name="showtimes[{{ $index }}][price]"
                                       class="form-control form-control-sm"
                                       value="{{ $row['price'] ?? '' }}"
                                       step="0.01" min="0" required>
                            </td>
                            <td>
                                <div class="avail-panel" style="font-size:0.72rem;color:var(--muted);min-height:28px;">
                                    <span class="avail-text">—</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($bookedCount > 0)
                                    <span title="Has bookings — cancel them before removing this showtime" style="color:var(--muted);">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div id="no-showtimes-msg"
                     class="text-center py-4"
                     style="color:var(--muted);font-size:0.9rem;{{ $rows->isNotEmpty() ? 'display:none;' : '' }}">
                    <i class="bi bi-calendar-x me-2"></i>No showtimes yet. Click "+ Add Showtime" to begin.
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-4 pb-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-2"></i>Save Showtimes
        </button>
        <a href="{{ route('admin.movies.index') }}" class="btn btn-outline-secondary">Skip for now</a>
    </div>

</form>
@endsection

@push('scripts')
<script>
const DURATION      = {{ $movie->duration }};
const CINEMAS       = @json($cinemas);
const ALL_SHOWTIMES = @json($allShowtimes);
const showtimesBody = document.getElementById('showtimes-body');
const addBtn        = document.getElementById('add-showtime');
const noMsg         = document.getElementById('no-showtimes-msg');
let rowCount        = {{ $nextRowIndex }};

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function toggleNoMsg() {
    noMsg.style.display = showtimesBody.children.length === 0 ? 'block' : 'none';
}

// Wire existing rows on page load
document.querySelectorAll('.showtime-row').forEach(row => {
    wireRow(row);
    updateEndTime(row);
    refreshAvailability(row);
});

// Add new row
addBtn.addEventListener('click', () => {
    const index = rowCount++;
    const tr = document.createElement('tr');
    tr.className = 'showtime-row';

    let cinemaOpts = '<option value="">Select Cinema</option>';
    CINEMAS.forEach(c => cinemaOpts += `<option value="${c.id}">${escapeHtml(c.name)}</option>`);

    tr.innerHTML = `
        <td>
            <input type="hidden" name="showtimes[${index}][id]" value="">
            <select name="showtimes[${index}][cinema_id]" class="form-select form-select-sm row-cinema-select" required>
                ${cinemaOpts}
            </select>
        </td>
        <td>
            <select name="showtimes[${index}][hall_id]" class="form-select form-select-sm row-hall-select" required>
                <option value="">Select Cinema First</option>
            </select>
        </td>
        <td>
            <input type="datetime-local" name="showtimes[${index}][start_time]"
                   class="form-control form-control-sm showtime-dt" required>
        </td>
        <td>
            <span class="end-time-label" style="font-size:0.8rem;color:var(--muted);">—</span>
        </td>
        <td>
            <input type="number" name="showtimes[${index}][price]"
                   class="form-control form-control-sm"
                   step="0.01" min="0" placeholder="0.00" required>
        </td>
        <td>
            <div class="avail-panel" style="font-size:0.72rem;color:var(--muted);min-height:28px;">
                <span class="avail-text">Select a hall and time</span>
            </div>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;

    showtimesBody.appendChild(tr);
    wireRow(tr);
    toggleNoMsg();
});

function wireRow(row) {
    const cinemaSelect = row.querySelector('.row-cinema-select');
    const hallSelect   = row.querySelector('.row-hall-select');
    const dtInput      = row.querySelector('.showtime-dt');

    // Booked rows have their cinema/hall locked, so there's no select to wire
    if (cinemaSelect) {
        cinemaSelect.addEventListener('change', function () {
            fillHalls(this.value, hallSelect);
            refreshAvailability(row);
        });
        hallSelect.addEventListener('change', () => refreshAvailability(row));
    }

    dtInput.addEventListener('change', () => {
        updateEndTime(row);
        refreshAvailability(row);
    });

    row.querySelector('.remove-row')?.addEventListener('click', () => {
        row.remove();
        toggleNoMsg();
    });
}

// Halls come with the cinemas already on the page, so no extra request is needed
function fillHalls(cinemaId, selectEl) {
    const cinema = CINEMAS.find(c => String(c.id) === String(cinemaId));
    if (!cinema) {
        selectEl.innerHTML = '<option value="">Select Cinema First</option>';
        return;
    }
    if (!cinema.halls.length) {
        selectEl.innerHTML = '<option value="">No halls in this cinema</option>';
        return;
    }
    selectEl.innerHTML = '<option value="">Select Hall</option>'
        + cinema.halls.map(h => `<option value="${h.id}">${escapeHtml(h.name)}</option>`).join('');
}

function updateEndTime(row) {
    const dt  = row.querySelector('.showtime-dt');
    const lbl = row.querySelector('.end-time-label');
    if (!dt || !dt.value || !lbl) return;
    const end = new Date(new Date(dt.value).getTime() + DURATION * 60000);
    lbl.textContent = end.toTimeString().slice(0, 5);
}

function refreshAvailability(row) {
    const hallId = parseInt(row.querySelector('.row-hall-select')?.value);
    const dtVal  = row.querySelector('.showtime-dt')?.value;
    const panel  = row.querySelector('.avail-panel');
    if (!panel) return;

    if (!hallId || !dtVal) {
        panel.innerHTML = '<span style="color:var(--muted);">Select a hall and time</span>';
        return;
    }

    const datePart = dtVal.split('T')[0];
    const slots    = ALL_SHOWTIMES.filter(s => s.hall_id === hallId && s.start_time.startsWith(datePart));
    panel.innerHTML = renderAvailability(slots, dtVal);
}

function renderAvailability(slots, currentDt) {
    if (!slots.length) {
        return '<span style="color:#198754;font-weight:600;"><i class="bi bi-check-circle-fill"></i> Free all day</span>';
    }
    const newStart = Math.floor(new Date(currentDt).getTime() / 1000);
    const newEnd   = newStart + DURATION * 60;
    let html = '<div style="font-weight:600;color:var(--muted);margin-bottom:2px;">Booked:</div>';
    slots.forEach(s => {
        const overlaps = newStart < s.end_ts && newEnd > s.start_ts;
        const color    = overlaps ? '#dc3545' : 'var(--muted)';
        const icon     = overlaps ? '⚠ ' : '· ';
        html += `<div style="color:${color};line-height:1.5;">${icon}${s.start_time.slice(11,16)}–${s.end_time.slice(11,16)} <span style="opacity:.6;">${escapeHtml(s.movie)}</span></div>`;
    });
    return html;
}
</script>
@endpush
