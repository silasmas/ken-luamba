# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Ken Luamba backend — a Laravel 13 + Filament 5 API and back-office for a book publishing/e-commerce platform: catalog of books/authors, cart & orders, shipping, mobile-money/card payments (FlexPay), a digital library with signed streaming and progress tracking, book-launch event invitations (RSVP), and a courier/delivery app API.

Note: `frontend/` in this repo only contains a stray `node_modules` directory (no source, no package.json) — it is not a working app. The real, separate frontend is the Next.js repo `kenluamba_front` (deployed independently, communicates with this backend over `NEXT_PUBLIC_API_URL`). Treat this repo as backend-only.

## Commands

Install / local setup:
```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --port=8001
```
Create a Filament admin user: `php artisan make:filament-user`

Dev (server + queue + logs + vite concurrently): `composer dev`

Build/setup in one shot: `composer setup` (installs deps, migrates, npm install/build)

Tests:
```
composer test                       # config:clear then artisan test
php artisan test
php artisan test --filter=TestName  # single test
```

Local URLs: API `http://localhost:8001/api/v1`, admin `http://localhost:8001/admin`, health check `/api/v1/health`. Production: `https://admin.kenluamba.com` (same paths), document root must be `public/`.

## Architecture

- **Admin panel**: Filament 5 at `/admin`, resources in `app/Filament/Resources/*` — one per domain entity (Books, Authors, Orders, Payments, Deliveries, Events, Invitations, InvitationDispatchLogs, PickupPoints, ShippingCities/Zones/Settings, PricingPeriods, QuantityDiscounts, BookReviews, BookReleaseSubscriptions, ContactSettings, ShopSettings, AdminAppearanceSettings, Users). Roles/permissions via `bezhansalleh/filament-shield` + `spatie/laravel-permission`.
- **API** (`routes/api.php`, all under `/api/v1`): public catalog endpoints (`/books`, `/authors/{slug}`, `/shop/config`, `/shipping/quote`), OTP-based auth (`/auth/register`, `/login`, `/verify-otp`, throttled), session-based `cart/*` (optional-Sanctum), authenticated `/orders`, `/wishlist`, `/library/*` (digital library — stream/file/progress, all Sanctum-protected), and a `courier/*` group for delivery scanning/acceptance/confirmation. Auth: Laravel Sanctum, tokens via `auth:sanctum` middleware; some file/stream routes use Laravel's `signed` URL middleware instead (`/library/stream-file/{accessId}/{userId}`, `/invitations/{token}/share-image.png`).
- **Payments**: `app/Services/FlexPay/{FlexPayCardService,FlexPayMobileService}` integrate the FlexPay gateway (mobile money + card) for the DRC market; webhook at `/payments/flexpay-callback`, status polling at `/payments/status`, card return redirect at `/payments/card-return`.
- **Digital library / anti-piracy**: `DigitalAccess`, `DigitalAccessLog`, `DigitalAccessShare`, `DigitalAccessShareProgress`, `DigitalReadingProgress` models plus `DigitalAccessService` (used directly in `routes/web.php` for the signed `/digital/stream/{accessId}/{userId}` route) — controls who can stream a purchased ebook, tracks reading progress, and supports sharing access via tokenized links (`DigitalShareController`, `/shares/{token}`).
- **Events/Invitations module**: `Event`/`Invitation`/`InvitationDispatchLog` models. Admin creates events (book launches) and invites guests via email/WhatsApp (`wa.me` links)/SMS/shareable link; public RSVP flow at `/api/v1/invitations/{token}` (GET) and `/rsvp` (POST), rendered by the frontend at `{FRONTEND_URL}/invitation/{token}`. SMS sending via `app/Services/Sms/KecelSmsService` (+ `SmsMessageAnalyzer`).
- **Shipping & delivery**: `ShippingZone`/`ShippingZoneCommune`/`ShippingCity`/`ShippingSetting`/`PickupPoint` model zone-based shipping cost/quote logic; `Delivery`/`DeliveryProof`/`QrCode` support a courier app flow (accept delivery, scan QR, confirm handoff) under `courier/*` routes.
- **Deploy tooling**: `app/Services/Deploy/DeployService.php` + `DeployController` expose HTTP-triggered deploy actions (`migrate`, `seed`, `setup`, `shield`, `storage`) via `?secret=DEPLOY_SECRET` query param on `/` — used for SSH-less deploys on Hostinger. Also exposed in-admin under **Système → Déploiement** (super_admin only).
- **Key env vars**: `APP_URL`, `FILESYSTEM_DISK` (must be `public` for catalog/avatar images), `DEPLOY_SECRET`, `FRONTEND_URL` (CORS + invitation links), `SANCTUM_STATEFUL_DOMAINS`, `DB_*`.
- **Docs**: `docs/CAHIER-DES-CHARGES.md` (spec/requirements, French), `docs/API.md` (API reference).
