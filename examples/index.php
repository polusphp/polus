<?php
date_default_timezone_set('UTC');
define('__ROOT__', dirname(__DIR__));
require __ROOT__.'/vendor/autoload.php';

use Polus\App;

$appNs = 'Polus\Test';

$urls = [
    '/hello/world',
    '/hello/named_route',
    '/yo',
];

foreach ($urls as $url) {
    echo "------ PATH: ".$url." ------ \n\n";
    $_SERVER['REQUEST_URI'] = $url;
    $app = new App($appNs);
    $app->registerController('Polus\Test\Controller\Test');

    $app->run();
    unset($app);
    echo "\n--------------\n\n";
}
