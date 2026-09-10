<?php
$c = new \App\Http\Controllers\ClassementController();
echo json_encode($c->index(request())->getData());
