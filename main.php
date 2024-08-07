<?php

require './libs/router.php';

$router = new Router(array('base_url' => '/api_manag/v1'));

$router->get('/', function($req, $res){
    
    $res::text('Hello world');
});

$router->dispatch();