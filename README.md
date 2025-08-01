

### composer install
### composer require tymon/jwt-auth
### php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
### php artisan jwt:secret

### change name file .env.example to .env   only
### go to storage/public and create folders ->
## cargos
## vehicles 
## users
## employes
## products 
### then activate: php artisan storage:link
### php artisan queue:table
### goto .env modefi the constant QUEUE_CONECTION=sync to database
### evere start to the project activate php atisan queue:work
### register in site https://pusher.com/ 
### composer require pusher/pusher-php-server
### add{ 
## BROADCAST_DRIVER=pusher
## PUSHER_APP_ID=your cluster
## PUSHER_APP_KEY=your key
## PUSHER_APP_SECRET=your secret
## PUSHER_APP_CLUSTER=your cluster} --->to env file
## php artisan config:cache
## php artisan vendor:publish --tag=laravel-notifications



