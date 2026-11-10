@extends('students.layout')

@section('title', 'Curriculum Details')
@section('page-class', 'page-landscape')

@section('content')
    <style>
        .curriculum-landscape { --ink:#172554;--muted:#64748b;--line:#e2e8f0; }
        .curriculum-heading { display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.35rem;margin-bottom:1.2rem;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 2px 10px rgba(15,23,42,.04); }
        .curriculum-title { min-width:0; }
        .curriculum-eyebrow { margin-bottom:.35rem;color:#2563eb;font-size:.68rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase; }
        .curriculum-heading h2 { color:var(--ink);font-size:clamp(1.15rem,2vw,1.55rem);font-weight:800;line-height:1.25; }
        .curriculum-heading p { margin-top:.4rem;color:var(--muted);font-size:.77rem;overflow-wrap:anywhere; }
        .curriculum-actions { display:flex;flex:none;gap:.5rem; }
        .curriculum-actions .btn { white-space:nowrap; }
        .year-board { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;align-items:start; }
        .year-lane { min-width:0;overflow:hidden;background:#eaf0fb;border:1px solid #dce5f3;border-radius:13px; }
        .year-heading { display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.8rem .85rem;color:#fff;background:linear-gradient(110deg,#1e3a8a,#2563eb); }
        .year-heading h3 { font-size:.88rem;font-weight:800; }
        .year-heading span { flex:none;padding:.25rem .45rem;border:1px solid rgba(255,255,255,.25);border-radius:999px;background:rgba(255,255,255,.12);font-size:.66rem;font-weight:700; }
        .semester-list { display:grid;gap:.65rem;padding:.65rem; }
        .semester-panel { min-width:0;overflow:hidden;background:#fff;border:1px solid #e1e8f2;border-radius:9px; }
        .semester-heading { display:flex;align-items:center;justify-content:space-between;gap:.4rem;padding:.6rem .65rem;color:#1e3a8a;background:#f1f6ff;border-bottom:1px solid #e3ebf7;font-size:.72rem;font-weight:800; }
        .semester-heading span { color:#64748b;font-size:.64rem;font-weight:700;white-space:nowrap; }
        .course-list { display:grid; }
        .course-row { min-width:0;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.2rem .45rem;padding:.52rem .6rem;border-bottom:1px solid #edf1f6; }
        .course-row:last-child { border-bottom:0; }
        .course-title { min-width:0;color:#1f2937;font-size:.7rem;font-weight:650;line-height:1.3;overflow-wrap:anywhere; }
        .course-code { display:block;margin-bottom:.12rem;color:#1d4ed8;font-size:.65rem;font-weight:800;letter-spacing:.02em; }
        .course-units { align-self:start;min-width:2.45rem;padding:.18rem .3rem;border-radius:6px;color:#1e3a8a;background:#eff6ff;font-size:.64rem;font-weight:800;text-align:center;white-space:nowrap; }
        .course-meta { grid-column:1/-1;color:#64748b;font-size:.62rem;line-height:1.35;overflow-wrap:anywhere; }
        .course-group { color:#475569;font-weight:700; }
        .year-total { padding:.6rem .75rem;border-top:1px solid #dce5f3;color:#334155;background:#f8fafc;font-size:.7rem;font-weight:800;text-align:right; }
        .elective-board { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem;margin-top:1rem; }
        .elective-lane .year-heading { background:linear-gradient(110deg,#0e7490,#0891b2); }
        .curriculum-empty { padding:2rem 1rem;text-align:center;color:#64748b;background:#fff;border:1px solid var(--line);border-radius:14px; }
        @media(max-width:1150px) { .year-board { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media(max-width:680px) {
            .curriculum-heading { align-items:flex-start;flex-direction:column;padding:1rem; }
            .curriculum-actions { flex-wrap:wrap; }
            .year-board,.elective-board { grid-template-columns:1fr; }
        }
        @media print {
            @page { size:landscape; margin:8mm; }
            body { min-width:0!important;background:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact; }
            .sidebar,.workspace-header,.curriculum-actions { display:none!important; }
            .workspace { margin:0!important; }
            .page-landscape { width:100%!important;max-width:none!important;padding:0!important;margin:0!important; }
            .curriculum-heading { padding:.4rem .65rem;margin-bottom:.55rem;box-shadow:none;border-radius:6px; }
            .curriculum-heading h2 { font-size:14pt; }
            .curriculum-heading p { margin-top:.15rem;font-size:7pt; }
            .year-board { grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:4mm; }
            .elective-board { grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:4mm;margin-top:4mm; }
            .year-lane,.semester-panel { break-inside:avoid;box-shadow:none; }
            .year-heading { padding:5pt 6pt; }
            .year-heading h3 { font-size:8pt; }
            .year-heading span,.semester-heading span { font-size:6pt; }
            .semester-list { gap:3pt;padding:3pt; }
            .semester-heading { padding:4pt 5pt;font-size:7pt; }
            .course-row { gap:1pt 3pt;padding:3pt 4pt; }
            .course-title { font-size:6.5pt; }
            .course-code,.course-units { font-size:6pt; }
            .course-meta { font-size:5.5pt; }
            .year-total { padding:4pt 5pt;font-size:6.5pt; }
        }
    </style>

    @php
        $yearLanes = $subjects->except('Electives');
        $electiveSemesters = $subjects->get('Electives', collect());
    @endphp

    <div class="curriculum-landscape">
        <header class="curriculum-heading">
            <div class="curriculum-title">
                <div class="curriculum-eyebrow">Program of study · {{ $curriculum->name }}</div>
                <h2>{{ $curriculum->program }}</h2>
                <p>Imported {{ $curriculum->created_at->format('M j, Y') }} from {{ $curriculum->source_filename }}</p>
            </div>
            <div class="curriculum-actions">
                <button type="button" class="btn btn-primary" onclick="window.print()">Print landscape</button>
                <a href="{{ route('curricula.index') }}" class="btn btn-secondary">Back to Curricula</a>
            </div>
        </header>

        @if ($yearLanes->isEmpty() && $electiveSemesters->isEmpty())
            <div class="curriculum-empty">This curriculum has no subject rows.</div>
        @else
            @if ($yearLanes->isNotEmpty())
                <div class="year-board">
                    @foreach ($yearLanes as $yearLevel => $semesters)
                        @php($yearSubjects = $semesters->flatten(1))
                        <section class="year-lane">
                            <header class="year-heading">
                                <h3>{{ $yearLevel }}</h3>
                                <span>{{ $yearSubjects->sum(fn ($subject) => (int) ($subject->pivot->units ?? $subject->units)) }} units</span>
                            </header>
                            <div class="semester-list">
                                @foreach ($semesters as $semester => $termSubjects)
                                    <section class="semester-panel">
                                        <header class="semester-heading">
                                            <strong>{{ $semester }}</strong>
                                            <span>{{ $termSubjects->sum(fn ($subject) => (int) ($subject->pivot->units ?? $subject->units)) }} units</span>
                                        </header>
                                        <div class="course-list">
                                            @foreach ($termSubjects as $subject)
                                                <article class="course-row">
                                                    <div class="course-title">
                                                        <span class="course-code">{{ $subject->pivot->curriculum_code ?: $subject->code }}</span>
                                                        {{ $subject->pivot->subject_name ?: $subject->name }}
                                                    </div>
                                                    <span class="course-units">{{ $subject->pivot->units ?? $subject->units }} u</span>
                                                    @if ($subject->pivot->course_group || $subject->pivot->lecture_units !== null || $subject->pivot->contact_hours !== null || $subject->pivot->prerequisites || $subject->pivot->co_requisites)
                                                        <div class="course-meta">
                                                            @if ($subject->pivot->course_group)
                                                                <span class="course-group">{{ $subject->pivot->course_group }}</span>
                                                            @endif
                                                            @if ($subject->pivot->lecture_units !== null || $subject->pivot->lab_units !== null)
                                                                · L {{ $subject->pivot->lecture_units ?? 0 }} / Lab {{ $subject->pivot->lab_units ?? 0 }} units
                                                            @endif
                                                            @if ($subject->pivot->contact_hours !== null)
                                                                · {{ $subject->pivot->contact_hours }} hrs/wk
                                                            @endif
                                                            @if ($subject->pivot->prerequisites && $subject->pivot->prerequisites !== 'None')
                                                                · Pre: {{ $subject->pivot->prerequisites }}
                                                            @endif
                                                            @if ($subject->pivot->co_requisites && $subject->pivot->co_requisites !== 'None')
                                                                · Co: {{ $subject->pivot->co_requisites }}
                                                            @endif
                                                        </div>
                                                    @endif
                                                </article>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif

            @if ($electiveSemesters->isNotEmpty())
                <div class="elective-board">
                    @foreach ($electiveSemesters as $electiveType => $termSubjects)
                        <section class="year-lane elective-lane">
                            <header class="year-heading">
                                <h3>{{ $electiveType }} Elective Options</h3>
                                <span>{{ $termSubjects->sum(fn ($subject) => (int) ($subject->pivot->units ?? $subject->units)) }} option units</span>
                            </header>
                            <div class="course-list">
                                @foreach ($termSubjects as $subject)
                                    <article class="course-row">
                                        <div class="course-title">
                                            <span class="course-code">{{ $subject->pivot->curriculum_code ?: $subject->code }}</span>
                                            {{ $subject->pivot->subject_name ?: $subject->name }}
                                        </div>
                                        <span class="course-units">{{ $subject->pivot->units ?? $subject->units }} u</span>
                                        @if ($subject->pivot->prerequisites && $subject->pivot->prerequisites !== 'None')
                                            <div class="course-meta">Pre: {{ $subject->pivot->prerequisites }}</div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection
