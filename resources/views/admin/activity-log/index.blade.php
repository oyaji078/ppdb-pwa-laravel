@extends('layouts.admin')

@section('title', 'Activity Log')
@section('subheading', 'Catatan tindakan panitia pada sistem. Data ini tidak dapat diubah atau dihapus.')

@section('content')
    <div class="space-y-5">
        <form method="GET" action="{{ route('admin.activity-log.index') }}" class="card p-4">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <x-form.input name="q" label="Cari Keterangan" :value="request('q')" placeholder="Kata kunci" />
                <x-form.select name="action" label="Aksi" placeholder="Semua aksi" :value="request('action')" :options="$actions" />
                <x-form.select name="user_id" label="Pengguna" placeholder="Semua pengguna" :value="request('user_id')" :options="$users" />
                <x-form.input name="from" type="date" label="Dari Tanggal" :value="request('from')" />
                <x-form.input name="to" type="date" label="Sampai Tanggal" :value="request('to')" />
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" class="btn-primary">
                    <x-icon name="filter" class="h-4 w-4" />
                    Terapkan
                </button>
                <a href="{{ route('admin.activity-log.index') }}" class="btn-secondary">Bersihkan</a>
            </div>
        </form>

        <div class="card overflow-hidden">
            @if ($logs->isEmpty())
                <x-empty-state icon="history" title="Belum ada aktivitas"
                               description="Aktivitas panitia akan tercatat di sini secara otomatis." />
            @else
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Waktu</th>
                                <th scope="col">Pengguna</th>
                                <th scope="col">Aksi</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col">Objek</th>
                                <th scope="col">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        {{ $log->created_at?->translatedFormat('d M Y') }}
                                        <span class="block text-xs text-slate-500">{{ $log->created_at?->format('H:i:s') }}</span>
                                    </td>
                                    <td class="font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                                    <td>
                                        <x-badge class="bg-slate-100 text-slate-700 ring-slate-200">
                                            {{ \App\Services\ActivityLogger::label($log->action) }}
                                        </x-badge>
                                    </td>
                                    <td class="max-w-96">{{ $log->description }}</td>
                                    <td class="text-xs text-slate-500">
                                        {{ $log->subjectLabel() }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}
                                    </td>
                                    <td class="font-mono text-xs text-slate-500">{{ $log->ip_address }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
