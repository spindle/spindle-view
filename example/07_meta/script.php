<?php
require __DIR__ . '/../../vendor/autoload.php';

$view = new Spindle\View('template.phtml', __DIR__);

echo $view->render();
