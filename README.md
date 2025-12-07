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
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@miniorder.local
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

- Configured via SMTP (Mailtrap for testing)
- Sends order confirmation emails to customers asynchronously
- Rate-limited on free Mailtrap plans → throttle jobs or upgrade plan

------------------------------------------------------------
📊 Database Design (ERD)

Users
- id, name, email, password, role (admin, vendor, customer)

Products
- id, name, description, price, stock, image_key, vendor_id

Orders
- id, product_id, customer_id, quantity, status (pending, confirmed)

Jobs
- Laravel queue jobs table

Cache
- Redis/file cache store

Relationships:
- users (vendor) → products
- users (customer) → orders
- products → orders

------------------------------------------------------------
📚 API Documentation

- Full API endpoints are provided in the Postman collection (/mini-order-system/mini-order.postman_collection.json)
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
