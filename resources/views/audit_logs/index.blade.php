@extends('layouts.app', [
    'header' => 'Log Aktivitas Data',
    'subheader' => 'Riwayat perubahan data rapor: siapa mengubah apa, dan kapan'
])

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Cari nama {{ strtolower(\App\Models\Institution::term('student')) }}..."
                class="w-full sm:w-64 px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition">

            <select name="user_id" onchange="this.form.submit()"
                class="px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-hidden transition bg-white">
                <option value="">Semua Pengguna</option>
                @foreach($staff as $u)
                    <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition">Cari</button>

            @if(request('search') || request('user_id'))
                <a href="{{ route('audit-logs.index') }}" class="text-xs text-rose-600 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50/75 text-xs uppercase font-semibold text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Waktu</th>
                        <th class="px-6 py-3.5">Pengguna</th>
                        <th class="px-6 py-3.5">{{ \App\Models\Institution::term('student') }} / Periode</th>
                        <th class="px-6 py-3.5">Data</th>
                        <th class="px-6 py-3.5">Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/60 transition align-top">
                            <td class="px-6 py-3 text-xs font-mono text-slate-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3 text-xs font-semibold text-slate-900">
                                {{ $log->user?->name ?? 'Sistem / akun dihapus' }}
                                @if($log->user)
                                    <span class="block font-normal text-[11px] text-slate-400">{{ $log->user->role_label }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-xs">
                                <span class="font-medium text-slate-800">{{ $log->student_name ?? '-' }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $log->period_title ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-3 text-xs font-semibold text-slate-700">{{ $log->field_label }}</td>
                            <td class="px-6 py-3 text-xs max-w-md">
                                <span class="text-rose-700 line-through break-words">{{ $log->old_value ?? '(kosong)' }}</span>
                                <span class="text-slate-400 mx-1">&rarr;</span>
                                <span class="text-emerald-700 font-medium break-words">{{ $log->new_value ?? '(kosong)' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada aktivitas perubahan data yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">{{ $logs->links() }}</div>
        @endif
    </div>

    <p class="text-[11px] text-slate-400">Riwayat disimpan selama {{ \App\Models\AuditLog::RETENTION_DAYS }} hari, setelah itu dihapus otomatis.</p>
</div>
@endsection
