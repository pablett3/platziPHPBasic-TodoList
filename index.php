<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'Task.php';

use Aura\Router\RouterContainer;

use Relay\Relay;

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'platziphptodo',
    'username'  => 'precioJusto',
    'password'  => 'precioJusto',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

// Make this Capsule instance available globally via static methods... (optional)
$capsule->setAsGlobal();
// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
$capsule->bootEloquent();

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$loader = new Twig_Loader_Filesystem('.');
$twig = new \Twig_Environment($loader, array(
    'debug' => true,
    'cache' => false,
));

$router = new RouterContainer();
$map = $router->getMap();

$map->get('todo.list', '/platziPHPTaskList/', function ($request) use ($twig) {
    $tasks = Task::all();
    $response = new Zend\Diactoros\Response\HtmlResponse($twig->render('template.twig', [
        'tasks' => $tasks
    ]));
    return $response;
});

$map->post('todo.add', '/platziPHPTaskList/add', function ($request) {
    $data = $request->getParsedBody();

    $task = new Task();
    $task->description = $data['description'];
    $task->save();

    $response = new Zend\Diactoros\Response\RedirectResponse('/platziPHPTaskList/');
    return $response;
});

$map->get('todo.check', '/platziPHPTaskList/check/{id}', function ($request) {
    $id = $request->getAttribute('id');

    $task = Task::find($id);
    $task->done = true;
    $task->save();

    $response = new Zend\Diactoros\Response\RedirectResponse('/platziPHPTaskList/');
    return $response;
});

$map->get('todo.uncheck', '/platziPHPTaskList/uncheck/{id}', function ($request) {
    $id = $request->getAttribute('id');

    $task = Task::find($id);
    $task->done = false;
    $task->save();

    $response = new Zend\Diactoros\Response\RedirectResponse('/platziPHPTaskList/');
    return $response;
});

$map->get('todo.delete', '/platziPHPTaskList/delete/{id}', function ($request) {
    $id = $request->getAttribute('id');

    $task = Task::find($id);
    $task->delete();

    $response = new Zend\Diactoros\Response\RedirectResponse('/platziPHPTaskList/');
    return $response;
});

$relay = new Relay([
    new Middlewares\AuraRouter($router),
    new Middlewares\RequestHandler()
]);

$response = $relay->handle($request);

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}
echo $response->getBody();
