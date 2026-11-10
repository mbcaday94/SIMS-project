<aside class="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zm0 2.18L19.5 9 12 12.82 4.5 9 12 5.18zM5 13.18V17l7 4 7-4v-3.82L12 17l-7-3.82z"/></svg>
        <span>SIMS</span>
    </a>

    <div class="sidebar-section-label">SIMS workspace</div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('students.index') }}" class="sidebar-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M16 4.5a4 4 0 0 1 0 7.5M19 14a6 6 0 0 1 3 5v2"/></svg>
            <span>Admission</span>
        </a>
        <a href="{{ route('subjects.index') }}" class="sidebar-link {{ request()->routeIs('subjects.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
            <span>Subjects</span>
        </a>
        <a href="{{ route('enrollments.index') }}" class="sidebar-link {{ request()->routeIs('enrollments.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
            <span>Enrollments</span>
        </a>
        <a href="{{ route('teaching-loads.index') }}" class="sidebar-link {{ request()->routeIs('teaching-loads.*', 'faculties.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h3M8 17h7"/></svg>
            <span>Teaching Loads</span>
        </a>
        <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18M18 17V9M13 17V5M8 17v-3"/></svg>
            <span>Reports</span>
        </a>
        @if (auth()->user()->isAdministrator())
            <a href="{{ route('curricula.index') }}" class="sidebar-link {{ request()->routeIs('curricula.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><path d="M8 7h8M8 11h8"/></svg>
                <span>Curricula</span>
            </a>
        @endif
    </nav>

    <div class="sidebar-user">
        <div class="sidebar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        <div class="sidebar-user-copy">
            <strong>{{ auth()->user()->name }}</strong>
            <span>{{ ucfirst(auth()->user()->role) }}</span>
        </div>
    </div>
    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
        @csrf
        <button type="submit" class="sidebar-logout">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
            <span>Sign out</span>
        </button>
    </form>
</aside>
