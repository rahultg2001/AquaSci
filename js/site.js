(() => {
  'use strict';
  const toggle = document.getElementById('navToggle');
  const menu = document.getElementById('siteNavMenu');
  toggle?.addEventListener('click', () => {
    const open = toggle.getAttribute('aria-expanded') !== 'true';
    toggle.setAttribute('aria-expanded', String(open));
    menu.classList.toggle('pkp_site_nav_menu--isOpen', open);
    toggle.querySelector('span').textContent = open ? 'Close Menu' : 'Open Menu';
  });
  const about = document.getElementById('aboutToggle');
  function closeAbout() {
    about?.setAttribute('aria-expanded', 'false');
    about?.parentElement.classList.remove('is-open');
  }
  about?.addEventListener('click', () => {
    const open = about.getAttribute('aria-expanded') !== 'true';
    about.setAttribute('aria-expanded', String(open));
    about.parentElement.classList.toggle('is-open', open);
  });
  document.addEventListener('click', event => {
    if (!about?.parentElement.contains(event.target)) closeAbout();
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      closeAbout();
      if (menu?.classList.contains('pkp_site_nav_menu--isOpen')) {
        toggle.click();
        toggle.focus();
      }
    }
  });
  const current = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('#navigationPrimary a').forEach(link => {
    if (link.getAttribute('href') === current) link.setAttribute('aria-current', 'page');
  });
  document.querySelectorAll('table').forEach(table => {
    if (table.parentElement.classList.contains('table-scroll')) return;
    const wrapper = document.createElement('div');
    wrapper.className = 'table-scroll';
    wrapper.setAttribute('role', 'region');
    wrapper.setAttribute('aria-label', 'Scrollable data table');
    wrapper.tabIndex = 0;
    table.before(wrapper);
    wrapper.append(table);
  });
  // Associate the original journal's labels with their controls.
  document.querySelectorAll('form label:not([for])').forEach((label, i) => {
    const field = label.nextElementSibling;
    if (field?.matches('input,select,textarea')) {
      field.id ||= 'journal-field-' + i;
      label.htmlFor = field.id;
    }
  });
  document.querySelectorAll('form[data-email-form]').forEach(form => {
    form.addEventListener('submit', event => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const parts = [];
      form.querySelectorAll('input,select,textarea').forEach(field => {
        if (field.type === 'hidden' || field.type === 'submit') return;
        const label = field.labels?.[0]?.textContent.trim() || field.name || 'Details';
        parts.push(label + ': ' + field.value.trim());
      });
      const subject = current === 'board.html' ? 'Editorial role application — Aquaculture Scientific' : 'Editorial office enquiry — Aquaculture Scientific';
      const href = 'mailto:editor@aquaculturescientific.com?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(parts.join('\n\n'));
      let result = form.querySelector('.form-result');
      if (!result) {
        result = document.createElement('p');
        form.append(result);
      }
      result.replaceChildren();
      const message = document.createElement('p');
      message.textContent = 'Your email draft is ready. Review it and send it from your email app.';
      const link = document.createElement('a');
      link.href = href;
      link.textContent = 'Open email draft';
      result.append(message, link);
      result.hidden = false;
      location.href = href;
    });
  });
  const title = 'In silico analysis of novel Triacontafluoropentadec-1-ene as a sustainable replacement for dodecane in fisheries microplastics: Molecular docking, dynamics simulation and pharmacophore studies of acetylcholinesterase activity';
  const authors = ['Thakur, Rahul', 'Joshi, Vibhor', 'Sahoo, Ganesh Chandra', 'Tiwari, Rajnarayan R.', 'Rana, Sindhuprava'];
  document.querySelectorAll('.citation-download').forEach(button => {
    button.addEventListener('click', () => {
      const format = button.dataset.format;
      const content = format === 'ris'
        ? ['TY  - JOUR', ...authors.map(a => 'AU  - ' + a), 'TI  - ' + title, 'JO  - Aquaculture Scientific', 'PY  - 2026', 'VL  - 1', 'IS  - 1', 'M1  - AS2026-001', 'N1  - Sample article supplied with the journal website', 'ER  - ', ''].join('\r\n')
        : '@article{Thakur2026Sample,\n  title = {' + title + '},\n  author = {' + authors.join(' and ') + '},\n  journal = {Aquaculture Scientific},\n  year = {2026},\n  volume = {1},\n  number = {1},\n  note = {AS2026-001; Sample article}\n}\n';
      const url = URL.createObjectURL(new Blob([content], {type:'text/plain;charset=utf-8'}));
      const a = document.createElement('a');
      a.href = url;
      a.download = 'AS2026-001.' + format;
      a.click();
      setTimeout(() => URL.revokeObjectURL(url), 1500);
    });
  });
})();
