<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'roles',
    'ascs',
    'users',
    'players',
    'poules',
    'poule_teams',
    'match_games',
    'match_events',
    'announcements',
    'transactions',
    'player_notes',
    'convocations',
    'app_devices',
];

$sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) continue;

    $rows = DB::table($table)->get();
    if ($rows->isEmpty()) continue;

    $sql .= "-- Données de la table : {$table}\n";
    $sql .= "TRUNCATE TABLE `{$table}`;\n";

    foreach ($rows as $row) {
        $data = (array) $row;
        $columns = array_keys($data);
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        
        $escapedValues = array_map(function($val) {
            if (is_null($val)) return "NULL";
            if (is_bool($val)) return $val ? '1' : '0';
            if (is_numeric($val) && !is_string($val)) return $val;
            return "'" . addslashes((string)$val) . "'";
        }, array_values($data));

        $sql .= "INSERT INTO `{$table}` (" . implode(', ', $escapedColumns) . ") VALUES (" . implode(', ', $escapedValues) . ");\n";
    }
    $sql .= "\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents(__DIR__ . '/backup_local.sql', $sql);
echo "Export termine avec succes dans backup_local.sql !\n";
