# OpenELO

Open source chess rating system based on Elo, designed for tournaments and matches outside the FIDE circuit.

## Quick Start

```bash
docker compose up -d
```

Open http://localhost:8080

## Features

- Decentralized rating system based on trust circuits
- No login/password - everything works via email confirmations
- Double validation for clubs and players
- Triple validation for match results
- FIDE-style K factor (40/20/10)
- Multi-language support (EN/IT)

## Configuration

Copy `.env.example` to `.env` and configure:

- `BASE_URL` - Your app URL
- `SMTP_*` - Email settings for production
- `DEFAULT_LANG` - Default language (en/it)

## Development

Emails are saved to `data/emails/` when SMTP is not configured.

## License

MIT
