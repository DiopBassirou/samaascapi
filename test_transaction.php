<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

DB::beginTransaction();
$t = \App\Models\PouleTeam::first();
$old = $t->points;
$t->increment('points', 3);
$t2 = \App\Models\PouleTeam::find($t->id);
echo "Old: $old, New: {$t2->points}\n";
DB::rollBack();
