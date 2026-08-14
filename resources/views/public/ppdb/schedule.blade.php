@extends('layouts.public')

@section('title', 'Jadwal '.$settings->admissionName())

@section('hero')
    <x-page-header :title="'Jadwal '.$settings->admissionName()"
                   :subtitle="$academicYear ? 'Tahapan penerimaan Tahun Ajaran '.$academicYear->name : null"
                   :breadcrumb="[$settings->admissionName() => route('ppdb.index'), 'Jadwal' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        @if ($schedules->isEmpty())
            <div class="card">
                <x-empty-state icon="calendar-off" title="Jadwal belum tersedia"
                               description="Panitia belum menerbitkan jadwal untuk tahun ajaran ini." />
            </div>
        @else
            <ol class="relative space-y-4 border-l-2 border-slate-200 pl-6">
                @foreach ($schedules as $schedule)
                    <li class="relative">
                        <span @class([
                            'absolute -left-[1.9rem] flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-slate-50',
                            'bg-emerald-500 text-white' => $schedule->phase() === 'ongoing',
                            'bg-slate-300 text-white' => $schedule->phase() === 'finished',
                            'bg-white text-slate-400 ring-slate-200' => $schedule->phase() === 'upcoming',
                        ])>
                            <x-icon :name="$schedule->phase() === 'finished' ? 'check' : 'dot'" class="h-3.5 w-3.5" />
                        </span>

                        <div class="card p-5">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h2 class="font-semibold text-slate-900">{{ $schedule->title }}</h2>
                                    @if ($schedule->wave)
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $schedule->wave->name }}</p>
                                    @endif
                                </div>
                                <x-badge :class="$schedule->phaseBadge()">{{ $schedule->phaseLabel() }}</x-badge>
                            </div>

                            <p class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-700">
                                <x-icon name="calendar" class="h-4 w-4 text-slate-400" />
                                {{ $schedule->dateRange() }}
                            </p>

                            @if ($schedule->description)
                                <p class="prose-content mt-2">{{ $schedule->description }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
@endsection
