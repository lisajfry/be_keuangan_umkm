# Requirement 

 php                ^8.2.23   
 composer           ^2.7.9
 laravel/framework  ^12.0   
 laravel/sanctum    ^4.2    
 laravel/tinker     ^2.10.1 
 maatwebsite/excel  ^3.1    



# 1. Clone Repository

```bash
git clone https://github.com/lisajfry/be_keuangan_umkm.git
cd be_keuangan_umkm
```
# 2. Instalasi & Menjalankan be_admin
menginstall semua package otomatis
Masuk ke folder:
```bash
cd be_admin
composer install
```


## 2.1 Import database
Download disini
```bash
https://drive.google.com/drive/folders/1mgDC93Oz2xcs2fYw-QaSy-PUuPONL8FI?usp=sharing
```

## 2.2 Konfigurasi .env ADMIN

Isi file .env seperti berikut (sesuaikan database):

```bash
APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:bBVdXDhH4kreXgb2fW6gcRg1/xI3jK70nTQnPtbEebo=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laporan_keuangan
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

UMKM_API_URL=http://127.0.0.1:8001/api
```

## 2.3 Install package
```bash
composer require maatwebsite/excel
```

## 2.4 Jalankan Server Admin
php artisan serve --host=127.0.0.1 --port=8000


# 3. Installasi & Menjalankan be_umkm

Masuk ke folder:
```bash
cd be_umkm
composer install
```


## 3.1 Konfigurasi .env UMKM

Isi file .env seperti berikut (sesuaikan database):

```bash
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laporan_keuangan
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```
## 3.2 Install package
```bash
composer require maatwebsite/excel
```

## 3.3 Jalankan Server Admin
php artisan serve --host=127.0.0.1 --port=80001




