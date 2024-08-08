<?php
require './vendor/autoload.php';
require './libs/router.php';
require './controllers/auth.controller.php';

$router = new Router(array('base_url' => '/api_manag/v1'));

$router->post('/user/auth/signup', signup());
$router->post('/user/auth/login', login());

$router->dispatch();