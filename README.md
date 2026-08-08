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

## command 

- php artisan migrate:fresh
-   php artisan optimize:clear
    php artisan route:clear
    php artisan config:clear
    php artisan cache:clear
    php artisan route:list
    php artisan serve
