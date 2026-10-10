(function () {
  const pages = [
  ["index.html", "Home"],
  ["aims.html", "Aims & scope"],
  ["article-types.html", "Article types"],
  ["subjects.html", "Subjects"],
  ["issues.html", "Articles"],
  ["calls.html", "Calls"],
  ["apc.html", "APC & fees"],
  ["authors.html", "Guide for authors"],
  ["board.html", "Board"],
  ["editorial-office.html", "Editorial Office"],
  ["submit.html", "Submit"],
  ["contact.html", "Contact"]
];
  const here = (location.pathname.split("/").pop() || "index.html");

  function header() {
    return `
    <div class="topbar">
      <div class="wrap">
        <div>Open access · CC BY 4.0 · English only</div>
        <div><a href="register.html">Register / Login</a> · <a href="editorial-office.html">Editorial office</a></div>
      </div>
    </div>
    <div class="issn-bar">
      <div class="wrap">
        <div>ISSN (print): <b>****-****</b></div>
        <div>ISSN (online): <b>****-****</b></div>
        <div>International</div>
      </div>
    </div>
    <div class="brand-row">
      <div class="wrap">
        <a class="brand" href="index.html">
          <img src="assets/logo.jpg" alt="Aquaculture Scientific logo">
          <div>
            <h1>Aquaculture Scientific</h1>
            <p>An international journal</p>
          </div>
        </a>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <button class="mobile-toggle" id="navToggle" type="button">Menu</button>
          <a class="cta" href="submit.html">Submit manuscript</a>
        </div>
      </div>
    </div>
    <nav class="main" id="mainNav">
      <div class="wrap">
        ${pages.map(([href, label]) => `<a href="${href}" class="${here===href?"active":""}">${label}</a>`).join("")}
      </div>
    </nav>
    <div class="promo">100% APC waiver on every article type until 31 December 2026</div>`;
  }

  function footer() {
    return `
    <footer>
      <div class="wrap foot-grid">
        <div>
          <h4>Aquaculture Scientific</h4>
          <p>International open-access journal for fisheries, aquaculture and aquatic resources. Editorial office in Porto Alegre, Brazil.</p>
          <p>Rua Tocantins, 170— Agronomia<br>
          Porto Alegre, RS, 91540-420, Brazil</p>
        </div>
        <div>
          <h4>Authors</h4>
          <p><a href="authors.html">Guide for authors</a><br>
          <a href="templates/AS-manuscript-template.docx">Word template</a><br>
          <a href="article-types.html">Article types</a><br>
          <a href="calls.html">Call for papers</a><br>
          <a href="ethics.html">Publication ethics</a></p>
        </div>
        <div>
          <h4>Journal</h4>
          <p><a href="aims.html">Aims &amp; scope</a><br>
           <a href="subjects.html">Browse subjects</a><br>
           <a href="apc.html">APC &amp; fees</a><br>
           <a href="board.html">Editorial board</a><br>
           <a href="editorial-office.html">Editorial Office</a></p>
        </div>
        <div>
          <h4>Contact</h4>
          <p>editor@aquaculturescientific.com<br>
          submissions@aquaculturescientific.com<br>
          +55 (51) 98213-6091</p>
        </div>
      </div>
      <div class="wrap legal">
        ISSN (print) ****-**** · ISSN (online) ****-****.
        © 2026 Aquaculture Scientific. Licensed under CC BY 4.0.
      </div>
    </footer>`;
  }

  const h = document.getElementById("site-header");
  const f = document.getElementById("site-footer");
  if (h) h.innerHTML = header();
  if (f) f.innerHTML = footer();
  const btn = document.getElementById("navToggle");
  if (btn) btn.addEventListener("click", () => document.getElementById("mainNav").classList.toggle("open"));

  document.querySelectorAll("[data-form]").forEach((form) => {
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const box = form.querySelector(".form-result");
      if (box) {
        box.hidden = false;
        box.textContent = "Official Webside";
      }
      form.reset();
    });
  });
})();
