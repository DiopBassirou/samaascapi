<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixAccents extends Command
{
    protected $signature = 'fix:accents';
    protected $description = 'Nettoyer les accents casses dans la base de donnees';

    public function handle()
    {
        $map = [
            'Ã©' => 'e', 'Ã¨' => 'e', 'Ã‰' => 'E', 'Ã ' => 'a',
            'Ã¢' => 'a', 'Ãª' => 'e', 'Ã®' => 'i', 'Ã´' => 'o',
            'Ã»' => 'u', 'Ã§' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a',
            'â' => 'a', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ç' => 'c', 'É' => 'E',
        ];

        $this->info('=== 1. Table ASC ===');
        \App\Models\Asc::all()->each(function($a) use ($map) {
            $newNom = str_replace(array_keys($map), array_values($map), $a->nom);
            if ($newNom !== $a->nom) {
                $a->update(['nom' => $newNom]);
                $this->line("CORRIGE: {$a->code_unique} => {$newNom}");
            } else {
                $this->line("OK: {$a->code_unique} => {$a->nom}");
            }
        });

        $this->info("\n=== 2. Table poule_teams ===");
        \App\Models\PouleTeam::all()->each(function($pt) use ($map) {
            $newNom = str_replace(array_keys($map), array_values($map), $pt->nom_equipe ?? '');
            if ($newNom !== ($pt->nom_equipe ?? '')) {
                $pt->update(['nom_equipe' => $newNom]);
                $this->line("CORRIGE: #{$pt->id} => {$newNom}");
            } else {
                $this->line("OK: #{$pt->id} => {$pt->nom_equipe}");
            }
        });

        $this->info("\n=== 3. Lieux des matchs ===");
        \App\Models\MatchGame::whereNotNull('lieu')->get()->each(function($m) use ($map) {
            $newLieu = str_replace(array_keys($map), array_values($map), $m->lieu ?? '');
            if ($newLieu !== ($m->lieu ?? '')) {
                $m->update(['lieu' => $newLieu]);
                $this->line("CORRIGE: Match #{$m->id} lieu => {$newLieu}");
            }
        });

        $this->info("\nTermine !");
    }
}
