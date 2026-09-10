<?php
$c = new \App\Http\Controllers\ClassementController();
$data = $c->index(request())->getData(true);
echo json_encode($data);
