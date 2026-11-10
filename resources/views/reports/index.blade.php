@extends('students.layout')

@section('title', 'Reports')

@section('content')
    <style>
        .report-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
        .report-heading h1 { font-size:1.5rem; font-weight:800; color:#111827; }
        .report-heading p { color:#6b7280; font-size:0.85rem; margin-top:0.3rem; }
        .report-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:1rem; margin-bottom:1.5rem; }
        .report-stat { background:#fff; border-radius:14px; padding:1.2rem 1.4rem; box-shadow:0 2px 10px rgba(0,0,0,.06); border-top:4px solid var(--report-color); }
        .report-stat span { display:block; color:#6b7280; font-size:.8rem; }
        .report-stat strong { display:block; color:#111827; font-size:1.7rem; margin-top:.4rem; }
        .report-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .report-card { background:#fff; border-radius:14px; padding:1.35rem 1.5rem; box-shadow:0 2px 10px rgba(0,0,0,.06); min-width:0; }
        .report-card h2 { font-size:1rem; color:#111827; margin-bottom:1.1rem; }
        .chart-row { display:grid; grid-template-columns:minmax(90px,140px) minmax(40px,1fr) 32px; align-items:center; gap:.7rem; margin:.85rem 0; }
        .chart-label { overflow-wrap:anywhere; color:#4b5563; font-size:.82rem; }
        .chart-track { height:12px; background:#eef2f7; border-radius:99px; overflow:hidden; }
        .chart-bar { height:100%; min-width:4px; border-radius:99px; background:var(--chart-color); }
        .chart-total { text-align:right; font-size:.82rem; font-weight:700; color:#111827; }
        .report-empty { color:#6b7280; font-size:.875rem; padding:.5rem 0; }
        @media (max-width:640px) {
            .report-grid { grid-template-columns:1fr; }
            .report-heading { align-items:flex-start; flex-direction:column; }
            .chart-row { grid-template-columns:minmax(85px,110px) minmax(30px,1fr) 28px; gap:.5rem; }
        }
        @media print {
            body { background:#fff !important; }
            .topbar, .report-actions, .alert { display:none !important; }
            .page { max-width:none !important; padding:0 !important; }
            .report-card, .report-stat { box-shadow:none; border:1px solid #d1d5db; break-inside:avoid; }
            .report-grid { gap:.6rem; }
        }
    </style>

    <div class="report-heading">
        <div>
            <h1>SIMS Summary Report</h1>
            <p>Visual overview of students, subjects, and enrollments · Generated {{ $generatedAt->format('M d, Y g:i A') }}</p>
        </div>
        <div class="report-actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        </div>
    </div>

    <div class="report-summary">
        <div class="report-stat" style="--report-color:#2563eb"><span>Total Students</span><strong>{{ number_format($totalStudents) }}</strong></div>
        <div class="report-stat" style="--report-color:#16a34a"><span>Total Subjects</span><strong>{{ number_format($totalSubjects) }}</strong></div>
        <div class="report-stat" style="--report-color:#d97706"><span>Total Enrollments</span><strong>{{ number_format($totalEnrollments) }}</strong></div>
        <div class="report-stat" style="--report-color:#9333ea"><span>Total Subject Units</span><strong>{{ number_format($totalUnits) }}</strong></div>
    </div>

    <div class="report-grid">
        <section class="report-card" aria-labelledby="course-report-title">
            <h2 id="course-report-title">Students by Course</h2>
            @forelse ($studentsByCourse as $row)
                <div class="chart-row">
                    <span class="chart-label">{{ $row['label'] }}</span>
                    <div class="chart-track"><div class="chart-bar" style="--chart-color:#2563eb;width:{{ $row['width'] }}%"></div></div>
                    <span class="chart-total">{{ $row['total'] }}</span>
                </div>
            @empty
                <p class="report-empty">No student records available yet.</p>
            @endforelse
        </section>

        <section class="report-card" aria-labelledby="year-report-title">
            <h2 id="year-report-title">Students by Year Level</h2>
            @forelse ($studentsByYearLevel as $row)
                <div class="chart-row">
                    <span class="chart-label">{{ $row['label'] }}</span>
                    <div class="chart-track"><div class="chart-bar" style="--chart-color:#0ea5e9;width:{{ $row['width'] }}%"></div></div>
                    <span class="chart-total">{{ $row['total'] }}</span>
                </div>
            @empty
                <p class="report-empty">No student records available yet.</p>
            @endforelse
        </section>

        <section class="report-card" aria-labelledby="term-report-title">
            <h2 id="term-report-title">Enrollments by School Year and Semester</h2>
            @forelse ($enrollmentsByTerm as $row)
                <div class="chart-row">
                    <span class="chart-label">{{ $row['label'] }}</span>
                    <div class="chart-track"><div class="chart-bar" style="--chart-color:#d97706;width:{{ $row['width'] }}%"></div></div>
                    <span class="chart-total">{{ $row['total'] }}</span>
                </div>
            @empty
                <p class="report-empty">No enrollment records available yet.</p>
            @endforelse
        </section>

        <section class="report-card" aria-labelledby="status-report-title">
            <h2 id="status-report-title">Enrollments by Status</h2>
            @forelse ($enrollmentsByStatus as $row)
                <div class="chart-row">
                    <span class="chart-label">{{ $row['label'] }}</span>
                    <div class="chart-track"><div class="chart-bar" style="--chart-color:#9333ea;width:{{ $row['width'] }}%"></div></div>
                    <span class="chart-total">{{ $row['total'] }}</span>
                </div>
            @empty
                <p class="report-empty">No enrollment records available yet.</p>
            @endforelse
        </section>
    </div>
@endsection
