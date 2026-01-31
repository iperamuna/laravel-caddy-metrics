@php
    $advisor = $this->advisor;
    $status = $advisor['status'];

    $theme = match ($status) {
        'optimal' => [
            'border' => 'border-emerald-200',
            'bg' => 'bg-emerald-100/50',
            'main_bg' => 'bg-emerald-50',
            'text' => 'text-emerald-700',
            'icon' => 'bg-emerald-500',
            'label' => 'System Optimal'
        ],
        'increase' => [
            'border' => 'border-green-200',
            'bg' => 'bg-green-100/50',
            'main_bg' => 'bg-green-50',
            'text' => 'text-green-700',
            'icon' => 'bg-green-500',
            'label' => 'Can Scale'
        ],
        'caution' => [
            'border' => 'border-amber-200',
            'bg' => 'bg-amber-100/50',
            'main_bg' => 'bg-amber-50',
            'text' => 'text-amber-700',
            'icon' => 'bg-amber-500',
            'label' => 'High Load'
        ],
        'headroom' => [
            'border' => 'border-indigo-200',
            'bg' => 'bg-indigo-100/50',
            'main_bg' => 'bg-indigo-50',
            'text' => 'text-indigo-700',
            'icon' => 'bg-indigo-500',
            'label' => 'Under Utilized'
        ],
        default => [
            'border' => 'border-slate-200',
            'bg' => 'bg-slate-100/50',
            'main_bg' => 'bg-slate-50',
            'text' => 'text-slate-700',
            'icon' => 'bg-slate-500',
            'label' => 'Status Unknown'
        ],
    };
@endphp

<div class="relative {{ $theme['main_bg'] }} border-2 {{ $theme['border'] }} rounded-2xl shadow-sm overflow-hidden"
    wire:poll.10s>
    <!-- Subtle background pattern -->
    <div class="absolute inset-0 opacity-[0.03] pointer-events-none"
        style="background-image: url('data:image/svg+xml,%3Csvg width=\" 20\" height=\"20\" viewBox=\"0 0 20 20\"
        xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cpath d=\"M0 0h20v20H0V0zm10 10l10-10H0L10 10z\" fill=\"%23000\"
        fill-rule=\"evenodd\"/%3E%3C/svg%3E');"></div>

    <div class="relative p-6 md:p-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div class="flex items-center gap-4">
                <div
                    class="h-12 w-12 rounded-xl {{ $theme['icon'] }} flex items-center justify-center shadow-lg shadow-indigo-100">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 22.5 12 13.5H3.75z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Thread
                        Optimization</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-extrabold text-slate-800 tracking-tight">{{ $theme['label'] }}</span>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ $theme['bg'] }} {{ $theme['text'] }} border {{ $theme['border'] }}">
                            Live Analysis
                        </span>
                    </div>
                </div>
            </div>

            <!-- Current Status Bubble -->
            <div class="{{ $theme['bg'] }} border-2 {{ $theme['border'] }} px-6 py-3 rounded-2xl text-center">
                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Current State</div>
                <div class="text-xl font-black {{ $theme['text'] }}">
                    {{ $advisor['utilization'] }}% <span class="text-xs font-medium opacity-60">usage</span>
                </div>
            </div>
        </div>

        <!-- Simulator Content -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left Side: Interactive Slider -->
            <div class="lg:col-span-4 space-y-6">
                <div class="space-y-3">
                    <div class="flex justify-between items-end">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Safety
                            Margin</label>
                        <span class="text-lg font-black text-indigo-600">{{ $this->safetyMargin }}%</span>
                    </div>
                    <input type="range" wire:model.live="safetyMargin" min="10" max="95" step="5"
                        class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    <p class="text-[10px] font-medium text-slate-400 leading-relaxed">
                        Adjust the margin of capacity you want to leave for other system processes and traffic spikes.
                        A <span class="font-bold">75% margin</span> is typically safe.
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-1 h-6 bg-indigo-500 rounded-full"></div>
                            <div>
                                <div class="text-[9px] font-bold text-slate-400 uppercase">Live Avg Load</div>
                                <div class="text-sm font-black text-slate-800">{{ $advisor['avg_goroutines'] }} <span
                                        class="text-[10px] font-medium">threads</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: The Simulation Display -->
            <div class="lg:col-span-8 flex flex-col justify-center">
                <div class="relative bg-slate-900 rounded-2xl p-6 overflow-hidden shadow-xl">
                    <!-- Geometric Background Patterns -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                    <div
                        class="absolute bottom-0 left-0 w-24 h-24 bg-indigo-500/10 rounded-full -ml-12 -mb-12 blur-2xl">
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                        <div class="flex-1 space-y-3 text-center md:text-left">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Simulation
                                result</h4>
                            <p class="text-base font-medium text-white leading-relaxed">
                                Running at <span class="text-amber-400 font-bold">{{ $advisor['utilization'] }}%</span>
                                with
                                <span class="text-white font-bold">{{ $advisor['current_threads'] }} threads</span>.
                            </p>
                            <p class="text-base font-medium text-slate-300 leading-relaxed">
                                Optimally, increase to
                                <span class="text-emerald-400 font-bold">{{ $advisor['suggested_threads'] }}
                                    threads</span>,
                                allowing for a <span class="text-indigo-300 font-bold">{{ $this->safetyMargin }}% safety
                                    margin</span>
                                even under load.
                            </p>
                        </div>

                        <div
                            class="flex flex-col items-center gap-3 bg-white/5 border border-white/10 p-4 rounded-xl backdrop-blur-sm">
                            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Recommended
                                Caddyfile</div>
                            <div class="flex items-center gap-3">
                                <code
                                    class="text-lg font-mono text-emerald-400 font-bold bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700 shadow-inner">
                                    num_threads {{ $advisor['suggested_threads'] }}
                                </code>
                            </div>
                            <div class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">Copy config
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>