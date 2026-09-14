<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$req = Illuminate\Http\Request::create('/api/classement', 'GET');
$res = app()->handle($req);
echo substr($res->getContent(), 0, 1000);
