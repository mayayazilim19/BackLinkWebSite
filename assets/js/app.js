const state = {items:[], filtered:[], page:1, perPage:window.NEWS_PER_PAGE||100, filter:'all', source:'', query:''};
const $      = sel => document.querySelector(sel);
const fmtDate = d => { try { return new Intl.DateTimeFormat(document.documentElement.lang==='tr'?'tr-TR':'en-US',{dateStyle:'medium',timeStyle:'short'}).format(new Date(d)); } catch(e) { return d||''; } };
const escapeHtml = s => (s||'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));

function articleCard(item) {
    const detailUrl = 'article.php?id=' + encodeURIComponent(item.id || '');
    return `<article class="news-card">
  <div class="news-meta">
    <span class="badge">${escapeHtml(item.source)}</span>
    <span>${escapeHtml(item.category)}</span>
    <span>&bull;</span>
    <time datetime="${escapeHtml(item.published)}">${fmtDate(item.published)}</time>
  </div>
  <h2><a href="${detailUrl}">${escapeHtml(item.title)}</a></h2>
  <p>${escapeHtml(item.summary || '')}</p>
  <a class="btn"     href="${detailUrl}">${t('btn.read')}</a>
  <a class="btn-ext" href="${escapeHtml(item.url)}" target="_blank" rel="noopener nofollow">${t('btn.src')}</a>
</article>`;
}

function applyFilters() {
    const q = state.query.toLowerCase();
    state.filtered = state.items.filter(i => {
        if (state.source && i.source !== state.source) return false;
        if (state.filter !== 'all' && i.language !== state.filter) return false;
        if (q && !(i.title + ' ' + i.summary + ' ' + i.source + ' ' + i.category).toLowerCase().includes(q)) return false;
        return true;
    });
    state.page = 1;
    renderNews();
}

function setSourceFilter(sourceName) {
    state.source = sourceName;
    // highlight active source link
    document.querySelectorAll('.source-link').forEach(a =>
        a.classList.toggle('source-link--active', a.dataset.source === sourceName)
    );
}

function clearSourceFilter() {
    state.source = '';
    document.querySelectorAll('.source-link').forEach(a => a.classList.remove('source-link--active'));
}

function renderNews() {
    const grid = $('#newsGrid');
    if (!grid) return;
    const start = (state.page - 1) * state.perPage;
    const slice = state.filtered.slice(start, start + state.perPage);
    grid.innerHTML = slice.length
        ? slice.map(articleCard).join('')
        : `<div class="notice">${t('noNews')}</div>`;
    renderPagination();
}

function renderPagination() {
    const el = $('#pagination');
    if (!el) return;
    const pages = Math.max(1, Math.ceil(state.filtered.length / state.perPage));
    if (pages <= 1) { el.innerHTML = ''; return; }

    const p = state.page;
    const pageBtn = (n, label, active) =>
        `<button class="page-btn${active ? ' active' : ''}" data-page="${n}">${label ?? n}</button>`;
    const ellipsis = '<span class="page-ellipsis">&hellip;</span>';

    let html = '';
    if (p > 1) html += pageBtn(p - 1, '‹ Prev');
    html += pageBtn(1, null, p === 1);
    if (p > 3) html += ellipsis;
    for (let i = Math.max(2, p - 1); i <= Math.min(pages - 1, p + 1); i++) html += pageBtn(i, null, i === p);
    if (p < pages - 2) html += ellipsis;
    if (pages > 1) html += pageBtn(pages, null, p === pages);
    if (p < pages) html += pageBtn(p + 1, 'Next ›');

    el.innerHTML = html;
    el.querySelectorAll('[data-page]').forEach(b => b.addEventListener('click', () => {
        state.page = parseInt(b.dataset.page, 10);
        renderNews();
        window.scrollTo({top: 0, behavior: 'smooth'});
    }));
}

async function loadNews() {
    const grid = $('#newsGrid');
    if (grid) grid.innerHTML = `<div class="notice">${t('loading')}</div>`;
    try {
        const data = await fetch('api/news.php').then(r => r.json());
        state.items    = data.items || [];
        state.filtered = state.items;
        const up = $('#updatedAt');
        if (up) up.textContent = t('updated') + ' ' + fmtDate(data.updated_at);

        // Only filter by language if explicitly set via URL param (?lang=tr/en)
        const urlLang = new URLSearchParams(window.location.search).get('lang');
        if (urlLang && urlLang !== 'all') {
            state.filter = urlLang;
            document.querySelectorAll('.chip').forEach(b =>
                b.classList.toggle('active', b.dataset.filter === urlLang)
            );
        }
        applyFilters();
    } catch(e) {
        if (grid) grid.innerHTML = '<div class="notice">News could not be loaded. Check RSS source URLs and PHP settings on your host.</div>';
    }
}

async function loadSponsors() {
    try {
        const sponsors = await fetch('api/sponsors.php').then(r => r.json());
        document.querySelectorAll('[data-sponsors]').forEach(slot => {
            slot.innerHTML = sponsors.map(s =>
                `<a class="sponsor-card" href="${escapeHtml(s.url)}" target="_blank" rel="sponsored noopener">
                    <strong>${escapeHtml(s.name)}</strong>
                    <em>${escapeHtml(s.category||'Sponsor')}</em>
                    <span class="small">${escapeHtml(s.description||'')}</span>
                </a>`
            ).join('');
        });
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    // Language chip filters
    document.querySelectorAll('.chip').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.chip').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        state.filter = btn.dataset.filter;
        clearSourceFilter();
        applyFilters();
    }));

    // Newspaper source links in left sidebar
    document.querySelectorAll('.source-link[data-source]').forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            if (state.source === link.dataset.source) {
                // clicking active source again → show all
                clearSourceFilter();
                state.filter = 'all';
                document.querySelectorAll('.chip').forEach(b =>
                    b.classList.toggle('active', b.dataset.filter === 'all')
                );
            } else {
                setSourceFilter(link.dataset.source);
                // clear language chip selection when browsing by source
                document.querySelectorAll('.chip').forEach(b => b.classList.remove('active'));
            }
            applyFilters();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    });

    // Search
    const search = $('#searchInput');
    if (search) search.addEventListener('input', e => {
        state.query = e.target.value;
        applyFilters();
    });

    // Re-render cards when UI language changes (translation only — don't change news filter)
    document.addEventListener('siteLangChange', () => {
        if (state.items.length) applyFilters();
    });

    loadNews();
    loadSponsors();
});
