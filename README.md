# crystalcentury

Operational source of truth for [crystalcentury.com](https://crystalcentury.com).

## Live

- Site: https://crystalcentury.com

This repo intentionally tracks only the parts of the WordPress stack that benefit from version control:

- `child-theme/`
- `mu-plugins/`
- `scripts/`
- `docs/`

It does not attempt to version WordPress core, uploads, or the full live database.

## Structure

- `child-theme/`: `hello-elementor-child` code pulled from the live site
- `mu-plugins/`: live must-use plugins that materially affect storefront behavior
- `scripts/`: pull/deploy helpers for syncing custom code with the live host
- `docs/audits/`: reproducible audit notes and captured HTML evidence
- `docs/exports/`: lightweight option and identity exports from the live site

## Live sync

Pull the current live custom code:

```powershell
pwsh -NoProfile -File .\scripts\Pull-LiveCustomCode.ps1
```

Deploy the tracked custom code back to the live site:

```powershell
pwsh -NoProfile -File .\scripts\Deploy-LiveCustomCode.ps1
```

## Operating boundary

Managed ScalaHosting support scope must remain intact.

Allowed here:

- child theme changes
- must-use plugin changes
- WP-CLI verification
- cache flushes

Not tracked or modified here without deliberate escalation:

- root or SPanel internals
- Nginx/Apache/OpenLiteSpeed topology
- `/swrapper`
- server packages and services
