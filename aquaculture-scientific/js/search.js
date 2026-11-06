(() => {
  'use strict';
  const form = document.getElementById('journal-search');
  const input = document.getElementById('query');
  const scope = document.getElementById('search-scope');
  const results = document.getElementById('search-results');
  const status = document.getElementById('search-status');
  if (!form) return;
  const corpus = window.journalSearchData || [];
  const normalize = text => text.toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  function search(updateURL = true) {
    const query = input.value.trim();
    results.replaceChildren();
    if (updateURL) {
      const params = new URLSearchParams();
      if (query) params.set('query', query);
      if (scope.value !== 'all') params.set('scope', scope.value);
      history.replaceState(null, '', location.pathname + (params.size ? '?' + params : ''));
    }
    if (!query) {
      status.textContent = 'Enter a search term to find articles, authors and journal pages.';
      return;
    }
    const terms = normalize(query).split(/\s+/).filter(Boolean);
    const matches = corpus.filter(item => {
      if (scope.value !== 'all' && item.kind !== scope.value) return false;
      const all = normalize(item.title + ' ' + item.text);
      return terms.every(term => all.includes(term));
    }).sort((a,b) => {
      const score = item => (item.kind === 'article' ? 10 : 0) + terms.reduce((sum,term) => sum + (normalize(item.title).includes(term) ? 5 : 0), 0);
      return score(b) - score(a);
    });
    status.textContent = matches.length + ' result' + (matches.length === 1 ? '' : 's') + ' for “' + query + '”';
    if (!matches.length) {
      const p = document.createElement('p');
      p.className = 'no-results';
      p.textContent = 'No matching content. Try an author name, a shorter keyword, or choose All content.';
      results.append(p);
    }
    for (const item of matches) {
      const entry = document.createElement('article');
      entry.className = 'search-result';
      const h = document.createElement('h2');
      const a = document.createElement('a');
      a.href = item.url;
      a.textContent = item.title;
      h.append(a);
      const p = document.createElement('p');
      const index = Math.max(0, normalize(item.text).indexOf(terms[0]) - 65);
      p.textContent = (index ? '…' : '') + item.text.slice(index, index + 260) + (item.text.length > index + 260 ? '…' : '');
      const kind = document.createElement('div');
      kind.className = 'result-kind';
      kind.textContent = item.kind === 'article' ? 'Article · Volume 1, Issue 1 (2026)' : 'Journal information';
      entry.append(h,p,kind);
      results.append(entry);
    }
  }
  const params = new URLSearchParams(location.search);
  input.value = params.get('query') || '';
  if (['all','article','page'].includes(params.get('scope'))) scope.value = params.get('scope');
  form.addEventListener('submit', event => {event.preventDefault();search();});
  scope.addEventListener('change', () => search());
  search(false);
})();
