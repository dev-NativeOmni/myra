@extends('layouts.app', [
    'header' => 'Jadwal Hari Aktif Halaqah Kelas',
    'subheader' => 'Tentukan hari aktif pertemuan dan halaqah tahfidz untuk masing-masing kelas'
])

@section('content')
<div class="space-y-6" x-data="{ activeDay: '1' }">
    <!-- Top Action Card -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">Pengaturan Hari Aktif per Kelas</h3>
            <p class="text-xs text-slate-500 mt-0.5">Pilih hari pada tab di bawah lalu sesuaikan status keaktifan tiap kelas untuk hari tersebut.</p>
        </div>
        <button type="submit" form="schedules-form"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            <span>Simpan Perubahan Jadwal</span>
        </button>
    </div>

    <!-- Day Selector Tabs -->
    <div class="flex flex-wrap gap-1.5 p-1.5 bg-slate-100 rounded-2xl border border-slate-200 shadow-xs">
        @foreach ($daysOfWeek as $dayNum => $dayName)
            <button type="button"
                    @click="activeDay = '{{ $dayNum }}'"
                    :class="activeDay === '{{ $dayNum }}' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200'"
                    class="flex-1 min-w-[80px] py-2 px-3 rounded-xl text-xs font-semibold uppercase tracking-wider transition duration-150 cursor-pointer text-center select-none">
                {{ $dayName }}
            </button>
        @endforeach
    </div>

    <!-- Main Schedules Form -->
    <form id="schedules-form" method="POST" action="{{ route('class-schedules.update') }}">
        @csrf

        @foreach ($daysOfWeek as $dayNum => $dayName)
            <div x-show="activeDay === '{{ $dayNum }}'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs space-y-5">
                
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <h4 class="font-bold text-sm text-slate-900 uppercase tracking-wide">Hari {{ $dayName }}</h4>
                    </div>
                    <span class="text-xs text-slate-500 font-medium">Klik pada kartu kelas untuk mengaktifkan / menonaktifkan</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($classrooms as $class)
                        @php
                            $isActive = in_array($dayNum, $class->tahfizh_days ?? [1, 2, 3, 4, 5, 6], true);
                        @endphp
                        <label class="relative flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:border-emerald-300 transition cursor-pointer bg-slate-50/60 hover:bg-emerald-50/30 select-none group">
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                       name="schedules[{{ $class->id }}][]"
                                       value="{{ $dayNum }}"
                                       {{ $isActive ? 'checked' : '' }}
                                       class="w-4 h-4 text-emerald-600 rounded-sm border-slate-300 focus:ring-emerald-500 cursor-pointer">
                                <div>
                                    <div class="font-bold text-xs text-slate-800 group-hover:text-emerald-900">
                                        Kelas {{ $class->name }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $class->students()->count() }} Santri Terdaftar
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-sm {{ $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500' }}">
                                {{ $isActive ? 'Aktif' : 'Libur' }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </form>
</div>
@endsection

