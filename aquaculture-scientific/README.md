# Aquaculture Scientific — redesigned journal website

The complete website is in this folder. It contains 22 HTML pages and all images, fonts, the article PDF and the Word manuscript template.

## Open in VS Code

1. Open this folder in VS Code.
2. Open `index.html` with Live Server, or double-click it to view the site directly.
3. No Node.js or npm installation is required.

You can also run `python3 -m http.server 8080` from this folder and open http://localhost:8080.

## Publish on your own hosting

Upload the contents of this folder to your website's public document root. Keep the existing domain and hosting configuration. The supplied CNAME is retained for the original domain.

## Content and layout

The reference is https://epubs.icar.org.in/index.php/FT . The redesign uses the same OJS default-theme layout, blue navigation header, cover-and-about arrangement, two-column journal layout, announcement block, green article cards and submission button. All journal content and all supplied journal images remain Aquaculture Scientific's. No ICAR affiliation, journal rating, impact factor, DOI or readership statistic was added.

Pages include the home page, current issue, archive, announcements, article abstract and PDF, editorial board, aims and scope, article types, subject areas, calls, author guidelines, APC table, publication ethics, editorial office, contact, privacy and reader/library information.

## Working features

- Local, dependency-free full-text search with article and information filters.
- Responsive desktop and phone layout, mobile menu and About submenu.
- Supplied PDF and Word manuscript template downloads.
- Article abstract extracted from the supplied PDF and RIS/BibTeX citation downloads.
- Existing manuscript-details form retained at its original Formspree endpoint.
- Manuscript email links include the current form details; authors attach files themselves.
- Contact and board application forms prepare an email draft for the visitor to review and send.

## Account and submission integration

The upload referenced `/register.php` and `/login.php` but did not include either page or a complete account backend. Registration and Login now show honest editorial-office assistance pages rather than redirecting to missing files. They do not create accounts or store passwords. A complete account system or an OJS installation must be connected separately if required.

The existing Formspree account and endpoint are owned/configured separately. Browser checks verified required fields and email-draft wiring. No test manuscript was sent to the editorial office. Manuscript files are sent by email, as on the supplied website.

## Editing

- Every HTML file contains the shared header, sidebar and footer, so it also works without fetching HTML fragments.
- `css/ojs-base.css` is the OJS default theme stylesheet.
- `css/journal-layout.css` contains the reference layout customizations.
- `css/style.css` contains Aquaculture Scientific and responsive adaptations.
- `js/site.js` controls menus, draft emails, tables and citation downloads.
- `js/search-data.js` contains the search index. Update it when changing page content.
- `js/search.js` controls the search interface.

## Credits and licenses

The OJS Default Theme is by the Public Knowledge Project. The reused default theme stylesheet is distributed under GPL v3; its license is in `licenses/GPL-3.txt`. The layout stylesheet was adapted from the user-selected reference. Noto Sans is bundled for consistent offline typography under the SIL Open Font License in `licenses/Noto-Sans-OFL.txt`. Journal text, cover, photos, logo, PDF, template and publication details were supplied by the user and retain their original licenses.

The original PHP helper files are retained in `server-integration/` for your developer. Private credentials are not included in the redesigned public website. These helper files alone do not provide the missing registration/login backend.
