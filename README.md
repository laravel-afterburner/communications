# Afterburner Communications Package

Team-scoped announcements and discussion threads for Laravel Afterburner Jetstream.

## Features

- **Announcements** — role-targeted posts, read tracking, scheduled email (`announcements:send-scheduled`)
- **Discussion threads** — scopes: `council`, `team`, `property` (optional lot link)

## Installation

### Local Development Setup

For local development, add the package as a path repository:

```bash
composer config repositories.afterburner-communications path ../afterburner-communications
composer require laravel-afterburner/communications:@dev
```

### Quick Install (Recommended)

```bash
composer require laravel-afterburner/communications
php artisan afterburner:communications:install
```

The install command will:

- Publish config and views
- Add communications environment variables to `.env` and `.env.example` when present
- Optionally run migrations and seed permissions

### Manual Install

```bash
php artisan vendor:publish --tag=afterburner-communications-config
php artisan vendor:publish --tag=afterburner-communications-assets
php artisan migrate
php artisan db:seed --class="Afterburner\\Communications\\Database\\Seeders\\CommunicationsPermissionsSeeder"
```

If you use `migrate:fresh --seed`, register `CommunicationsPermissionsSeeder` with your app's `PackageSeederRegistry` (or call it from `DatabaseSeeder`) so discussion permissions are assigned beyond the default role templates.

## Configuration

Environment variables (added by the install command):

```env
AFTERBURNER_COMMUNICATIONS_ENABLED=true
AFTERBURNER_COMMUNICATIONS_DISCUSSIONS_ENABLED=true
```

Package options live in `config/afterburner-communications.php`. Discussions default on when `afterburner.entity_label` is `strata`.

## Permissions

| Slug | Purpose |
|------|---------|
| `post_announcements` | Create and manage team announcements |
| `manage_discussions` | Create / lock threads |

Seeded via `CommunicationsPermissionsSeeder` (registered with `PackageSeederRegistry`).

## Events (host listeners)

- `AnnouncementPublished`
- `ThreadCreated`

## Scheduled Tasks

Register in your host app:

```php
$schedule->command('announcements:send-scheduled')->everyMinute();
```

## License

MIT License
