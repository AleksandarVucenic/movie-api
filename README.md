# Movie Watchlist API

A REST API for managing a personal movie watchlist. Add movies by title or IMDb ID, track your watch status, leave ratings and notes, and filter your list however you like. Movie data is fetched from the OMDb API and stored locally so repeat lookups don't hit the external service.

## Tech stack

- **Laravel 11** with MySQL
- **Laravel Sanctum** for authentication
- **OMDb API** for movie data

## Requirements

- PHP 8.3+
- Composer
- MySQL
- An OMDb API key — register for free at [omdbapi.com/apikey.aspx](https://www.omdbapi.com/apikey.aspx). The free tier gives you 1,000 requests/day which is plenty for development.

## Getting started

```bash
git clone https://github.com/AleksandarVucenic/movie-api.git
cd movie-api

composer install

cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials and OMDb key:

```env
DB_DATABASE=movie_api
DB_USERNAME=root
DB_PASSWORD=

OMDB_API_KEY=your_key_here
```

```bash
php artisan migrate
php artisan serve
```

The API runs at `http://localhost:8000`.

## Running tests

```bash
php artisan test
```

---

## API reference

All watchlist endpoints require a `Bearer` token in the `Authorization` header.

### Auth

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/register` | Create an account |
| `POST` | `/api/login` | Log in, receive a token |
| `GET` | `/api/me` | Get the authenticated user |
| `POST` | `/api/logout` | Revoke the current token |

**Register body:**
```json
{
  "name": "James Kirk",
  "email": "james.kirk@starfleet.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Login body:**
```json
{
  "email": "james.kirk@starfleet.com",
  "password": "password123"
}
```

Login returns a token you'll use on all subsequent requests:
```json
{
  "data": {
    "user": { "id": 1, "name": "James Kirk", "email": "james.kirk@starfleet.com" },
    "token": "1|abc123..."
  }
}
```

---

### Watchlist

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/watchlist` | List your watchlist (paginated) |
| `POST` | `/api/watchlist` | Add a movie |
| `GET` | `/api/watchlist/{id}` | Get a single item |
| `PATCH` | `/api/watchlist/{id}` | Update status, rating, or notes |
| `DELETE` | `/api/watchlist/{id}` | Remove from watchlist |

**Add a movie** — provide either `imdb_id` or `title`:
```json
{ "imdb_id": "tt1375666" }
```
```json
{ "title": "Inception" }
```

**Update an item** — all fields optional:
```json
{
  "status": "watched",
  "rating": 9,
  "notes": "One of my all-time favourites."
}
```

Status values: `to_watch` · `watching` · `watched`

**Filtering and pagination:**
```
GET /api/watchlist?status=watched
GET /api/watchlist?search=inception
GET /api/watchlist?status=to_watch&search=nolan&per_page=5
```

---

## Postman collection

Import `Movie-Api.postman_collection.json` from the repo root. The login request automatically saves your token to a global variable (`current_token`) so all other requests are authenticated without any copy-pasting.

Quick start:
1. Import the collection
2. **Register** to create an account
3. **Login** — token is saved automatically
4. Use the watchlist endpoints

---

## Decisions and trade-offs

### Why Sanctum

Sanctum's token-based auth is the right fit for a stateless API like this. It's built into Laravel, has no external dependencies, and issues opaque tokens that are straightforward to revoke per-session. JWT would add complexity (refresh token logic, signing keys) without real benefit here. Session-based auth is for browser clients.

### Why OMDb

Simple HTTP API, free key, and it returns everything needed (title, year, genre, director, runtime, rating, poster) in one call with no pagination or auth complexity.

### Movie deduplication via repository

When a user adds a movie, we do a `firstOrCreate` on the `movies` table keyed by `imdb_id`. This means if two users add the same film, we store one movie record and two watchlist entries pointing to it. This is the right data model — movie metadata is shared, user-specific data (status, rating, notes) lives on the watchlist row.

### Interface for the movie API provider

`MovieApiProvidersInterface` means swapping OMDb for TMDB is a one-line change in the service container — the service layer never touches HTTP directly. This also makes testing clean: mock the interface, no HTTP calls in tests.

### Service layer

Business logic (fetching from OMDb, deduplicating the movie, creating the watchlist entry) lives in `WatchlistService`, not the controller. Controllers handle HTTP in and out; services handle what actually happens. Easy to test, easy to extend.

### Authorization via policies

Ownership checks (`user_id === auth user`) live in `WatchlistPolicy`, not scattered across controller methods. This keeps the authorization logic in one place and makes it easy to extend (e.g. admin roles later).

### What I skipped

**Soft deletes** — users removing an item from their watchlist is a deliberate action. There's no recovery flow needed here, so hard deletes keep things simple.

**Exhaustive validation tests** — the tests cover the business logic (conflict handling, authorization, 404s, the happy path) rather than testing every validation rule. The form requests are straightforward; testing that Laravel rejects a missing field isn't valuable.