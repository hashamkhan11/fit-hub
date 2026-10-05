/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // "Redline" identity — navy-black athletic-data base with an ember-red
                // accent, shared by both the fh-* gym app and the pf-* RankSol platform
                // panel (one unified surface language, not two separate palettes).
                // Token *names* are kept stable from the prior "Pulse" system so existing
                // fh-*/pf-* component classes and ad-hoc utility usage repaint with
                // minimal file churn — only the hex/rgba values changed.
                void: '#0B0F1A',
                ink: { DEFAULT: '#EDF0F5', 2: '#1E2738' },
                chalk: { DEFAULT: '#131A28', 2: '#0F1420', 3: '#1E2738' },
                gold: { DEFAULT: '#F0562B', 2: '#C7401D', 3: '#FF8B63' },
                blue: { DEFAULT: '#4C8DFF', 2: '#3568C9' },
                turf: '#39D97A',
                tape: '#FF4D5E',
                warn: '#FFB020',
                'warn-soft': 'rgba(255, 176, 32, 0.14)',
                steel: { DEFAULT: '#8A93A6', 2: '#4B5568' },
                graphite: { DEFAULT: '#131A28', 2: '#0F1420', 3: '#0F1420' },
                teal: { DEFAULT: '#F0562B', 2: '#C7401D', 3: '#A83417' },
                mist: { DEFAULT: '#8A93A6', 2: '#4B5568' },
                'gold-soft': 'rgba(240, 86, 43, 0.14)',
                'blue-soft': 'rgba(76, 141, 255, 0.14)',
                'turf-soft': 'rgba(57, 217, 122, 0.12)',
                'tape-soft': 'rgba(255, 77, 94, 0.12)',
                'steel-soft': 'rgba(138, 147, 166, 0.1)',

                // Marketing site (public homepage) — repointed onto the same
                // "Redline" dark tokens as fh-*/pf-* above, so the public site
                // reads as the same product as the app/dashboard. mk-* class
                // names kept stable (own component set, own copy/layout) —
                // only the hex values now match the rest of the app.
                'mk-paper': '#131A28',
                'mk-paper-2': '#0F1420',
                'mk-ink': '#EDF0F5',
                'mk-ink-2': '#8A93A6',
                'mk-line': '#1E2738',
            },
            fontFamily: {
                // "Redline" identity — real self-hosted variable webfonts.
                display: ['"Bricolage Grotesque"', '-apple-system', 'sans-serif'],
                sans: ['"Plus Jakarta Sans"', '-apple-system', 'sans-serif'],
                mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
            },
            borderRadius: {
                // One shared scale: hairline-bordered cards/inputs get the smaller
                // radius, cards/modals get the larger one. Both surfaces (fh-*/pf-*)
                // now share it — no more sharp-vs-soft split between the two apps.
                DEFAULT: '12px',
                sm: '8px',
                lg: '20px',
                xl: '28px',
            },
            boxShadow: {
                'fh-card': '0 14px 30px -16px rgba(0,0,0,0.5)',
                'fh-lift': '0 20px 40px -18px rgba(0,0,0,0.55)',
                'fh-glow': '0 20px 45px -12px rgba(240,86,43,0.28), 0 4px 14px rgba(0,0,0,0.4)',
                'mk-card': '0 14px 30px -16px rgba(0,0,0,0.5)',
                'mk-lift': '0 20px 40px -18px rgba(0,0,0,0.55)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(14px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.7s cubic-bezier(0.16, 1, 0.3, 1) both',
            },
        },
    },
    plugins: [],
};
