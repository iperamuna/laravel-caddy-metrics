<div class="w-full space-y-6" x-data="{ showHelp: false }">
    <div class="flex justify-end gap-2">
        <button wire:click="toggleCaddyfile"
            class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-0.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5zm0 3a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z"
                    clip-rule="evenodd" />
            </svg>
            View Caddyfile
        </button>
        <button @click="showHelp = true"
            class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <svg class="-ml-0.5 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                    clip-rule="evenodd" />
            </svg>
            How to read charts
        </button>
    </div>

    <!-- Caddyfile Modal -->
    @if($showCaddyfile)
        @php $caddyfile = $this->caddyfileContent; @endphp
        <div class="relative z-50" aria-labelledby="caddyfile-modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl sm:p-6">
                        <div class="absolute right-0 top-0 pr-4 pt-4">
                            <button wire:click="toggleCaddyfile" type="button"
                                class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" id="caddyfile-modal-title">
                                    Caddyfile Configuration
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ $caddyfile['path'] }}
                                </p>
                            </div>
                            <div class="mt-4">
                                @if($caddyfile['error'])
                                    <div class="rounded-md bg-red-50 p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-red-700">{{ $caddyfile['error'] }}</p>
                                                <p class="mt-2 text-xs text-red-600">
                                                    Set the path in your <code>.env</code>:
                                                    <code>CADDYFILE_PATH=/path/to/Caddyfile</code>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="max-h-96 overflow-auto rounded-lg bg-gray-900 p-4">
                                        <pre
                                            class="text-sm text-gray-100 whitespace-pre-wrap font-mono">{{ $caddyfile['content'] }}</pre>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6">
                            <button wire:click="toggleCaddyfile" type="button"
                                class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Help Modal -->
    <div x-show="showHelp" style="display: none;" class="relative z-50 transition-opacity ease-out duration-300"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" x-show="showHelp"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6"
                    x-show="showHelp" @click.away="showHelp = false" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <div>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Understanding
                                Caddy Metrics</h3>
                            <div class="mt-4 text-left">
                                <div class="space-y-4 text-sm text-gray-500">

                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <h4 class="font-medium text-gray-900 mb-1">Health & Load
                                            (Up/Down)</h4>
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li><strong>Go Goroutines:</strong> Active "threads". Spikes mean high
                                                concurrency. Constant rising without falling indicates potential leaks.
                                            </li>
                                            <li><strong>Go Memstats Alloc Bytes:</strong> RAM usage. Sawtooth pattern is
                                                normal (garbage collection). Steep constant climb means memory leak.
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <h4 class="font-medium text-gray-900 mb-1">Traffic Volume
                                            (Always Up)</h4>
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li><strong>Request Duration Seconds Count:</strong> Cumulative total of
                                                processed requests. Steep slope = High Traffic. Flat = No Traffic.</li>
                                            <li><strong>Response Size Bytes Sum:</strong> Cumulative total bandwidth
                                                served. Steep slope = High Bandwidth usage.</li>
                                            <li><strong>Responses Total:</strong> Total responses sent. Should generally
                                                mirror request counts.</li>
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6">
                        <button type="button"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            @click="showHelp = false">Got it</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thread Advisor Component -->
    <livewire:thread-advisor />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:poll.5s>
        @foreach($this->metrics as $name => $metric)
            <div
                class="relative flex flex-col bg-white rounded-xl shadow-sm border border-slate-200/60 overflow-hidden transition-all duration-200 hover:shadow-md">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white">
                    <div>
                        <h3 class="text-base font-semibold leading-6 text-gray-900">
                            {{ Str::title(str_replace('_', ' ', $name)) }}
                        </h3>
                    </div>
                </div>

                <div class="p-6">
                    <div class="relative w-full h-64" wire:ignore>
                        <canvas id="chart-{{ Str::slug($name) }}"></canvas>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @script
    <script>
        let charts = {};

        const initChart = (name, labels, values) => {
            const id = `chart-${name.toLowerCase().replace(/[^a-z0-9]/g, '-')}`;
            const ctx = document.getElementById(id);
            if (!ctx) return;

            if (charts[id]) {
                charts[id].data.labels = labels;
                charts[id].data.datasets[0].data = values;
                charts[id].update('none');
                return;
            }

            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.2)');
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

            charts[id] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: name,
                        data: values,
                        borderColor: '#4f46e5',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    family: "'Figtree', sans-serif",
                                    size: 11
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true
                        }
                    }
                }
            });
        }

        const renderCharts = (metrics) => {
            if (!metrics) return;
            for (const [name, metric] of Object.entries(metrics)) {
                initChart(name, metric.labels, metric.values);
            }
        }

        // Initial render
        renderCharts(@js($this->metrics));

        $wire.on('metrics-updated', (event) => {
            renderCharts(event.metrics);
        });
    </script>
    @endscript
</div>