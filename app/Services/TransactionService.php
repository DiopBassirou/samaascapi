<?php

namespace App\Services;

use App\Models\Transaction;

class TransactionService
{
    /**
     * Récupère le bilan financier de l'ASC.
     */
    public function getBilanFinancier(string $ascCode)
    {
        $transactions = Transaction::select('transactions.*')
            ->join('users', 'users.id', '=', 'transactions.user_id')
            ->where('users.asc_code', $ascCode)
            ->orderBy('date_transaction', 'desc')
            ->get();

        $entrees = $transactions->where('type', 'ENTREE')->sum('montant');
        $sorties = $transactions->where('type', 'DEPENSE')->sum('montant');
        $solde = $entrees - $sorties;

        return [
            'bilan' => [
                'total_entrees' => $entrees,
                'total_depenses' => $sorties,
                'solde_actuel' => $solde,
            ],
            'transactions' => $transactions
        ];
    }

    /**
     * Enregistre une nouvelle transaction.
     */
    public function createTransaction(array $data, int $userId)
    {
        return Transaction::create([
            'user_id' => $userId,
            'type' => $data['type'],
            'montant' => $data['montant'],
            'categorie' => $data['categorie'],
            'date_transaction' => $data['date_transaction'],
        ]);
    }
}
