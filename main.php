<?php
require_once './vendor/autoload.php';
require_once './libs/router.php';
require_once './controllers/userAuth.controller.php';
require_once './controllers/customer.controller.php';
require_once './middlewares/auth.middleware.php';
require_once './controllers/user.controller.php';
require_once './controllers/wallet.controller.php';
require_once './controllers/invoice.controller.php';

$router = new Router(array('base_url' => '/api_manag/v1'));

$router->post('/user/auth/signup', signup());
$router->post('/user/auth/signin', login());

$router->get('/user/get', [auth_middleware(), get_user()]);
$router->post('/user/update', [auth_middleware(), update_user()]);


$router->post('/customer/add', [auth_middleware(), add_customer()]);
$router->post('/customer/update', [auth_middleware(), update_customer()]);
$router->post('/customer/delete', [auth_middleware(), delete_customer()]);
$router->get('/customer/get/:id', [auth_middleware(), get_customer()]);
$router->get('/customer/get', [auth_middleware(), get_customer()]);

$router->post('/wallet/add', [auth_middleware(), add_wallet()]);
$router->post('/wallet/update', [auth_middleware(), update_wallet()]);
$router->post('/wallet/delete', [auth_middleware(), delete_wallet()]);
$router->get('/wallet/get/:id', [auth_middleware(), get_wallets()]);
$router->get('/wallet/get', [auth_middleware(), get_wallets()]);


$router->post('/invoice/add', [auth_middleware(), add_invoice()]);
$router->post('/invoice/update/:id', [auth_middleware(), update_invoice()]);
$router->post('/invoice/delete', [auth_middleware(), delete_invoice()]);
$router->get('/invoice/get/:id', [auth_middleware(), get_invoice()]);
$router->get('/invoice/get', [auth_middleware(), get_invoice()]);


$router->dispatch();