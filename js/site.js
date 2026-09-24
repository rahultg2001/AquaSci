(function () {

  /* =========================
     WEBSITE PAGES
     ========================= */

  const pages = [
    ["/", "Home"],
    ["/aims", "Aims & scope"],
    ["/article-types", "Article types"],
    ["/subjects", "Subjects"],
    ["/issues", "Articles"],
    ["/calls", "Calls"],
    ["/apc", "APC & fees"],
    ["/authors", "Guide for authors"],
    ["/board", "Board"],
    ["/editorial-office", "Editorial Office"],
    ["/submit", "Submit"],
    ["/contact", "Contact"]
  ];


  /* =========================
     CURRENT PAGE
     ========================= */

  let here = window.location.pathname;

  // Remove trailing slash except for homepage
  if (here.length > 1 && here.endsWith("/")) {
    here = here.slice(0, -1);
  }

  // Homepage
  if (here === "") {
    here = "/";
  }


  /* =========================
     HEADER
     ========================= */

  function header() {
    return `
    <div class="topbar">
      <div class="wrap">
        <div>Open access · CC BY 4.0 · English only</div>

        <div>
          <a href="/register">Register / Login</a>
          ·
          <a href="/editorial-office">Editorial office</a>
        </div>
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

        <a class="brand" href="/">
          <img
            src="/assets/logo.jpg"
            alt="Aquaculture Scientific logo"
          >

          <div>
            <h1>Aquaculture Scientific</h1>
            <p>An international journal</p>
          </div>
        </a>


        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">

          <button
            class="mobile-toggle"
            id="navToggle"
            type="button"
          >
            Menu
          </button>

          <a class="cta" href="/submit">
            Submit manuscript
          </a>

        </div>

      </div>
    </div>


    <nav class="main" id="mainNav">
      <div class="wrap">

        ${pages.map(([href, label]) => {

          const active =
            href === "/"
              ? here === "/"
              : here === href;

          return `
            <a
              href="${href}"
              class="${active ? "active" : ""}"
            >
              ${label}
            </a>
          `;

        }).join("")}

      </div>
    </nav>


    <div class="promo">
      100% APC waiver on every article type until 31 December 2026
    </div>
    `;
  }


  /* =========================
     FOOTER
     ========================= */

  function footer() {
    return `
    <footer>

      <div class="wrap foot-grid">


        <!-- JOURNAL -->
        <div>

          <h4>Aquaculture Scientific</h4>

          <p>
            International open-access journal for fisheries,
            aquaculture and aquatic resources.
            Editorial office in Porto Alegre, Brazil.
          </p>

          <p>
            Rua Tocantins, 170— Agronomia<br>
            Porto Alegre, RS, 91540-420, Brazil
          </p>

        </div>


        <!-- AUTHORS -->
        <div>

          <h4>Authors</h4>

          <p>

            <a href="/authors">
              Guide for authors
            </a>
            <br>

            <a href="/templates/AS-manuscript-template.docx">
              Word template
            </a>
            <br>

            <a href="/article-types">
              Article types
            </a>
            <br>

            <a href="/calls">
              Call for papers
            </a>
            <br>

            <a href="/ethics">
              Publication ethics
            </a>

          </p>

        </div>


        <!-- JOURNAL LINKS -->
        <div>

          <h4>Journal</h4>

          <p>

            <a href="/aims">
              Aims &amp; scope
            </a>
            <br>

            <a href="/subjects">
              Browse subjects
            </a>
            <br>

            <a href="/apc">
              APC &amp; fees
            </a>
            <br>

            <a href="/board">
              Editorial board
            </a>
            <br>

            <a href="/editorial-office">
              Editorial Office
            </a>

          </p>

        </div>


        <!-- CONTACT -->
        <div>

          <h4>Contact</h4>

          <p>
            editor@aquaculturescientific.com<br>
            submissions@aquaculturescientific.com<br>
            +55 (51) 98213-6091
          </p>

        </div>

      </div>


      <div class="wrap legal">

        ISSN (print) ****-**** ·
        ISSN (online) ****-****.
        © 2026 Aquaculture Scientific.
        Licensed under CC BY 4.0.

      </div>

    </footer>
    `;
  }


  /* =========================
     INSERT HEADER + FOOTER
     ========================= */

  const h = document.getElementById("site-header");
  const f = document.getElementById("site-footer");

  if (h) {
    h.innerHTML = header();
  }

  if (f) {
    f.innerHTML = footer();
  }


  /* =========================
     MOBILE MENU
     ========================= */

  const btn = document.getElementById("navToggle");

  if (btn) {

    btn.addEventListener("click", function () {

      const nav = document.getElementById("mainNav");

      if (nav) {
        nav.classList.toggle("open");
      }

    });

  }


  /* =========================
     FORMS
     ========================= */

  document
    .querySelectorAll("[data-form]")
    .forEach(function (form) {

      form.addEventListener("submit", function (e) {

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