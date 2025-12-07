Mini Order & Catalogue Management System

A production-style Laravel 10 backend implementing modular REST APIs with Passport authentication, role-based access control (RBAC), AWS S3 image storage with signed URLs, Redis caching, queues for async notifications, request logging, rate limiting, and basic tests.

------------------------------------------------------------
🚀 Setup Instructions

1. Clone & Install
   git clone https://github.com/sjshubhangi/mini-order-system.git
   cd mini-order-system
   composer install
   cp .env.example .env
   php artisan key:generate

2. Configure .env

Application
APP_NAME=MiniOrderSystem
APP_ENV=local
APP_KEY=base64:your_generated_key
APP_DEBUG=true
APP_URL=http://localhost:8000

Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_order
DB_USERNAME=root
DB_PASSWORD=

AWS S3
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=ap-southeast-2
AWS_BUCKET=mini-order-catalogue
AWS_USE_PATH_STYLE_ENDPOINT=false

SMTP Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail_address@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_gmail_address@gmail.com
MAIL_FROM_NAME="Mini Order System"

Queue & Cache
QUEUE_CONNECTION=database   # use redis in production
CACHE_DRIVER=redis          # file for local, redis for production

3. Migrate & Seed
   php artisan migrate
   php artisan db:seed
   php artisan passport:install
   php artisan queue:table && php artisan migrate

4. Run Services
   php artisan serve
   php artisan queue:work
   redis-server   # or ensure Redis service is running

------------------------------------------------------------
🧱 Architecture Overview

- Users Module
  Register, login, profile (/me)
  Roles: admin, vendor, customer

- Products Module
  CRUD operations
  Image upload to AWS S3 with UUID keys
  Signed URL generation for secure access
  Popular products endpoint cached with Redis

- Orders Module
  Customers place orders
  Stock decremented atomically with lockForUpdate
  Vendors/Admins list, show, update orders
  Notifications dispatched via queue

- Notifications
  SendOrderNotification job sends email via SMTP
  Asynchronous processing with Laravel queues

- Logging & Rate Limiting
  All API requests logged (file/DB/CloudWatch-ready)
  Rate limits: api=60/min, login=10/min

------------------------------------------------------------
☁️ AWS Setup Notes

1. Create S3 bucket: mini-order-catalogue
2. Region: ap-southeast-2
3. Create IAM user with AmazonS3FullAccess policy
4. Update .env with credentials
5. Test upload:
   php artisan tinker
   >>> Storage::disk('s3')->put('test.txt', 'Hello World');

------------------------------------------------------------
🧵 Queue + Cache Explanation

Queue
- Connection: database (Redis in production)
- Job: SendOrderNotification
- Run worker:
  php artisan queue:work

Cache
- Driver: redis (file for local)
- Popular products cached for 1 hour
- Cache invalidated on product/order mutations

------------------------------------------------------------
📬 Email Notifications

- Configured via SMTP (Gmail App Password)
- Sends order confirmation emails to customers asynchronously
- Rate-limited on Gmail → throttle jobs or upgrade to Google Workspace

------------------------------------------------------------
📊 Database Design (ERD)

This system uses Laravel 10 with Passport authentication and queues. The database includes core entities, job tracking, and OAuth tables.

Entities:
- users
  - id (PK), name, email, password, role (admin, vendor, customer), timestamps
- products
  - id (PK), name, description, price, stock, image_key, vendor_id (FK → users.id), deleted_at, timestamps
- orders
  - id (PK), product_id (FK → products.id), customer_id (FK → users.id), quantity, status (pending, completed), timestamps
- jobs
  - id (PK), queue, payload, attempts, reserved_at, available_at, created_at
- failed_jobs
  - id (PK), uuid, connection, queue, payload, exception, failed_at
- password_reset_tokens
  - email, token, created_at
- personal_access_tokens
  - id (PK), tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, timestamps

Passport OAuth Tables:
- oauth_clients
  - id (PK), user_id (FK → users.id), name, secret, redirect, personal_access_client, password_client, revoked, timestamps
- oauth_access_tokens
  - id (PK), user_id (FK → users.id), client_id (FK → oauth_clients.id), name, scopes, revoked, timestamps, expires_at
- oauth_refresh_tokens
  - id (PK), access_token_id (FK → oauth_access_tokens.id), revoked, expires_at
- oauth_auth_codes
  - id (PK), user_id (FK → users.id), client_id (FK → oauth_clients.id), scopes, revoked, expires_at
- oauth_personal_access_clients
  - id (PK), client_id (FK → oauth_clients.id), timestamps

Relationships:
- users → products (vendor_id)
- users → orders (customer_id)
- products → orders (product_id)
- users → oauth_clients
- oauth_clients → oauth_access_tokens
- oauth_access_tokens → oauth_refresh_tokens
- users → oauth_auth_codes
- oauth_clients → oauth_auth_codes

SQL Dump:
- For database schema reference, the full SQL dump has been included in the repository.  
You can find it here: /docs/mini_order.sql


------------------------------------------------------------
📚 API Documentation

- Full API endpoints are provided in the Postman collection (/docs/mini-order.postman_collection.json)
- Import into Postman to test authentication, product management, and order workflows

------------------------------------------------------------
🧪 Testing

- Unit tests included for:
  One controller
  One service/class
  One helper/utility function

Run tests:
   php artisan test

------------------------------------------------------------
👩‍💻 Author

Shubhangi — Senior Software Engineer
GitHub: https://github.com/sjshubhangi
