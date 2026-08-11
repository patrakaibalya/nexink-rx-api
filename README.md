- create a fresh hash in Laravel Tinker:
- php artisan tinker
- Hash::make('password');
- Example output:=> "$2y$12$abcxyz....."
- debuging
- dd(Hash::check( 'password', '$2y$12$kY8qdX5mqioUjq0A4c/HKuSCzqyOisChUtqwfSjdMYF3jfT4TvbwS' ));

## truncate table
- SET FOREIGN_KEY_CHECKS = 0;
- TRUNCATE TABLE order_items; TRUNCATE TABLE orders; TRUNCATE TABLE customers;
- SET FOREIGN_KEY_CHECKS = 1;

## tinker
- php artisan tinker

- $doctor = App\Models\DoctorAccount::find(1);

$database = $doctor->database;

config([
    'database.connections.doctor' => [
        'driver' => 'mysql',
        'host' => $database->database_host,
        'port' => $database->database_port,
        'database' => $database->database_name,
        'username' => $database->database_username,
        'password' => $database->database_password,
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
    ],
]);

DB::purge('doctor');
DB::reconnect('doctor');

## command 
cd /var/www/nexink-rx-api
- php artisan migrate:fresh
-   php artisan optimize:clear
    php artisan route:clear
    php artisan config:clear
    php artisan cache:clear
    php artisan route:list
    php artisan serve


- sudo chown -R ubuntu:www-data storage bootstrap/cache
- sudo chmod -R 775 storage bootstrap/cache
- sudo nano /etc/nginx/sites-available/nexink-rx-api
- sudo ln -s /etc/nginx/sites-available/nexink-rx-api /etc/nginx/sites-enabled/nexink-rx-api
- sudo rm /etc/nginx/sites-enabled/default
- sudo nginx -t
- sudo systemctl restart nginx
- sudo systemctl status nginx
- check local public ip for AWS in terminal ->curl https://checkip.amazonaws.com


## Truncate

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE nexink_master.doctor_databases;
TRUNCATE TABLE nexink_master.doctor_accounts;

SET FOREIGN_KEY_CHECKS = 1;
