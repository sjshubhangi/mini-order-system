# Mini Order & Catalogue Management System

Production-style Laravel 10 backend using **Passport** for API auth, **RBAC**, **AWS S3** for images with signed URLs, **Redis caching**, **queues** for async notifications, **request logging**, **rate limiting**, and **tests**.

## Setup
1. composer install
2. Configure .env (DB, Redis, AWS, Mail)
3. php artisan migrate
4. php artisan passport:install
5. php artisan queue:table && php artisan migrate
6. Run services:
   - php artisan serve
   - php artisan queue:work
   - redis-server (or ensure Redis service is running)

## Modules
- Users: register, login, me; roles: admin, vendor, customer
- Products: CRUD, S3 upload, signed URL, cached popular
- Orders: place order (customer), list/show/update (vendor/admin)

## Endpoints
- POST /api/register
- POST /api/login (throttle:login)
- GET /api/me
- GET /api/products
- GET /api/products/popular
- GET /api/products/{id}
- GET /api/products/{id}/image-url
- POST /api/products (vendor/admin)
- PATCH /api/products/{id} (vendor owner/admin)
- DELETE /api/products/{id} (vendor owner/admin)
- POST /api/orders (customer)
- GET /api/orders (vendor/admin)
- GET /api/orders/{id} (vendor/admin)
- PATCH /api/orders/{id}/status (vendor/admin)

## Notes
- Mail logs to storage/logs (local)
- Popular products cached 1h; invalidated on product/order mutations
- Rate limits: api=60/min, login=10/min

