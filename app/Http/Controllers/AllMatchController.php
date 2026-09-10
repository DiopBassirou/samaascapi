<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AllMatchController extends Controller
{
    /**
     * Retourne tous les matchs de la plateforme, groupés par date.
     * Filtrable par zone et catégorie.
     * 
     * GET /api/all-matches?zone=Zone 1&categorie=SENIOR
     */
    public function index(Request $request)
    {
        $query = MatchGame::query()
            ->orderBy('date_match', 'desc');

        // Filtrer par zone si spécifié
        if ($request->has('zone') && $request->zone) {
            $zone = $request->zone;
            // Les matchs sont liés aux ASC via asc_code, on filtre via la zone de l'ASC
            $query->whereHas('asc', function($q) use ($zone) {
                $q->where('zone', $zone);
            });
        }

        // Filtrer par catégorie si spécifié
        if ($request->has('categorie') && $request->categorie) {
            $query->where('categorie', $request->categorie);
        }

        $matches = $query->with(['asc', 'opponent.asc', 'opponent.poule'])->get();

        // Grouper par date
        $grouped = [];
        $today = Carbon::today();

        foreach ($matches as $match) {
            $matchDate = Carbon::parse($match->date_match)->startOfDay();
            $diff = $today->diffInDays($matchDate, false);

            if ($diff == 0) {
                $label = "Aujourd'hui";
            } elseif ($diff == -1) {
                $label = 'Hier';
            } elseif ($diff == -2) {
                $label = 'Avant-hier';
            } elseif ($diff == 1) {
                $label = 'Demain';
            } elseif ($diff == 2) {
                $label = 'Après-demain';
            } elseif ($diff > 0) {
                $label = $matchDate->translatedFormat('d M');
            } else {
                $label = $matchDate->translatedFormat('d M');
            }

            $dateKey = $matchDate->format('Y-m-d');

            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [
                    'label' => $label,
                    'date' => $dateKey,
                    'matches' => [],
                ];
            }

            $ascName = $match->asc ? $match->asc->nom : 'Équipe A';
            
            $adversaireName = 'Adversaire';
            $adversaireLogo = null;

            if ($match->opponent) {
                if ($match->opponent->asc) {
                    $adversaireName = $match->opponent->asc->nom;
                    $adversaireLogo = $match->opponent->asc->logo_url;
                } else {
                    $adversaireName = $match->opponent->nom_equipe;
                }
            } elseif ($match->adversaire_code) {
                $advAsc = \App\Models\Asc::where('code_unique', $match->adversaire_code)->first();
                if ($advAsc) {
                    $adversaireName = $advAsc->nom;
                    $adversaireLogo = $advAsc->logo_url;
                }
            } elseif ($match->adversaire_nom) {
                $adversaireName = $match->adversaire_nom;
            }

            $pouleName = $match->phase ?? 'Phase de Groupes';
            if ($pouleName === 'Phase de Groupes' && $match->opponent && $match->opponent->poule) {
                $pouleName = $match->opponent->poule->nom;
            }

            $grouped[$dateKey]['matches'][] = [
                'id' => $match->id,
                'home' => $ascName,
                'home_logo' => $match->asc ? $match->asc->logo_url : null,
                'away' => $adversaireName,
                'away_logo' => $adversaireLogo,
                'score_home' => $match->score_asc,
                'score_away' => $match->score_adv,
                'statut' => $match->statut,
                'categorie' => $match->categorie ?? 'SENIOR',
                'poule' => $pouleName,
                'zone' => $match->asc ? $match->asc->zone : '',
                'date_match' => $match->date_match,
                'lieu' => $match->lieu,
            ];
        }

        // Convertir en array indexé et trier par date (plus récent d'abord)
        $dates = array_values($grouped);

        // Récupérer les zones et catégories disponibles
        $zones = \App\Models\Asc::where('is_active', true)
            ->distinct()
            ->pluck('zone')
            ->filter()
            ->values();

        return response()->json([
            'dates' => $dates,
            'zones' => $zones,
            'categories' => ['SENIOR', 'CADET'],
        ]);
    }
}
