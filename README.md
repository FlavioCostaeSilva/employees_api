## About this project

This is a job test, build with PHP (v8.3) and Laravel
- Flávio Costa e Silva - https://www.linkedin.com/in/flaviocostaesilva/

## How to install (Dev Environment)

1. Install Docker and Docker Compose in your machine
2. Clone this project
3. Go to project root folder
4. Run command "**docker compose build**"
5. Run command "**docker compose up -d**"
6. After finish, run command "**docker compose ps**"
7. Find the name from php-fpm container
8. Run the command "**docker exec -it <PHP_CONTAINER_NAME> /bin/bash**"
9. Run command "**php artisan migrate**", to run database migrations
10. Add Manager seeds with "**php artisan db:seed --class=ManagerSeeder**"
11. Add Employee seeds with "**php artisan db:seed --class=EmployeeSeeder**"
12. Create the test database **test_employee_api**
13. Go to URL [localhost:8081](http://localhost:8081)

### API Documentation

See here: https://documenter.getpostman.com/view/3303999/2sB3QQKoWD

### How to run tests

``
php artisan test
``

### How to check code coverage
``
php artisan test --coverage --coverage-html coverage-report
``

### How to activate Queue
``
php artisan queue:work redis --verbose
``

### Troubleshot
Try it first:

``
php artisan config:clear &&
php artisan cache:clear &&
composer dump-autoload
``

