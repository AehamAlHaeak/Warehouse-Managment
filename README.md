

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


