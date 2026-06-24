const state = {page:1, perPage:window.NEWS_PER_PAGE||100, filter:'all', query:'', total:0, pages:1};
const $      = sel => document.querySelector(sel);
const fmtDate = d => { try { return new Intl.DateTimeFormat(document.documentElement.lang==='tr'?'tr-TR':'en-US',{dateStyle:'medium',timeStyle:'short'}).format(new Date(d)); } catch(e) { return d||''; } };
const escapeHtml = s => (s||'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));

function articleCard(item) {
    const detailUrl = `article.php?id=${encodeURIComponent(item.id||'')}`;
    return `<article class="news-card">
  <div class="news-meta">
    <span class="badge">${escapeHtml(item.source)}</span>
    <span>${escapeHtml(item.category)}</span>
    <span>&bull;</span>
    <time datetime="${escapeHtml(item.published)}">${fmtDate(item.published)}</time>
  </div>
  <h2><a href="${detailUrl}">${escapeHtml(item.title)}</a></h2>
  <p>${escapeHtml(item.summary||'Click through to read the full story at the original source.')}</p>
  <a class="btn" href="${detailUrl}">Read article</a>
  <a class="btn-ext" href="${escapeHtml(item.url)}" target="_blank" rel="noopener nofollow">Source &nearr;</a>
</article>`;
}

function renderNews(items) {
    const grid = $('#newsGrid');
    if (!grid) return;
    grid.innerHTML = items.length
        ? items.map(articleCard).join('')
        : '<div class="notice">No matching news found. Try a different filter or search term.</div>';
    renderPagination();
}

function renderPagination() {
    const el = $('#pagination');
    if (!el) return;
    if (state.pages <= 1) { el.innerHTML = ''; return; }
    const win = 5;
    let start = Math.max(1, state.page - Math.floor(win/2));
    let end   = Math.min(state.pages, start + win - 1);
    if (end - start < win - 1) start = Math.max(1, end - win + 1);
    let html = '';
    if (state.page > 1)           html += btn(state.page - 1, '&lsaquo; Prev');
    if (start > 1)                html += btn(1, '1') + (start > 2 ? '<span class="page-ellipsis">&hellip;</span>' : '');
    for (let i = start; i <= end; i++) html += btn(i, i, i === state.page);
    if (end < state.pages)        html += (end < state.pages - 1 ? '<span class="page-ellipsis">&hellip;</span>' : '') + btn(state.pages, state.pages);
    if (state.page < state.pages) html += btn(state.page + 1, 'Next &rsaquo;');
    el.innerHTML = html;
    el.querySelectorAll('[data-page]').forEach(b => b.addEventListener('click', () => {
        state.page = parseInt(b.dataset.page, 10);
        loadNews();
        window.scrollTo({top: 0, behavior: 'smooth'});
    }));
}
function btn(page, label, active) {
    return `<button class="page-btn${active?' active':''}" data-page="${page}">${label}</button>`;
}

async function loadNews() {
    const grid = $('#newsGrid');
    if (grid) grid.innerHTML = '<div class="notice">Loading news&hellip;</div>';
    try {
        const p = new URLSearchParams({page: state.page, limit: state.perPage, lang: state.filter, q: state.query});
        const data = await fetch(`api/news.php?${p}`).then(r => r.json());
        state.total = data.total || data.count || 0;
        state.pages = data.pages || 1;
        const up = $('#updatedAt');
        if (up) up.textContent = `Updated: ${fmtDate(data.updated_at)}`;
        renderNews(data.items || []);
        renderSources(data.items || []);
    } catch(e) {
        if (grid) grid.innerHTML = '<div class="notice">News could not be loaded. Check RSS source URLs and PHP settings.</div>';
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

function renderSources(items) {
    const box = $('#sourceList');
    if (!box) return;
    const map = new Map();
    items.forEach(i => map.set(i.source, {name:i.source, language:i.language, category:i.category}));
    box.innerHTML = [...map.values()].map(s =>
        `<li><strong>${escapeHtml(s.name)}</strong><div class="small">${escapeHtml(s.category)} &bull; ${escapeHtml((s.language||'').toUpperCase())}</div></li>`
    ).join('');
}

// read ?lang= from URL on page load (used by all-news.php links)
function langFromUrl() {
    const p = new URLSearchParams(window.location.search);
    return p.get('lang') || 'all';
}

let searchTimer;
document.addEventListener('DOMContentLoaded', () => {
    const initLang = langFromUrl();
    if (initLang !== 'all') {
        state.filter = initLang;
        document.querySelectorAll('.chip').forEach(b => {
            b.classList.toggle('active', b.dataset.filter === initLang);
        });
    }
    document.querySelectorAll('.chip').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.chip').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        state.filter = btn.dataset.filter;
        state.page   = 1;
        loadNews();
    }));
    const search = $('#searchInput');
    if (search) search.addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { state.query = e.target.value; state.page = 1; loadNews(); }, 400);
    });
    loadNews();
    loadSponsors();
});
