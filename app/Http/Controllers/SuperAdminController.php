<?php

namespace App\Http\Controllers;

use App\Models\Asc;
use App\Models\MatchGame;
use App\Models\User;
use App\Services\PlayerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SuperAdminController extends Controller
{
    public function getPendingAscs()
    {
        $ascs = Asc::with('president')->where('statut', 'EN_ATTENTE')->get();
        return response()->json($ascs);
    }

    public function getAllAscs()
    {
        // Retourne toutes les ASCs (utile pour voir les codes générés)
        $ascs = Asc::with('president')->orderBy('zone')->orderBy('nom')->get();
        return response()->json($ascs);
    }

    public function createAsc(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
        ]);

        $codeUnique = strtoupper(Str::random(8));
        
        $asc = Asc::create([
            'code_unique' => $codeUnique,
            'nom' => $request->nom,
            'zone' => $request->zone,
            'statut' => 'VALIDEE', // Déjà validée car créée par le super admin
            'is_active' => true,
        ]);

        return response()->json(['message' => 'ASC créée avec succès.', 'asc' => $asc], 201);
    }

    public function approveAsc(Request $request, string $codeUnique)
    {
        $asc = Asc::findOrFail($codeUnique);
        $asc->update([
            'statut' => 'VALIDEE',
            'is_active' => true,
        ]);

        // Promouvoir le créateur au rang de Président
        if ($asc->president_id) {
            User::where('id', $asc->president_id)->update(['role_id' => 2]);
        }

        return response()->json(['message' => 'ASC validée avec succès.']);
    }

    public function rejectAsc(Request $request, string $codeUnique)
    {
        $asc = Asc::findOrFail($codeUnique);
        $asc->update([
            'statut' => 'REJETEE',
            'is_active' => false,
        ]);

        // Le créateur n'est plus rattaché à cette ASC
        if ($asc->president_id) {
            User::where('id', $asc->president_id)->update(['asc_code' => null]);
        }

        return response()->json(['message' => 'ASC rejetée.']);
    }

    /**
     * Créer un match entre 2 équipes (Super Admin)
     * Peut aussi directement mettre le score si le match est déjà joué
     */
    public function createMatch(Request $request, \App\Services\StandingsCalculationService $standingsService)
    {
        $request->validate([
            'poule_team_a_id' => 'required|exists:poule_teams,id',
            'poule_team_b_id' => 'required|exists:poule_teams,id|different:poule_team_a_id',
            'date_match'      => 'required|date',
            'categorie'       => 'required|in:CADET,SENIOR',
            'lieu'            => 'nullable|string|max:255',
            'phase'           => 'nullable|string|max:100',
            'score_a'         => 'nullable|integer|min:0',
            'score_b'         => 'nullable|integer|min:0',
        ]);

        $teamA = \App\Models\PouleTeam::findOrFail($request->poule_team_a_id);
        $teamB = \App\Models\PouleTeam::findOrFail($request->poule_team_b_id);

        // Le match est enregistré du point de vue de l'équipe A
        $hasScore = $request->filled('score_a') && $request->filled('score_b');

        $match = \App\Models\MatchGame::create([
            'asc_code'      => $teamA->asc_code,
            'poule_team_id' => $teamB->id,
            'date_match'    => $request->date_match,
            'statut'        => $hasScore ? 'TERMINE' : 'A_VENIR',
            'score_asc'     => $request->score_a ?? 0,
            'score_adv'     => $request->score_b ?? 0,
            'categorie'     => $request->categorie,
            'lieu'          => $request->lieu,
            'phase'         => $request->phase ?? 'Phase de Groupes',
        ]);

        // Si le score est donné, recalculer le classement
        if ($hasScore) {
            $standingsService->updatePouleTeamStats($match);
        }

        return response()->json([
            'message' => $hasScore ? 'Match créé avec score final !' : 'Match programmé !',
            'match'   => $match->load(['opponent', 'events']),
        ], 201);
    }

    /**
     * Modifier le score d'un match (Super Admin - pas de restriction asc_code)
     */
    public function updateMatchScore(Request $request, $id, \App\Services\StandingsCalculationService $standingsService)
    {
        $match = \App\Models\MatchGame::findOrFail($id);

        $request->validate([
            'score_asc' => 'required|integer|min:0',
            'score_adv' => 'required|integer|min:0',
            'statut'    => 'nullable|string|in:A_VENIR,EN_COURS,MI_TEMPS,DEUXIEME_MI_TEMPS,TERMINE',
        ]);

        $match->update([
            'score_asc' => $request->score_asc,
            'score_adv' => $request->score_adv,
            'statut'    => $request->statut ?? 'TERMINE',
        ]);

        if (($request->statut ?? 'TERMINE') === 'TERMINE') {
            $standingsService->updatePouleTeamStats($match);
        }

        return response()->json([
            'message' => 'Score mis à jour !',
            'match'   => $match->load(['opponent', 'events']),
        ]);
    }

    /**
     * Nommer un utilisateur Président d'une ASC
     */
    public function assignPresident(Request $request)
    {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'asc_code' => 'required|exists:ascs,code_unique',
        ]);

        $presidentRoleId = \App\Models\Role::where('nom', 'President')->first()?->id ?? 4;

        $user = User::findOrFail($request->user_id);
        $user->update([
            'role_id'  => $presidentRoleId,
            'asc_code' => $request->asc_code,
        ]);

        // Met à jour l'ASC aussi
        Asc::where('code_unique', $request->asc_code)->update([
            'president_id' => $user->id,
        ]);

        return response()->json([
            'message' => "{$user->prenom} {$user->nom} est maintenant Président de l'ASC.",
        ]);
    }

    /**
     * Uploader le logo d'une ASC spécifique (Super Admin)
     */
    public function uploadAscLogo(Request $request, string $codeUnique)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $asc = Asc::where('code_unique', $codeUnique)->firstOrFail();

        // Supprimer l'ancien logo
        if ($asc->logo_path && Storage::disk('public')->exists($asc->logo_path)) {
            Storage::disk('public')->delete($asc->logo_path);
        }

        // Sauvegarder le nouveau
        $path = $request->file('logo')->store('logos', 'public');
        $asc->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo mis à jour pour ' . $asc->nom,
            'logo_url' => url(Storage::url($path)),
        ]);
    }

    /**
     * Ajouter un joueur à une ASC (Super Admin)
     */
    public function addPlayer(Request $request, string $codeUnique, PlayerService $playerService)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:255',
            'poste' => 'required|string|max:100',
        ]);

        $asc = Asc::where('code_unique', $codeUnique)->firstOrFail();

        $player = $playerService->createPlayer($data, $asc->code_unique);

        return response()->json([
            'message' => 'Joueur ajouté avec succès',
            'player' => $player
        ], 201);
    }

    /**
     * Mettre à jour un match (date, lieu, phase, score_asc, score_adv)
     */
    public function updateMatch(Request $request, $id)
    {
        $match = MatchGame::findOrFail($id);

        $request->validate([
            'date_match' => 'nullable|date',
            'lieu' => 'nullable|string|max:255',
            'phase' => 'nullable|string|max:255',
            'score_asc' => 'nullable|integer',
            'score_adv' => 'nullable|integer',
            'asc_code' => 'nullable|string|max:100',
            'poule_team_id' => 'nullable|integer',
            'categorie' => 'nullable|string|max:100',
            'statut' => 'nullable|string|max:50',
        ]);

        if ($request->has('date_match')) $match->date_match = $request->date_match;
        if ($request->has('lieu')) $match->lieu = $request->lieu;
        if ($request->has('phase')) $match->phase = $request->phase;
        if ($request->has('score_asc')) $match->score_asc = $request->score_asc;
        if ($request->has('score_adv')) $match->score_adv = $request->score_adv;
        if ($request->has('asc_code')) $match->asc_code = $request->asc_code;
        if ($request->has('poule_team_id')) $match->poule_team_id = $request->poule_team_id;
        if ($request->has('categorie')) $match->categorie = $request->categorie;
        if ($request->has('statut')) $match->statut = $request->statut;

        $match->save();

        return response()->json([
            'message' => 'Match mis à jour avec succès.',
            'match' => $match->load(['opponent', 'events'])
        ]);
    }

    /**
     * Supprimer un match
     */
    public function deleteMatch($id)
    {
        $match = MatchGame::findOrFail($id);
        $match->delete();

        return response()->json([
            'message' => 'Match supprimé avec succès.'
        ]);
    }

    /**
     * Récupérer tous les matchs (Super Admin)
     */
    public function getAllMatches(Request $request)
    {
        $matches = MatchGame::with(['asc', 'opponent'])->orderBy('date_match', 'desc')->get();
        return response()->json($matches);
    }
}
