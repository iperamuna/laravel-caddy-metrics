@extends('caddy-metrics::layouts.caddy')

@section('content')
    <div class="space-y-6">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    System Metrics <span class="text-gray-400 font-normal">for
                        {{ config('caddy-metrics.server_name') }}</span>
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Real-time performance monitoring from Caddy.
                </p>
            </div>
            <div class="mt-4 sm:ml-4 sm:mt-0 sm:flex-none">
                <span
                    class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                    Live
                </span>
            </div>
        </div>

        <!-- The Livewire component -->
        @if($enabled)
            <livewire:caddy-metrics />
        @else
            <div class="rounded-md bg-yellow-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Caddy Metrics are Disabled</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Metrics collection is currently disabled in your configuration. Enable it in
                                <code>config/caddy-metrics.php</code> or set <code>CADDY_METRICS_ENABLED=true</code> in your
                                .env file.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection