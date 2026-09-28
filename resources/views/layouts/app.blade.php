<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Taqreer - Sistem Laporan Bulanan Santri' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        try {
            if (localStorage.getItem('taqreer.sidebar.pinned') === '0') {
                document.documentElement.classList.add('sidebar-unpinned');
            }
        } catch (e) {}

        function sidebarLayout() {
            return {
                pinned: !document.documentElement.classList.contains('sidebar-unpinned'),
                peeking: false,
                mobileOpen: false,
                hideTimer: null,
                desktopQuery: window.matchMedia('(min-width: 1024px)'),
                get isOpen() {
                    return this.peeking || this.mobileOpen;
                },
                init() {
                    this.$watch('pinned', (value) => {
                        document.documentElement.classList.toggle('sidebar-unpinned', !value);
                        try {
                            localStorage.setItem('taqreer.sidebar.pinned', value ? '1' : '0');
                        } catch (e) {}
                        this.peeking = false;
                    });
                    this.desktopQuery.addEventListener('change', () => {
                        this.mobileOpen = false;
                        this.peeking = false;
                    });
                },
                toggle() {
                    if (this.desktopQuery.matches) {
                        this.pinned = !this.pinned;
                    } else {
                        this.mobileOpen = !this.mobileOpen;
                    }
                },
                peek() {
                    if (this.pinned || !this.desktopQuery.matches) {
                        return;
                    }
                    clearTimeout(this.hideTimer);
                    this.peeking = true;
                },
                scheduleHide() {
                    if (!this.peeking) {
                        return;
                    }
                    clearTimeout(this.hideTimer);
                    this.hideTimer = setTimeout(() => this.peeking = false, 300);
                },
                onKeydown(event) {
                    if (event.key === '\\' && (event.metaKey || event.ctrlKey)) {
                        event.preventDefault();
                        this.toggle();
                    } else if (event.key === 'Escape') {
                        this.mobileOpen = false;
                        this.peeking = false;
                    }
                },
            };
        }
    </script>
    <style>
        /* Sidebar: drawer on mobile, pinned or auto-hide (floating) on desktop */
        #app-sidebar {
            transform: translateX(-100%);
            transition: transform .22s ease, top .22s ease, bottom .22s ease, left .22s ease, border-radius .22s ease;
        }
        #app-sidebar.is-open {
            transform: none;
        }
        @media (min-width: 1024px) {
            #app-sidebar {
                transform: none;
            }
            #app-main {
                padding-left: 16rem;
                transition: padding-left .22s ease;
            }
            html.sidebar-unpinned #app-sidebar {
                top: .5rem;
                bottom: .5rem;
                left: .5rem;
                border-radius: 1rem;
                overflow: hidden;
                box-shadow: 0 25px 50px -12px rgb(0 0 0 / .45);
                transform: translateX(calc(-100% - 1rem));
            }
            html.sidebar-unpinned #app-sidebar.is-open {
                transform: none;
            }
            html.sidebar-unpinned #app-main {
                padding-left: 0;
            }
            html.sidebar-unpinned #sidebar-open-button {
                display: inline-flex;
            }
        }
    </style>
