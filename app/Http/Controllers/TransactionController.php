<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Asc;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $transactions = Transaction::where('asc_code', $request->user()->asc_code)
            ->orderBy('date_transaction', 'desc')
            ->get();

        $solde = $this->transactionService->calculateSolde($request->user()->asc_code);

        return response()->json([
            'solde' => $solde,
            'transactions' => $transactions
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:ENTREE,SORTIE',
            'montant' => 'required|numeric|min:0',
            'motif' => 'required|string|max:255',
            'date_transaction' => 'required|date',
        ]);

        $transaction = $this->transactionService->createTransaction($request->user()->asc_code, $data);

        return response()->json($transaction, 201);
    }

    public function exportPdf(Request $request)
    {
        // Seuls le Trésorier (3) et le Président (2) peuvent voir le PDF (Sécurité API)
        if (!in_array($request->user()->role_id, [2, 3])) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $ascCode = $request->user()->asc_code;
        $asc = Asc::where('code_unique', $ascCode)->first();
        
        $query = Transaction::where('asc_code', $ascCode);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date_transaction', [$request->start_date, $request->end_date]);
        }

        $transactions = $query->orderBy('date_transaction', 'desc')->get();

        $solde = 0;
        foreach ($transactions as $t) {
            if ($t->type == 'ENTREE') $solde += $t->montant;
            else $solde -= $t->montant;
        }

        $pdf = Pdf::loadView('pdf.bilan_financier', [
            'transactions' => $transactions,
            'solde' => $solde,
            'asc' => $asc,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);

        // Retourne le PDF en téléchargement direct
        return $pdf->download('bilan_financier_'.$ascCode.'.pdf');
    }
}
