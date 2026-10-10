# Frontend

## Local development with Vite

Start the Laravel API from `backend`:

```sh
php artisan serve
```

Then start the frontend from `frontend`:

```sh
npm install
npm run dev
```

The Vite development server proxies `/api` and `/broadcasting/auth` to Laravel
at `http://127.0.0.1:8000`.

## Run the built frontend through Laravel

From `frontend`, build the app:

```sh
npm install
npm run build
```

The build is written to `backend/public/frontend`. Run `php artisan serve` from
`backend`; Laravel serves the SPA at `/` and routes client-side URLs to it.

## Development deployment

Pushing to `main` runs the GitHub Actions workflow. It builds the frontend,
deploys the backend, and uploads the SPA to `backend/public/frontend` on the
configured development server. Production is not deployed by this workflow.
