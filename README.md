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
- php artisan doctor:migrate 1
- php artisan migrate:fresh
- php artisan doctor:migrate 1
-   php artisan optimize:clear
    php artisan route:clear
    php artisan config:clear
    php artisan cache:clear
    php artisan route:list
    php artisan queue:restart
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

## fillzilla file access file

sudo chown -R www-data:www-data /var/www/nexink-rx-api/storage/app/private/doctor-1
sudo chown -R www-data:www-data /var/www/nexink-rx-api/storage/app/private/clinic-1

## Filezilla after ->change and see in filezilla

sudo find /var/www/nexink-rx-api/storage/app/private/doctor-1 -type d -exec chmod 700 {} \;
sudo find /var/www/nexink-rx-api/storage/app/private/doctor-1 -type f -exec chmod 600 {} \;

sudo find /var/www/nexink-rx-api/storage/app/private/clinic-1 -type d -exec chmod 700 {} \;
sudo find /var/www/nexink-rx-api/storage/app/private/clinic-1 -type f -exec chmod 600 {} \;

## Filezilla verify 

ls -lad /var/www/nexink-rx-api/storage/app/private/doctor-1
ls -lad /var/www/nexink-rx-api/storage/app/private/clinic-1


## git pull
ubuntu@ip-172-31-12-116:/var/www/nexink-rx-api$ 

## Full Doctor Flow

┌─────────────────────────────┐
│ 1. Doctor Login             │
│ POST /auth/doctor/login     │
└──────────────┬──────────────┘
               ↓
┌─────────────────────────────┐
│ 2. Doctor Dashboard / Home  │
│ GET dashboard data          │
└──────────────┬──────────────┘
               ↓
┌─────────────────────────────┐
│ 3. Patient Management       │
│ Search existing patient     │
│ OR create new patient       │
└──────────────┬──────────────┘
               ↓
        Is patient new?
          ┌────┴────┐
         YES       NO
          ↓         ↓
 POST /patients   Select Patient
          └────┬────┘
               ↓
        ┌──────────────────┐
        │ How did patient  │
        │ come to doctor?  │
        └────────┬─────────┘
                 │
        ┌────────┴─────────┐
        ↓                  ↓
┌───────────────┐   ┌────────────────┐
│ WALK-IN NOW   │   │ SCHEDULED       │
│ Patient here  │   │ Patient booking │
└───────┬───────┘   └────────┬───────┘
        ↓                    ↓
 POST /queues         POST /appointments
        ↓                    ↓
 Waiting in Queue       Appointment Created
        ↓                    ↓
 Queue Status          Wait until appointment
 changes                      date/time
        ↓                    ↓
 Called / Ready             Check-in
        ↓                    ↓
        └──────────┬─────────┘
                   ↓
        ┌─────────────────────┐
        │ 4. Start Visit      │
        │ / Consultation      │
        └──────────┬──────────┘
                   ↓
        ┌─────────────────────┐
        │ 5. Consultation     │
        │ Doctor examines     │
        │ patient             │
        └──────────┬──────────┘
                   ↓
         ┌─────────┴──────────┐
         ↓                    ↓
┌─────────────────┐  ┌──────────────────┐
│ Prescription    │  │ Investigation    │
│ Medicines       │  │ Lab / Tests      │
└────────┬────────┘  └────────┬─────────┘
         ↓                    ↓
 Add Medicine           Add Test Items
         ↓                    ↓
 Save / Update           Save / Update
         ↓                    ↓
 Finalize                Order Investigation
         └─────────┬──────────┘
                   ↓
        ┌─────────────────────┐
        │ 6. Consultation     │
        │ Completed           │
        └─────────────────────┘

## Complete End-to-End Flow

                        DOCTOR LOGIN
                             ↓
                        DASHBOARD
                             ↓
                     SEARCH PATIENT
                             ↓
                      Patient Found?
                       ↙          ↘
                     YES          NO
                      ↓            ↓
                 Select       CREATE PATIENT
                                   ↓
                    └──────────────┘
                             ↓
                   What does patient need?
                             ↓
              ┌──────────────┴──────────────┐
              ↓                             ↓
        WALK-IN NOW                    FUTURE BOOKING
              ↓                             ↓
        CREATE QUEUE                CREATE APPOINTMENT
              ↓                             ↓
          WAITING                     SCHEDULED
              ↓                             ↓
          CALLED                  Patient arrives
              ↓                             ↓
              │                       CREATE QUEUE
              │                             ↓
              └──────────────┬──────────────┘
                             ↓
                       CONSULTATION
                             ↓
                         CREATE VISIT
                             ↓
                  ┌──────────┴──────────┐
                  ↓                     ↓
             PRESCRIPTION          INVESTIGATION
                  ↓                     ↓
             Add Medicines          Add Tests
                  ↓                     ↓
                Update               Update
                  ↓                     ↓
              FINALIZE                ORDER
                  └──────────┬──────────┘
                             ↓
                   CONSULTATION COMPLETE

                 ABOUT ME
                     │
                     ▼
              Doctor writes
                     │
                     ▼
             Google ML Kit OCR
                     │
                     ▼
           raw_recognized_text
                     │
                     ▼
              Doctor corrects
                     │
                     ▼
          final_corrected_text
                     │
                     ▼
               Doctor approves
                     │
                     ▼
        Save to MySQL + PB File
                     │
                     ▼
       ProcessDoctorHandwritingMemory
                     │
                     ▼
                   Qdrant

                    

                 PRESCRIPTION
                     │
                     ▼
              Doctor writes
                     │
                     ▼
             Google ML Kit OCR
                     │
                     ▼
           raw_recognized_text
                     │
                     ▼
              Review Screen
                     │
            ┌────────┴────────┐
            │                 │
            ▼                 ▼
        Edit manually     Re-review
            │                 │
            │                 ▼
            │          Memory Search
            │                 │
            │                 ▼
            │          AI Correction
            │                 │
            └────────► corrected_text
                          │
                          ▼
                    Doctor reviews
                          │
                   ┌──────┴──────┐
                   │             │
                   ▼             ▼
                Reject         Approve
                                 │
                                 ▼
                    Save PB + MySQL record
                                 │
                                 ▼
                  ProcessDoctorHandwritingMemory
                                 │
                                 ▼
                               Qdrant


## Run this command in server 

echo "========== MEMORY =========="
free -h

echo
echo "========== CPU =========="
nproc
uptime
lscpu | grep -E "CPU\(s\)|Model name"

echo
echo "========== DISK =========="
df -h
lsblk

echo
echo "========== TOP MEMORY PROCESSES =========="
ps aux --sort=-%mem | head -15

echo
echo "========== TOP CPU PROCESSES =========="
ps aux --sort=-%cpu | head -15

echo
echo "========== DOCKER =========="
docker ps
docker stats --no-stream

echo
echo "========== DOCKER DISK USAGE =========="
docker system df

echo
echo "========== SYSTEMD FAILED SERVICES =========="
systemctl --failed

echo
echo "========== RAM / DISK QUICK SUMMARY =========="
echo "RAM:"
free -h | grep Mem
echo "Root Disk:"
df -h /
