# Public-Readiness Plan — Draft Drip Scheduler

Goal: bring **WPDripDraftScheduler** up to the same "publicly seeable / usable"
standard as [`cloudflare-smtp-gateway`](https://github.com/teamfrontrow-james/cloudflare-smtp-gateway).

That repo's public-readiness kit was: a real `LICENSE` file, a polished `README`,
a `RELEASING.md`, CI + release/publish GitHub Actions workflows, dev-tooling config
(eslint/tsconfig + `package.json`), a `docs/` folder, and package-manager publish
metadata (`package.json`). Below is each item translated to a WordPress plugin.

## Translation map (Node → WordPress plugin)

| cloudflare-smtp-gateway (Node) | Draft Drip Scheduler (WP plugin) |
|---|---|
| `LICENSE` (MIT) | `LICENSE` — full **GPL-2.0** text (matches the header's declared license) |
| `package.json` publish metadata | `readme.txt` — **WordPress.org plugin format** (canonical WP distribution metadata) |
| `eslint.config.mjs` + `tsconfig.json` | `phpcs.xml` — WordPress Coding Standards ruleset |
| `package.json` devDependencies | `composer.json` — pulls in `wp-coding-standards/wpcs` + `phpcs` for local + CI |
| `.github/workflows/ci.yml` | `.github/workflows/ci.yml` — multi-version PHP lint + PHPCS |
| `.github/workflows/docker-publish.yml` | `.github/workflows/release.yml` — build a versioned plugin **zip**, attach to GH release on `v*` tag |
| `RELEASING.md` | `RELEASING.md` — adapted: bump header version → tag → zip → GH release (+ WP.org SVN notes) |
| `docs/troubleshooting.md` | `docs/troubleshooting.md` — extract the "immediate publishing" section from README |
| README polish | README polish — badges, contributing/license/issue links |

---

## Tasks (in order)

### 1. `LICENSE`
- Add the **full GPL-2.0 license text** (the plugin header already declares
  "GPL v2 or later" but no `LICENSE` file exists).
- Source: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt

### 2. `readme.txt` (WordPress.org standard)
This is the WP equivalent of `package.json` publish metadata — the file that makes
the plugin installable/listable. Include the standard headers and sections:
- Header block: `Contributors`, `Tags`, `Requires at least: 5.0`,
  `Tested up to: 6.x`, `Requires PHP: 7.2`, `Stable tag: 1.1.2`, `License: GPLv2 or later`.
- Sections: `== Description ==`, `== Installation ==`, `== Frequently Asked Questions ==`,
  `== Changelog ==`, `== Upgrade Notice ==` — port content from `README.md`.

### 3. `composer.json` + `phpcs.xml` (dev tooling)
- `composer.json`: name `teamfrontrow-james/draft-drip-scheduler`, `require-dev` on
  `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs`, `phpcompatibility/phpcompatibility-wp`;
  scripts: `"lint": "phpcs"`, `"lint:fix": "phpcbf"`.
- `phpcs.xml`: ruleset extending `WordPress`, scan `.` excluding `vendor/`,
  set `text_domain` to `draft-drip-scheduler`, set `minimum_supported_wp_version` 5.0
  and a `testVersion` of `7.2-` for PHPCompatibility.

### 4. `.github/workflows/ci.yml`
- Trigger on `push` to `main` + `pull_request`.
- Job A — **PHP lint**: matrix PHP `7.2`, `8.1`, `8.3`; `php -l` over all `*.php`.
- Job B — **coding standards**: `composer install`, then `composer run lint` (PHPCS).

### 5. `.github/workflows/release.yml`
- Trigger on `push` tags `v*` (mirrors cloudflare's release artifact workflow).
- Build a clean `draft-drip-scheduler.zip` (plugin files only — exclude `.git`,
  `.github`, `docs`, dev config, the plan/readme-dev files) and attach it to the
  GitHub Release via `softprops/action-gh-release` or `gh release upload`.

### 6. `RELEASING.md`
Document the release flow:
- Bump `Version:` in `draft-drip-scheduler.php` **and** `Stable tag:` in `readme.txt`.
- Update the changelog in both `README.md` and `readme.txt`.
- `git tag vX.Y.Z && git push --follow-tags` → release workflow builds + attaches the zip.
- `gh release create vX.Y.Z --generate-notes`.
- Note on WordPress.org SVN publishing (if/when the plugin is submitted).

### 7. `docs/troubleshooting.md`
- Extract the "Troubleshooting Immediate Publishing" content from `README.md` into
  its own doc (mirrors cloudflare's `docs/troubleshooting.md`), and link to it from
  the README.

### 8. Polish `README.md`
- Add a one-line bold tagline under the title.
- Add badges: License (GPL-2.0), WP version, PHP version, CI status.
- Add links to `LICENSE`, `RELEASING.md`, `docs/troubleshooting.md`, and the GitHub
  Issues page for support (replace "contact the plugin author").

### 9. `.gitignore`
- Add `/vendor/`, `composer.lock` (or keep — decide), and `*.zip` build artifacts
  (already covered) so dev tooling output isn't committed.

---

## Acceptance check
- [ ] `LICENSE` present with full GPL-2.0 text.
- [ ] `readme.txt` parses as a valid WP.org readme (header + required sections).
- [ ] `composer install && composer run lint` runs locally.
- [ ] CI workflow green on a test PR.
- [ ] Pushing a `vX.Y.Z` tag produces a GH release with `draft-drip-scheduler.zip`.
- [ ] README shows badges and links to docs/license/issues.

---

*Reference repo: cloudflare-smtp-gateway (commit `695b47e`). This plan adapts its
public-readiness scaffolding to WordPress plugin conventions.*