</head>
<body class="h-full text-slate-800">
    @php
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $isAdmin = $user?->isAdmin() ?? false;
        $isGuru = $user?->isGuru() ?? false;
        $isWaliKelas = $user?->isWaliKelas() ?? false;
        $isKesantrian = $user?->isKesantrian() ?? false;
        $isTu = $user?->isTu() ?? false;
        $isWaliMurid = $user?->isWaliMurid() ?? false;
        $isAdminOrSuper = $isSuperAdmin || $isAdmin;
        $responsibleClassroomIds = $user?->responsibleClassroomIds();
        $sampleReportId = $user && ! $isWaliMurid
            ? \App\Models\MonthlyReport::query()
                ->when($responsibleClassroomIds !== null, fn ($query) => $query->whereHas('student', fn ($student) => $student->whereIn('classroom_id', $responsibleClassroomIds)))
                ->latest('id')
                ->value('id')
            : null;
    @endphp

    <div class="min-h-full" x-data="sidebarLayout()" @keydown.window="onKeydown($event)">
        <!-- Hover zone on the left edge that reveals the auto-hidden sidebar -->
        <div class="hidden lg:block fixed inset-y-0 left-0 w-3 z-30" x-show="!pinned" @mouseenter="peek()"></div>

        <!-- Mobile backdrop -->
        <div x-show="mobileOpen" x-transition.opacity x-cloak @click="mobileOpen = false" class="fixed inset-0 z-45 bg-slate-900/50 lg:hidden" style="display: none;"></div>

        <!-- Sidebar Navigation -->
        <aside id="app-sidebar"
               :class="{ 'is-open': isOpen }"
               @mouseenter="peek()"
               @mouseleave="scheduleHide()"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col justify-between">
            <div class="flex-1 min-h-0 flex flex-col">
                <!-- Brand Header -->
                <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                    <a href="{{ $user ? route($user->homeRouteName()) : url('/') }}" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-600 flex items-center justify-center font-bold text-white shadow-lg shadow-emerald-500/30 text-lg">
                            T
                        </div>
                        <div>
                            <span class="text-base font-bold tracking-tight text-white block">Taqreer</span>
                            <span class="text-[10px] text-emerald-400 font-medium uppercase tracking-wider block">Reporting System</span>
                        </div>
                    </a>
                    <button type="button" @click="toggle()"
                            :title="pinned ? 'Sembunyikan otomatis sidebar (Ctrl/⌘ + \\)' : 'Sematkan sidebar (Ctrl/⌘ + \\)'"
                            :aria-label="pinned ? 'Sembunyikan otomatis sidebar' : 'Sematkan sidebar'"
                            class="p-1.5 -mr-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2"/>
                            <path stroke-linecap="round" stroke-width="2" d="M9 4v16"/>
                            <path x-show="pinned" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 10l-2 2 2 2"/>
                            <path x-show="!pinned" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l2 2-2 2"/>
                        </svg>
                    </button>
                </div>

                <!-- Navigation Links based on Role -->
                <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-sm font-medium">
                    @if($isWaliMurid)
                        <x-nav-link :href="route('parent.dashboard')" :active="request()->routeIs('parent.*')" icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                            Portal Rapor Ananda
                        </x-nav-link>
                    @else
                        @if($isAdminOrSuper)
                            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                Dashboard
                            </x-nav-link>
                        @endif

                        @if($isAdminOrSuper || $isGuru || $isWaliKelas)
                            <x-nav-link :href="route('analytics.index')" :active="request()->routeIs('analytics.*')" icon="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                Monitoring & Analitik
                            </x-nav-link>
                        @endif

                        @if($isAdminOrSuper)
                            <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                Rekap Laporan
                            </x-nav-link>
                        @endif

                        <!-- Input Modul Penilaian -->
                        @if($isAdminOrSuper || $isGuru || $isWaliKelas || $isKesantrian || $isTu)
                            <div class="pt-3 pb-1 px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                Portal Input Modul
                            </div>

                            @if($isAdminOrSuper || $isGuru)
                                <x-nav-link :href="route('tahfidz-journals.spreadsheet')" :active="request()->routeIs('tahfidz-journals.*')" icon="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                    Jurnal Tahfidz
                                </x-nav-link>
                            @endif

                            <x-nav-link :href="route('modules.spreadsheet')" :active="request()->routeIs('modules.*')" icon="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                Laporan Bulanan
                            </x-nav-link>
                        @endif

                        <!-- Master Data Section -->
                        @if($isAdminOrSuper)
                            <div class="pt-3 pb-1 px-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                Master Data & Akun
                            </div>

                            @if($isSuperAdmin)
                                <!-- ONLY Super Admin can manage Profil Lembaga -->
                                <x-nav-link :href="route('institution.edit')" :active="request()->routeIs('institution.*')" icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    Profil Lembaga
                                </x-nav-link>
                            @endif

                            <x-nav-link :href="route('classrooms.index')" :active="request()->routeIs('classrooms.*')" icon="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                Daftar {{ \App\Models\Institution::term('class') }}
                            </x-nav-link>

                            <x-nav-link :href="route('class-schedules.index')" :active="request()->routeIs('class-schedules.*')" icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z">
                                Jadwal {{ \App\Models\Institution::term('class') }}
                            </x-nav-link>

                            <x-nav-link :href="route('academic-calendar.index')" :active="request()->routeIs('academic-calendar.*')" icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                Kalender Akademik
                            </x-nav-link>

                            <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')" icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                Data {{ \App\Models\Institution::term('student') }}
                            </x-nav-link>

                            <x-nav-link :href="route('module-settings.index')" :active="request()->routeIs('module-settings.*')" icon="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                                Pengaturan Modul
                            </x-nav-link>

                            <x-nav-link :href="route('audit-logs.index')" :active="request()->routeIs('audit-logs.*')" icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                                Log Aktivitas
                            </x-nav-link>

                            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                Kelola Pengguna
                            </x-nav-link>
                        @endif
                    @endif
                </nav>
            </div>

            <!-- User Info & Logout Bar -->
            <div class="p-4 border-t border-slate-800">
                @if($user)
                    <div class="flex items-center justify-between mb-3">
                        <div class="min-w-0 pr-2">
                            <div class="text-xs font-bold text-white truncate">{{ $user->name }}</div>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-sm text-[10px] font-semibold bg-slate-800 text-emerald-400 border border-emerald-900">
                                {{ $user->role_label }}
                            </span>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 rounded-lg bg-slate-800 hover:bg-rose-950 text-slate-300 hover:text-rose-300 text-xs font-semibold transition border border-slate-700/60">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span>Keluar (Logout)</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2 bg-emerald-600 text-white rounded-lg text-xs font-bold">
                        Masuk (Login)
                    </a>
                @endif
            </div>
        </aside>

        <!-- Main Content Area -->
        <div id="app-main" class="min-h-full flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" id="sidebar-open-button" @click="toggle()" @mouseenter="peek()" @mouseleave="scheduleHide()"
                            title="Tampilkan sidebar (Ctrl/⌘ + \)" aria-label="Tampilkan sidebar"
                            class="lg:hidden inline-flex p-2 -ml-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2"/>
                            <path stroke-linecap="round" stroke-width="2" d="M9 4v16"/>
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900 truncate">{{ $header ?? 'Dashboard' }}</h1>
                        @if(isset($subheader))
                            <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $subheader }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    @if($sampleReportId)
                        <a href="{{ route('reports.preview', $sampleReportId) }}" target="_blank" title="Pratinjau PDF Sampel" aria-label="Pratinjau PDF Sampel" class="inline-flex items-center gap-2 p-2 sm:px-3.5 sm:py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg border border-emerald-200/80 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <span class="hidden sm:inline">Pratinjau PDF Sampel</span>
                        </a>
                    @endif
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <!-- Flash Notification Messages -->
                @if(session('success'))
                    <div class="mb-6 flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium shadow-xs">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 flex items-center gap-3 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm font-medium shadow-xs">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm shadow-xs">
                        <div class="font-semibold mb-1">Terdapat beberapa kesalahan pengisian:</div>
                        <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Yield Content -->
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
