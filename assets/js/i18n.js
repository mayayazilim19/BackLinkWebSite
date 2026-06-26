const translations = {
  en: {
    'nav.home':        'Home',
    'nav.allNews':     'All News',
    'nav.portfolio':   'Portfolio Companies',
    'nav.about':       'About',
    'hero.title':      'Turkish & English News Headlines',
    'hero.sub':        'Fresh headlines, short summaries, and direct links to the original newspapers — plus a clean advertising area for your portfolio companies.',
    'filter.all':      'All',
    'filter.tr':       'Turkish',
    'filter.en':       'English',
    'search.ph':       'Search headline, source, or category',
    'sidebar.sources': 'News Sources',
    'sidebar.links':   'Quick Links',
    'sidebar.portfolio':'Portfolio Companies',
    'sidebar.allNews': 'All News',
    'sidebar.trNews':  'Turkish News',
    'sidebar.enNews':  'English News',
    'src.group.tr':    'Turkish Newspapers',
    'src.group.en':    'English Newspapers',
    'curated.label':   'Curated feed:',
    'curated.text':    ' only headline, source, date, and short summary are shown. Use "Read full article" for the publisher page.',
    'btn.read':        'Read article',
    'btn.src':         'Source ↗',
    'art.back':        '← Back',
    'art.home':        'Home',
    'art.allNews':     'All News',
    'art.cta.text':    'This is the RSS summary. Read the complete story at the original publisher:',
    'art.cta.btn':     'Read Full Article',
    'updated':         'Updated:',
    'loading':         'Loading latest news…',
    'noNews':          'No matching news found. Try a different filter or search term.',
    'allnews.hero':    'All News',
    'allnews.sub':     'Browse the complete list of currently cached headlines from configured RSS sources.',
    'sponsor.strip':   'Sponsored portfolio directory: promote real companies with honest descriptions and clearly marked links.',
  },
  tr: {
    'nav.home':        'Ana Sayfa',
    'nav.allNews':     'Tüm Haberler',
    'nav.portfolio':   'Portföy Şirketi',
    'nav.about':       'Hakkında',
    'hero.title':      'Türkçe ve İngilizce Haber Başlıkları',
    'hero.sub':        'Güncel başlıklar, kısa özetler ve orijinal gazetelere doğrudan bağlantılar — portföy şirketleriniz için reklam alanı.',
    'filter.all':      'Tümü',
    'filter.tr':       'Türkçe',
    'filter.en':       'İngilizce',
    'search.ph':       'Başlık, kaynak veya kategori ara',
    'sidebar.sources': 'Haber Kaynakları',
    'sidebar.links':   'Hızlı Bağlantılar',
    'sidebar.portfolio':'Portföy Şirketi',
    'sidebar.allNews': 'Tüm Haberler',
    'sidebar.trNews':  'Türkçe Haberler',
    'sidebar.enNews':  'İngilizce Haberler',
    'src.group.tr':    'Türk Gazeteleri',
    'src.group.en':    'İngilizce Gazeteler',
    'curated.label':   'Seçilmiş feed:',
    'curated.text':    ' yalnızca başlık, kaynak, tarih ve kısa özet gösterilmektedir.',
    'btn.read':        'Haberi oku',
    'btn.src':         'Kaynak ↗',
    'art.back':        '← Geri',
    'art.home':        'Ana Sayfa',
    'art.allNews':     'Tüm Haberler',
    'art.cta.text':    'Bu RSS özetidir. Haberin tamamını orijinal kaynakta okuyun:',
    'art.cta.btn':     'Haberin Tamamını Oku',
    'updated':         'Güncellendi:',
    'loading':         'Haberler yükleniyor…',
    'noNews':          'Eşleşen haber bulunamadı. Farklı bir filtre veya arama terimi deneyin.',
    'allnews.hero':    'Tüm Haberler',
    'allnews.sub':     'Yapılandırılmış RSS kaynaklarından önbelläğe alınmış tüm başlıklar.',
    'sponsor.strip':   'Sponsorlu portföy rehberi: gerçek şirketleri dürüst açıklamalar ve açıkça işaretlenmiş bağlantılarla tanıtın.',
  }
};

let currentLang = localStorage.getItem('siteLang') || 'en';

function t(key) {
  return (translations[currentLang] || {})[key] || (translations.en || {})[key] || key;
}

function applyLang(lang) {
  if (!translations[lang]) return;
  currentLang = lang;
  localStorage.setItem('siteLang', lang);
  document.documentElement.lang = lang;

  document.querySelectorAll('[data-i18n]').forEach(el => {
    const v = t(el.dataset.i18n);
    if (v) el.textContent = v;
  });
  document.querySelectorAll('[data-i18n-ph]').forEach(el => {
    const v = t(el.dataset.i18nPh);
    if (v) el.placeholder = v;
  });
  document.querySelectorAll('.lang-btn').forEach(b =>
    b.classList.toggle('active', b.dataset.lang === lang)
  );

  // Notify app.js so it can re-render cards in the right language
  document.dispatchEvent(new CustomEvent('siteLangChange', {detail: {lang}}));
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.lang-btn').forEach(btn =>
    btn.addEventListener('click', () => applyLang(btn.dataset.lang))
  );
  applyLang(currentLang);
});
