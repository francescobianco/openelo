# OpenELO

> **Gestisci facilmente corsi di scacchi e laboratori scolastici.**

Platform for chess instructors running **club courses and school workshops**:
sign up, create your classes, add your kids, follow their progress.

Two things drive every decision here:

1. **Simplicity** — a course is set up in thirty seconds; nothing to install,
   nothing to learn.
2. **A smooth path into the Italian FSI circuit** — records stay complete and
   tidy, so taking kids to youth games, provincial and school championships
   does not mean rebuilding the lists every time. OpenELO is not an official
   FSI service and does not replace it.

> **Mission change (August 2026).** OpenELO started as a decentralized Elo
> rating system for chess communities outside FIDE circuits. That product is
> **still in the repository and still working** (see [Legacy](#legacy-rating-system)),
> it is simply no longer the main entry point. See `DESIGN.md`.

## Quick Start

```bash
docker compose up -d
php -f migrate.php   # or visit /migrate.php
```

Open http://localhost:8080

## Features

### Chess school platform (current mission)

- Instructor accounts: sign up, log in, email verification, password reset
- Courses and school workshops handled the same way, with parent and teacher contacts
- Classes: level, venue, schedule, dates, max students, archive
- Students: contacts, guardian contacts for minors, level, rating, notes
- Enrollments: a student can attend several classes; leaving a class keeps history
- Every query is scoped to the logged-in instructor: no data is ever shared

### Legacy rating system

Still available, just unlinked from the main navigation:

- Decentralized rating system based on trust circuits
- No login/password — everything works via email confirmations
- Double validation for clubs and players, triple validation for match results
- FIDE-style K factor (40/20/10)

Reachable at `?page=home_rating`, `?page=circuits`, `?page=clubs`,
`?page=players`, `?page=matches`, `?page=create`, `?page=submit`.

## Configuration

Copy `.env.example` to `.env` and configure:

- `BASE_URL` - Your app URL
- `SMTP_*` - Email settings for production
- `FORCE_LANG` - Pin the UI to one language (default `it`; set to empty to re-enable the language switcher)
- `DEFAULT_LANG` - Default language when `FORCE_LANG` is empty (en/it)
- `DB_TYPE` - Database type: `sqlite` (default) or `mysql`
- `DB_*` - Database connection settings (for MySQL)

### Language

The UI is fully bilingual (IT/EN), but we ship in **Italian only** for now:
`FORCE_LANG=it` pins the language at code level and hides the language switcher.
All English translations are kept in `src/lang.php` and come back the moment
`FORCE_LANG` is emptied.

### Look & feel

The interface is inspired by ChessKid: light, colourful, very rounded, with
chunky buttons. All of it lives in `public/style.css` — the design tokens sit
in `:root` (the previous dark palette is kept there, commented out) and a
final section shapes the components. Headings use **Nunito** from Google Fonts
with a system fallback, so the site still looks right if the font cannot load.

### Legacy Hosting Support

For hosting that doesn't support environment variables:

1. Copy `env.php.example` to `env.php`
2. Fill in your configuration values
3. The app will automatically load it

## Database Migrations

Run database migrations to set up or update the schema:

```bash
# From command line
php -f migrate.php

# Or visit in browser (legacy hosting)
https://yourdomain.com/migrate.php
```
