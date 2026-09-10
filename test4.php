<?php
$c = new \App\Http\Controllers\ClassementController();
$data = $c->index(request())->getData(true);
foreach($data as $zone => $cats) {
    foreach($cats as $cat => $poules) {
        foreach($poules as $p => $teams) {
            foreach($teams as $t) {
                if($t['pts'] > 0 || $t['j'] > 0) {
                    echo $t['name'] . ' - Pts: ' . $t['pts'] . ' (V:' . $t['v'] . ' N:' . $t['n'] . ' D:' . $t['d'] . ")\n";
                }
            }
        }
    }
}
