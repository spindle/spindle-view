<?php
require __DIR__ . '/../../vendor/autoload.php';

$view = new Spindle\View('template.phtml', __DIR__);
$view->title = 'example 1';
$view->data = range(1, 4);

/*
// or ...
$view->assign(array(
    'title' => 'example 1',
    'data' => range(1, 4),
));

// or ...
$title = 'example 1';
$data = range(1, 4);
$view->assign(compact('title', 'data'));

 */

echo $view->render();
