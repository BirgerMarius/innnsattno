# Project Analysis

## 1. Short Summary of the Application

This is a Laravel 9 application for Innsatt.no-style utility and information pages, primarily in Norwegian. The main public features include TV guide pages, prison-specific prayer times, Premier League and Eliteserien schedules/standings, weather for Tyristrand/Ringerike prison, a professional-resource portal, a news portal, feedback submission, a printable month calendar, visitation information, printable views, Sudoku, word search puzzles, and a spin wheel/assignment page.

The application is mostly server-rendered with Blade views. Several features depend on external HTTP APIs, including VG/Schibsted TV and sports data, MET weather data, Bonnetid prayer times, news sources, and YouDoSudoku puzzle generation.

## 2. Laravel Structure

- Standard Laravel entry points are present: `artisan`, `bootstrap/app.php`, `public/index.php`, `routes/`, `config/`, `app/`, `resources/`, `database/`, `storage/`, and `tests/`.
- Web routes are defined in `routes/web.php`; `routes/api.php`, `routes/channels.php`, and `routes/console.php` exist but appear unused or default.
- Controllers live in `app/Http/Controllers/`:
  - Public controllers cover football, Premier League, Eliteserien, weather, prayer times, news, professional resources, feedback, calendar, visitation, puzzles, and the assignment page.
  - Admin controllers cover authentication and management of feedback, news, and professional resources.
- Service classes under `app/Services/` handle football/Schibsted data, weather forecasts, news feeds, and word-search generation.
- A Livewire component exists at `app/Http/Livewire/Celle.php`, with a matching Blade view under `resources/views/livewire/`.
- The app still contains older Laravel-style files such as `app/User.php`, `database/seeds`, and `database/factories`, while dependencies target Laravel 9.
- Domain migrations and models support feedback, news, and professional resources in addition to the older default tables.

## 3. Main Technologies

- PHP `^8.0`
- Laravel Framework `^9.0`
- Laravel HTTP client / Guzzle for external API calls
- Livewire `^2.10`
- Blade templates
- Laravel Mix `^6.0.39`
- Webpack
- Tailwind CSS `^3.0.8`
- PostCSS
- Axios and Lodash in frontend dependencies
- BrowserSync configured for local development against `127.0.0.1:8000`
- PHPUnit `^9.3.3`
- Laravel Debugbar in development dependencies

## 4. Folder Overview

- `app/`: Application code. Contains controllers, services, models, middleware, providers, console commands, and one Livewire component.
- `app/Http/Controllers/`: Main public and admin feature logic. Some TV-guide and print behavior remains directly in `routes/web.php`.
- `app/Services/`: Integrations and normalization for weather, news, football/Schibsted data, and word-search generation.
- `routes/`: Route definitions. Most public behavior is in `web.php`.
- `resources/views/`: Blade templates for TV guide, print pages, football leagues, weather, prayer times, news, professional resources, feedback, calendar, visitation, word search, Sudoku, spin wheel, shared layout/partials, and archived/older views.
- `resources/css/`: Source CSS for main app and spin wheel styling.
- `resources/js/`: Source JavaScript entry points.
- `resources/wordsearch/`: JSON word list used by the word search generator.
- `public/`: Web root. Contains compiled CSS/JS, images, favicon files, `mix-manifest.json`, `robots.txt`, `.htaccess`, and `web.config`.
- `config/`: Standard Laravel configuration files.
- `database/`: Migrations, factories, and seeders for default tables plus feedback, professional resources/categories/tags, and news.
- `tests/`: An extensive Laravel feature/unit suite covering core routes, integrations, normalization, admin flows, submissions, and print views. At the current `main` baseline (`2343b5f`), the full suite passes with 129/129 tests.
- `storage/`: Standard ignored Laravel runtime folders for cache, sessions, compiled views, logs, and Debugbar data.

## 5. Build and Deployment Observations

- Frontend assets are built with Laravel Mix, not Vite.
- Available npm scripts include `dev`, `watch`, `hot`, and `prod`.
- `webpack.mix.js` builds:
  - `resources/js/app.js` to `public/js`
  - `resources/css/app.css` to `public/css` with Tailwind CSS
- `resources/css/spin.css` and `public/js/spin.js`/`public/js/wheel.js` are present, but only the main app CSS/JS are configured in `webpack.mix.js`.
- Compiled assets are committed under `public/css`, `public/js`, and `public/mix-manifest.json`.
- A local Docker environment is provided by the currently untracked `Dockerfile.local` and `compose.local.yaml`. It runs the Laravel application with PHP 8.1 and a local MySQL 8 service; these files are for development/testing and are not production deployment configuration.
- `.env.example` is mostly Laravel default values and does not document application-specific external API settings such as `YOUDOSUDOKU_API_KEY`.
- The README is the stock Laravel README and does not document how this application should be installed, configured, built, tested, or deployed.
- PHPUnit uses in-memory SQLite and isolated array-backed cache/session drivers. The full suite can be run locally in the application container.

## 6. Risks

- External API dependency risk: TV guide, football, weather, prayer times, news, and Sudoku functionality depend on third-party APIs being available and returning the expected data.
- Hardcoded secret/token risk: `PrayerController` contains an inline Bonnetid API token instead of reading it from environment configuration.
- Missing environment documentation: `YOUDOSUDOKU_API_KEY` is used by `TidsfordrivController` but is not listed in `.env.example`.
- Limited error handling: several external API calls assume successful responses and expected array keys. Upstream API failures or schema changes may produce user-facing errors.
- Route complexity risk: substantial TV guide fetching logic is implemented directly in `routes/web.php`, making it harder to test and maintain.
- Test maintenance risk: the suite is broad and currently green, but external response contracts and time-sensitive sports data still require ongoing fixture/configuration maintenance.
- Dependency age risk: the app uses Laravel 9 and Laravel Mix. These can still work, but they are older than current Laravel frontend conventions and may require planned maintenance.
- Code style consistency risk: several files show inconsistent indentation and formatting, which can make future changes more error-prone.
- Potential stale feature risk: football configuration includes season-specific identifiers, which must be updated as competitions change.
- Archived view risk: `resources/views/archive/` contains old Blade/PHP files and copied files whose current relevance is unclear.

## 7. Missing Documentation

- Application purpose and user-facing feature overview.
- Local setup steps, including PHP, Composer, Node, npm, database, and required services.
- Required environment variables and external API keys.
- External API ownership, endpoints, expected response contracts, and fallback behavior.
- Build commands and whether compiled assets should be committed.
- Deployment process, target hosting environment, web server requirements, queue/cache/session choices, and release checklist.
- Testing strategy, local Docker commands, and how to maintain fixtures.
- Operational troubleshooting for failed TV guide, football, prayer time, or Sudoku API calls.
- Explanation of active versus archived views and assets.
- Maintenance notes for updating football tournament IDs, channel lists, prison IDs, and API tokens.
