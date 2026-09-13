# Endpoints Reference

> **AUTO-GENERATED 2026-09-13** by `scripts/refresh-docs.mjs` from `php artisan route:list --json` — do not edit by hand, rerun the script (or the `/refresh-docs` skill) instead.

| Method | URI | Action | Middleware |
|---|---|---|---|
| GET | `/api/address/barangays/{code}/chain` | Api\AddressController@chain | throttle:public |
| GET | `/api/address/cities/{code}/barangays` | Api\AddressController@barangays | throttle:public |
| GET | `/api/address/provinces/{code}/cities` | Api\AddressController@citiesByProvince | throttle:public |
| GET | `/api/address/regions` | Api\AddressController@regions | throttle:public |
| GET | `/api/address/regions/{code}/cities` | Api\AddressController@citiesByRegion | throttle:public |
| GET | `/api/address/regions/{code}/provinces` | Api\AddressController@provinces | throttle:public |
| POST | `/api/auth/device` | Api\AuthController@device | auth:sanctum |
| POST | `/api/auth/firebase` | Api\AuthController@firebase | throttle:auth |
| POST | `/api/auth/logout` | Api\AuthController@logout | auth:sanctum |
| GET | `/api/health` | Closure |  |
| GET | `/api/notebook-types` | Api\NotebookController@types | auth:sanctum |
| GET | `/api/notebooks` | Api\NotebookController@index | auth:sanctum |
| POST | `/api/notebooks` | Api\NotebookController@store | auth:sanctum |
| GET | `/api/notebooks/{id}` | Api\NotebookController@show | auth:sanctum |
| PUT | `/api/notebooks/{id}` | Api\NotebookController@update | auth:sanctum |
| DELETE | `/api/notebooks/{id}` | Api\NotebookController@destroy | auth:sanctum |
| POST | `/api/notebooks/{id}/archive` | Api\NotebookController@archive | auth:sanctum |
| POST | `/api/notebooks/{id}/unarchive` | Api\NotebookController@unarchive | auth:sanctum |
| GET | `/api/profile` | Api\ProfileController@show | auth:sanctum |
| PUT | `/api/profile` | Api\ProfileController@update | auth:sanctum |
| GET | `/api/user` | Api\AuthController@me | auth:sanctum |
| GET | `/up` | Closure |  |
