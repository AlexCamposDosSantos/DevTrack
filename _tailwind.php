<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        dt: {
          base:    '#0d1117',
          surface: '#161b22',
          card:    '#21262d',
          hover:   '#2d333b',
          border:  '#30363d',
          text:    '#e6edf3',
          muted:   '#8b949e',
          accent:  '#58a6ff',
          green:   '#3fb950',
          red:     '#f85149',
          orange:  '#f0883e',
          purple:  '#a371f7',
          yellow:  '#d29922',
          teal:    '#39c5cf',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['Fira Code', 'Cascadia Code', 'Consolas', 'monospace'],
      },
      keyframes: {
        'wip-blink':  { '0%,100%': { opacity: '1' }, '50%': { opacity: '.45' } },
        toast:        { from: { transform: 'translateY(10px)', opacity: '0' }, to: { transform: 'translateY(0)', opacity: '1' } },
        shimmer:      { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
        'modal-in':   { from: { opacity: '0', transform: 'scale(0.96) translateY(10px)' }, to: { opacity: '1', transform: 'scale(1) translateY(0)' } },
        'fade-in':    { from: { opacity: '0' }, to: { opacity: '1' } },
      },
      animation: {
        'wip-blink': 'wip-blink 1.5s infinite',
        toast:       'toast .2s ease',
        shimmer:     'shimmer 1.8s linear infinite',
        'modal-in':  'modal-in 0.22s cubic-bezier(0.16, 1, 0.3, 1)',
        'fade-in':   'fade-in 0.15s ease',
      },
      boxShadow: {
        'card':     '0 2px 8px rgba(0,0,0,0.35)',
        'card-lg':  '0 4px 16px rgba(0,0,0,0.45)',
        'modal':    '0 24px 64px rgba(0,0,0,0.6)',
      }
    }
  }
}
</script>

<!-- Component classes — processadas pelo Play CDN -->
<style type="text/tailwindcss">
@layer components {

  /* ── INPUTS ── */
  .dt-input {
    @apply w-full bg-dt-base border border-dt-border rounded-lg text-dt-text
           px-2.5 py-1.5 text-xs outline-none transition-all
           placeholder:text-dt-muted
           focus:border-dt-accent focus:ring-2 focus:ring-dt-accent/10;
  }
  .dt-input-sm {
    @apply dt-input py-1 text-xs;
  }

  /* ── SELECTS ── */
  /* Nota: padding-right e arrow são aplicados via .sel no app.css */
  .dt-select {
    @apply w-full bg-dt-base border border-dt-border rounded-lg text-dt-text
           px-2.5 py-1.5 text-xs outline-none transition-all cursor-pointer
           focus:border-dt-accent focus:ring-2 focus:ring-dt-accent/10;
  }
  .dt-select-sm {
    @apply dt-select py-1 text-xs;
  }

  /* ── LABELS ── */
  .dt-label {
    @apply block text-[10px] font-semibold text-dt-muted uppercase tracking-wider mb-1;
  }

  /* ── BUTTONS ── */
  .dt-btn {
    @apply inline-flex items-center justify-center gap-1.5
           bg-dt-accent text-dt-base font-semibold rounded-lg px-3.5 py-1.5
           text-xs cursor-pointer border-0
           hover:opacity-85 active:scale-95 transition-all whitespace-nowrap;
  }
  .dt-btn-sm {
    @apply dt-btn px-3 py-1.5 text-xs;
  }
  .dt-btn-xs {
    @apply dt-btn px-2.5 py-1 text-xs rounded-md;
  }
  .dt-btn-ghost {
    @apply inline-flex items-center gap-1.5
           bg-transparent border border-dt-border text-dt-muted rounded-lg px-3 py-2
           text-sm cursor-pointer no-underline whitespace-nowrap
           hover:border-dt-muted hover:text-dt-text transition-colors;
  }
  .dt-btn-ghost-sm {
    @apply dt-btn-ghost py-1.5 text-xs;
  }
  .dt-btn-icon {
    @apply inline-flex items-center justify-center w-7 h-7
           bg-transparent border border-dt-border text-dt-muted rounded-lg
           cursor-pointer text-base leading-none
           hover:text-dt-text hover:border-dt-muted transition-colors;
  }
  .dt-btn-danger {
    @apply inline-flex items-center gap-1 text-dt-red border border-[#5a2020]
           bg-[#2b0e0d] rounded-lg px-2.5 py-1 text-xs cursor-pointer
           hover:bg-[#3d1a1a] transition-colors;
  }

  /* ── TIPO BADGE ── */
  .dt-badge {
    @apply inline-block text-[10px] font-bold px-2 py-0.5 rounded-md
           uppercase tracking-wide;
  }

  /* ── TAG CHIP ── */
  .dt-tag {
    @apply inline-block text-[11px] font-medium px-2 py-0.5 rounded-md border;
  }

  /* ── SECTION TITLE ── */
  .dt-section-title {
    @apply text-xs font-semibold text-dt-muted uppercase tracking-wider
           border-b border-dt-border pb-2 mb-3;
  }

  /* ── CARD KANBAN ── */
  .dt-kanban-card {
    @apply flex overflow-hidden rounded-xl border border-dt-border/60
           bg-dt-card shadow-card select-none cursor-grab
           hover:bg-dt-hover hover:border-dt-border hover:shadow-card-lg
           hover:-translate-y-0.5 transition-all duration-150;
  }

  /* ── CONFIG ITEM ROW ── */
  .dt-item-row {
    @apply flex items-center gap-2 px-3 py-2.5 group
           hover:bg-dt-card transition-colors;
  }
  .dt-item-actions {
    @apply flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity ml-auto;
  }
  .dt-item-edit-btn {
    @apply text-dt-muted p-1 rounded-md hover:bg-dt-border hover:text-dt-text
           text-xs cursor-pointer bg-transparent border-0 transition-colors;
  }
  .dt-item-delete-btn {
    @apply text-dt-muted p-1 rounded-md hover:bg-[#2b0e0d] hover:text-dt-red
           text-xs cursor-pointer bg-transparent border-0 transition-colors;
  }

  /* ── EDIT FORM (config inline) ── */
  .dt-edit-form {
    @apply px-3 pb-3 pt-2 bg-[#0f1318] border-t border-dt-border;
  }
  .dt-edit-input {
    @apply flex-1 bg-dt-base border border-dt-border rounded-lg text-dt-text
           px-2.5 py-1.5 text-xs outline-none focus:border-dt-accent transition-all;
  }
  .dt-color-pick {
    @apply w-9 h-9 rounded-lg border border-dt-border bg-transparent cursor-pointer p-0.5 flex-shrink-0;
  }

}
</style>

<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,400;0,500;0,600;0,700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/app.css">
