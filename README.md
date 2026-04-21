# <img src="public/assets/cloudify-icon.svg" alt="Cloudify Icon" width="36" /> Cloudify API

Cloudify API is a Laravel-based backend for authentication, API key management, and Cloudinary file operations.

## Live Frontend

- https://cloudify.meraj.pro

## Docs

- https://cloudify.meraj.pro/docs

## Features

- User authentication with Laravel Sanctum (`register`, `login`, `logout`, `me`)
- Google OAuth login flow with Socialite
- Per-user Cloudinary credential management
- Per-user public API key management
- File upload to Cloudinary via public API key
- File listing and deletion by logical `name` group
- Subdirectory hosting support (for example `/cloudify/api`)

## Tech Stack

- PHP `^8.3`
- Laravel `^13`
- Laravel Sanctum
- Laravel Socialite
- Cloudinary PHP SDK
- MariaDB/MySQL (default)

## Project Structure

- `routes/api.php`: API routes
- `routes/web.php`: Google OAuth endpoints and web fallback
- `app/Http/Controllers/Api/Auth`: auth controllers
- `app/Http/Controllers/Api/Keys`: key management controllers
- `app/Http/Controllers/Api/Cloudinary`: file upload/list/delete controller
- `database/migrations`: database schema
- `.htaccess`: root-to-`public` rewrite for shared hosting

## Installation

1. Clone the repository.
2. Install PHP dependencies.
3. Create `.env`.
4. Generate app key.
5. Configure database.
6. Run migrations.
7. Start the app.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Environment Configuration

At minimum, configure these values in `.env`:

```env
APP_NAME=Cloudify API
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cloudify_api
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:3000

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

For Sanctum SPA usage, also set:

```env
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
SESSION_DOMAIN=localhost
```

## API Overview

Base URL example (local): `http://localhost:8000/api`

Auth:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/logout` (Sanctum)
- `GET /api/user` (Sanctum)

Cloudinary Keys (Sanctum):

- `GET /api/keys/cloudinary`
- `POST /api/keys/cloudinary`
- `GET /api/keys/cloudinary/{id}`
- `PUT /api/keys/cloudinary/{id}`
- `DELETE /api/keys/cloudinary/{id}`

Public Keys (Sanctum):

- `GET /api/keys/public`
- `POST /api/keys/public`
- `GET /api/keys/public/{id}`
- `PUT /api/keys/public/{id}`
- `DELETE /api/keys/public/{id}`

Cloudinary Files (Bearer: public key):

- `POST /api/cloudinary/upload`
- `GET /api/cloudinary/files`
- `DELETE /api/cloudinary/files`

OAuth Routes:

- `GET /auth/google/redirect`
- `GET /auth/google/callback`

## Running Tests

```bash
php artisan test
```

## Shared Hosting (Subdirectory)

If you deploy under `/home/meraj/public_html/cloudify/api`:

1. Keep the repository root `.htaccess` file so requests are rewritten into `public/`.
2. Set the app URL with subdirectory path.

```env
APP_URL=https://your-domain.com/cloudify/api
```

3. Clear cached config/routes:

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Notes

- Keep sensitive secrets in `.env` only.
- Use HTTPS in production.
- Regenerate and rotate tokens/keys if exposed.
