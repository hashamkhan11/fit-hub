import './bootstrap';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { Chart } from 'chart.js/auto';

Chart.defaults.font.family = '"JetBrains Mono", ui-monospace, Consolas, monospace';
Chart.defaults.font.size = 11;
Chart.defaults.color = '#8A93A6';

document.addEventListener('alpine:init', () => {
    // Global replacement for the browser's native confirm()/prompt() —
    // one shared store + <x-confirm-modal/> (mounted once per layout) so any
    // button anywhere can trigger a styled confirmation with a single call:
    // $store.confirmModal.show({ message, danger, onConfirm: () => $wire.foo() }).
    // The callback is kept in a plain (non-Proxy-wrapped) field since Alpine's
    // reactive wrapping has no reason to touch it and functions don't serialize
    // cleanly through Alpine's reactive effects anyway.
    Alpine.store('confirmModal', {
        open: false,
        title: 'Confirm',
        message: '',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        danger: false,
        showInput: false,
        inputLabel: '',
        inputValue: '',
        inputRequired: false,
        _onConfirm: null,

        show(opts) {
            this.title = opts.title ?? 'Confirm';
            this.message = opts.message ?? '';
            this.confirmLabel = opts.confirmLabel ?? 'Confirm';
            this.cancelLabel = opts.cancelLabel ?? 'Cancel';
            this.danger = opts.danger ?? false;
            this.showInput = opts.input ?? false;
            this.inputLabel = opts.inputLabel ?? '';
            this.inputValue = opts.inputValue ?? '';
            this.inputRequired = opts.inputRequired ?? false;
            this._onConfirm = opts.onConfirm ?? null;
            this.open = true;
        },

        confirm() {
            if (this.showInput && this.inputRequired && !this.inputValue.trim()) return;

            const callback = this._onConfirm;
            const value = this.inputValue;
            this.close();
            callback?.(value);
        },

        close() {
            this.open = false;
            this._onConfirm = null;
            this.inputValue = '';
        },
    });

    // Count-up animation for KPI/stat numbers (`.fh-stat-value` / `.pf-stat-value`).
    // Animates the element's own text content from 0 to `target` over ~900ms
    // with an ease-out curve, formatting via `toLocaleString()` so thousands
    // separators match what the static Blade-rendered value would show.
    // `opts.decimals` controls fixed-decimal formatting (e.g. currency),
    // `opts.prefix`/`opts.suffix` wrap the formatted number (e.g. '$', '%')
    // so the animated text matches the final static text exactly.
    Alpine.data('countUp', (target, opts = {}) => ({
        display: '0',

        format(value) {
            const decimals = opts.decimals ?? 0;
            const formatted = Number(value).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
            return (opts.prefix ?? '') + formatted + (opts.suffix ?? '');
        },

        init() {
            const finalValue = Number(target) || 0;

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.display = this.format(finalValue);
                return;
            }

            const duration = 900;
            const start = performance.now();

            const step = (now) => {
                const elapsed = now - start;
                const t = Math.min(1, elapsed / duration);
                const eased = 1 - Math.pow(1 - t, 3);
                this.display = this.format(finalValue * eased);

                if (t < 1) requestAnimationFrame(step);
            };

            requestAnimationFrame(step);
        },
    }));

    Alpine.data('qrScanner', () => ({
        scanner: null,

        init() {
            this.scanner = new Html5QrcodeScanner('qr-reader', { fps: 10, qrbox: 250 }, false);

            this.scanner.render((decodedText) => {
                this.scanner.pause(true);

                this.$wire.checkIn(decodedText).then(() => {
                    setTimeout(() => this.scanner.resume(), 2000);
                });
            });
        },
    }));

    // "Clean Minimal" bar chart — thin bars, dashed gridlines, peak bar picked
    // out in `peakColor`, everything else muted steel. Tilted labels are
    // avoided by letting Chart.js autoSkip ticks instead of rotating them.
    //
    // The canvas is never destroyed/recreated on month navigation — Livewire
    // re-renders the whole component on every wire:click regardless of which
    // card's button was clicked, and tearing a chart's DOM down and rebuilding
    // it on every such render both raced Chart.js's initial-size measurement
    // (killing the entrance animation) and — because morphdom's keyed-node
    // matching has to be consistent across ALL siblings under one parent, not
    // just the element you meant to key — could misfire and drop *other*
    // cards' DOM entirely. Instead this chart is built once and kept alive;
    // `$wire.on(...)` below pushes fresh data into the live instance, and
    // `chart.update()` animates that transition on its own.
    //
    // `chart` is a closure variable, NOT a property on the object Alpine
    // returns. Alpine (like Vue) wraps every property of an x-data object in
    // a reactive Proxy, deeply, recursively. A Chart.js instance is full of
    // circular references (chart -> canvas -> chart, controllers -> chart,
    // scales -> chart...) — Proxy-wrapping that graph and then having Alpine
    // walk it during a reactive effect (which Livewire's morph triggers)
    // recurses forever and blows the call stack. Keeping the instance out of
    // Alpine's reactive data entirely avoids that path.
    //
    // `$wire.on(...)` (used below) has no unsubscribe: Livewire's own source
    // (`listen2`) just does `component.el.addEventListener(name, ...)` and
    // never removes it. If `init()` ever ran twice for the same canvas —
    // which is why the canvas is now also wrapped in `wire:ignore` in the
    // blade, so Livewire's morph never revisits it — the second `new Chart()`
    // would auto-destroy the first (Chart.js does this itself when a canvas
    // is reused), but the first init's listener would keep firing forever
    // against a chart whose `.canvas` is now null. Tagging the canvas element
    // itself with the live chart makes a second `init()` a no-op instead of a
    // silent leak, regardless of what triggers it.
    Alpine.data('barChart', (labels, data, label, peakColor, wholeNumbers = false) => {
        let chart = null;

        const barColors = (values) => {
            const peakIdx = values.indexOf(Math.max(...values));
            const mutedColor = 'rgba(141, 150, 160, 0.35)';
            return values.map((_, i) => (i === peakIdx ? peakColor : mutedColor));
        };

        return {
            init() {
                const canvas = this.$refs.canvas;

                if (canvas._fhChart) {
                    // `init()` running again on a canvas that already has a live chart
                    // means Livewire/Alpine re-initialized this component in place. The
                    // original `$wire.on` listener below is still attached and still
                    // valid (Livewire never removes it), so re-registering here would
                    // just create a second listener pointed at a second chart — leave
                    // both the chart and its listener alone.
                    chart = canvas._fhChart;
                    return;
                }

                const yTicks = { color: '#8A93A6' };

                if (wholeNumbers) {
                    // Counts of people (check-ins) can't be fractional — force integer-only
                    // gridlines instead of letting Chart.js pick "nice" steps like 2.5.
                    const maxValue = Math.max(1, ...data);
                    yTicks.stepSize = Math.max(1, Math.ceil(maxValue / 5));
                    yTicks.precision = 0;
                }

                this.$nextTick(() => {
                    chart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label,
                                data,
                                backgroundColor: barColors(data),
                                borderRadius: 2,
                                maxBarThickness: 22,
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: yTicks, grid: { color: '#1E2738', borderDash: [3, 3] }, border: { display: false } },
                                x: {
                                    grid: { display: false },
                                    border: { display: false },
                                    ticks: { maxRotation: 0, minRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                                },
                            },
                        },
                    });
                    canvas._fhChart = chart;
                });

                this.$wire.on('daily-checkins-updated', ({ labels: newLabels, data: newData }) => {
                    if (!chart) return;

                    chart.data.labels = newLabels;
                    chart.data.datasets[0].data = newData;
                    chart.data.datasets[0].backgroundColor = barColors(newData);

                    if (wholeNumbers) {
                        const maxValue = Math.max(1, ...newData);
                        chart.options.scales.y.ticks.stepSize = Math.max(1, Math.ceil(maxValue / 5));
                    }

                    chart.update();
                });
            },

            destroy() {
                if (chart?.canvas) chart.canvas._fhChart = null;
                chart?.destroy();
            },
        };
    });

    // "Modern Analytics" gradient area/line — for dense time-series data
    // (hours, days). Only a handful of x-axis labels are ever shown, spaced
    // out via the ticks callback below, so nothing needs to rotate.
    //
    // Same persistent-instance approach as `barChart` above: built once,
    // updated in place via `$wire.on` + `chart.update()` — never torn down on
    // month navigation. Chart.js's own update transition IS the animation.
    // See the comment on `barChart` for why `chart` is a closure variable
    // rather than a property Alpine would wrap in a reactive Proxy.
    Alpine.data('areaChart', (labels, data, color, eventName = 'peak-hours-updated') => {
        let chart = null;

        return {
            init() {
                const canvas = this.$refs.canvas;

                if (canvas._fhChart) {
                    chart = canvas._fhChart;
                    return;
                }

                this.$nextTick(() => {
                    chart = new Chart(canvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                data,
                                borderColor: color,
                                borderWidth: 2,
                                tension: 0.35,
                                fill: true,
                                backgroundColor: (ctx) => {
                                    const { chartArea } = ctx.chart;
                                    if (!chartArea) return 'transparent';
                                    const gradient = ctx.chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                    gradient.addColorStop(0, color + '55');
                                    gradient.addColorStop(1, color + '05');
                                    return gradient;
                                },
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                pointBackgroundColor: color,
                                pointBorderColor: '#131A28',
                                pointBorderWidth: 1.5,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { intersect: false, mode: 'index' },
                            },
                            scales: {
                                y: { beginAtZero: true, ticks: { color: '#8A93A6', precision: 0 }, grid: { color: '#1E2738', borderDash: [3, 3] }, border: { display: false } },
                                x: {
                                    grid: { display: false },
                                    border: { display: false },
                                    ticks: {
                                        maxRotation: 0,
                                        minRotation: 0,
                                        autoSkip: false,
                                        // Reads the *live* label count off the scale's own chart
                                        // (`this.chart` here is the Scale, per Chart.js's ticks
                                        // callback API) rather than closing over the initial
                                        // `labels` param, so tick-skipping stays correct after
                                        // `chart.update()` swaps in a different month's labels.
                                        callback(value, index) {
                                            const liveLabels = this.chart.data.labels;
                                            const step = Math.max(1, Math.round(liveLabels.length / 5));
                                            return index % step === 0 || index === liveLabels.length - 1 ? liveLabels[index] : '';
                                        },
                                    },
                                },
                            },
                        },
                    });
                    canvas._fhChart = chart;
                });

                this.$wire.on(eventName, ({ labels: newLabels, data: newData }) => {
                    if (!chart) return;

                    chart.data.labels = newLabels;
                    chart.data.datasets[0].data = newData;
                    chart.update();
                });
            },

            destroy() {
                if (chart?.canvas) chart.canvas._fhChart = null;
                chart?.destroy();
            },
        };
    });

    // Donut chart for categorical share-of-whole data (e.g. members by plan,
    // membership status). No month/week navigation on these yet, so — unlike
    // the charts above — nothing needs to push updated data into a live
    // instance; it's still built once and tagged onto the canvas so a stray
    // re-init (e.g. from an unrelated Livewire morph) is a no-op.
    Alpine.data('donutChart', (labels, data, colors) => {
        let chart = null;

        return {
            init() {
                const canvas = this.$refs.canvas;

                if (canvas._fhChart) {
                    chart = canvas._fhChart;
                    return;
                }

                this.$nextTick(() => {
                    chart = new Chart(canvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels,
                            datasets: [{ data, backgroundColor: colors, borderWidth: 0, hoverOffset: 4 }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '72%',
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12 } },
                            },
                        },
                    });
                    canvas._fhChart = chart;
                });
            },

            destroy() {
                if (chart?.canvas) chart.canvas._fhChart = null;
                chart?.destroy();
            },
        };
    });

    // "Modern Analytics" horizontal leaderboard bars — used for categorical
    // data (e.g. revenue by plan). Values are drawn at the end of each bar
    // via a small inline plugin, so the x-axis needs no ticks at all.
    //
    // Same persistent-instance approach as the other two charts. The card's
    // height (set via an inline style on the wrapping div, sized to the
    // number of plans) still updates normally through Livewire's own morph
    // since that's a plain attribute on a never-destroyed element; Chart.js's
    // ResizeObserver picks up the resulting height change and calls
    // chart.resize() on its own. See the comment on `barChart` for why
    // `chart` is a closure variable rather than a reactive Alpine property.
    Alpine.data('horizontalBarChart', (labels, data, colors, valuePrefix = '$') => {
        let chart = null;

        return {
            init() {
                const canvas = this.$refs.canvas;

                if (canvas._fhChart) {
                    chart = canvas._fhChart;
                    return;
                }

                this.$nextTick(() => {
                    chart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{ data, backgroundColor: colors, borderRadius: 6, maxBarThickness: 16 }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            layout: { padding: { right: 64 } },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => valuePrefix + Number(ctx.parsed.x).toLocaleString(),
                                    },
                                },
                            },
                            scales: {
                                x: { display: false, grid: { display: false }, border: { display: false } },
                                y: { grid: { display: false }, border: { display: false }, ticks: { color: '#EDF0F5' } },
                            },
                        },
                        plugins: [{
                            id: 'valueLabels',
                            afterDatasetsDraw(c) {
                                const { ctx } = c;
                                c.getDatasetMeta(0).data.forEach((bar, i) => {
                                    const value = c.data.datasets[0].data[i];
                                    ctx.save();
                                    ctx.font = '600 11px "JetBrains Mono", ui-monospace, Consolas, monospace';
                                    ctx.fillStyle = '#EDF0F5';
                                    ctx.textAlign = 'left';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(valuePrefix + Number(value).toLocaleString(), bar.x + 8, bar.y);
                                    ctx.restore();
                                });
                            },
                        }],
                    });
                    canvas._fhChart = chart;
                });

                this.$wire.on('revenue-by-plan-updated', ({ labels: newLabels, data: newData, colors: newColors }) => {
                    if (!chart) return;

                    chart.data.labels = newLabels;
                    chart.data.datasets[0].data = newData;
                    chart.data.datasets[0].backgroundColor = newColors;
                    chart.update();
                });
            },

            destroy() {
                if (chart?.canvas) chart.canvas._fhChart = null;
                chart?.destroy();
            },
        };
    });
});
