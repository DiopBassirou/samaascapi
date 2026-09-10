<?php
$m = \App\Models\MatchGame::first();
echo json_encode($m->toArray(), JSON_PRETTY_PRINT);
