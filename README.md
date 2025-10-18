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
9. Now, inside the container, run the command: "**composer install --optimize-autoloader**"
10. Run command "**cp .env.example .env**", to prepare config file
11. Run command to adjust folder owner: **chown -R www-data:www-data storage bootstrap/cache**
12. Run command to adjust correct permissions: **chmod -R 775 storage bootstrap/cache**
13. Run command "**php artisan key:generate**", to gen a Laravel unique key
14. Run command "**php artisan migrate**", to run database migrations
15. Go to URL [localhost:8081](http://localhost:8081)