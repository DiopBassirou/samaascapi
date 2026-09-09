<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bilan Financier ASC</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #2C5364; }
        .solde { text-align: right; font-size: 18px; font-weight: bold; padding: 10px; background-color: #f8f9fa; border: 1px solid #ddd; margin-bottom: 20px;}
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #2C5364; color: white; }
        .entree { color: green; font-weight: bold; }
        .sortie { color: red; font-weight: bold; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 50px; border-top: 1px solid #ddd; padding-top: 10px;}
    </style>
</head>
<body>
    <div class="header">
        <h1>Bilan Financier</h1>
        <h2>ASC : {{ $asc->nom ?? 'Inconnue' }} ({{ $asc->ville ?? '' }})</h2>
        @if(isset($start_date) && isset($end_date))
            <p><strong>Période :</strong> du {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</p>
        @else
            <p><strong>Période :</strong> Aperçu Global</p>
        @endif
        <p>Généré le : {{ date('d/m/Y H:i') }}</p>
    </div>

    <div class="solde">
        Solde Net de la période : <span style="color: {{ $solde >= 0 ? 'green' : 'red' }}">{{ $solde >= 0 ? '+' : '' }}{{ number_format($solde, 0, ',', ' ') }} FCFA</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Motif</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $t)
            <tr>
                <td>{{ \Carbon\Carbon::parse($t->date_transaction)->format('d/m/Y') }}</td>
                <td>
                    @if($t->type == 'ENTREE')
                        <span class="entree">ENTRÉE</span>
                    @else
                        <span class="sortie">SORTIE</span>
                    @endif
                </td>
                <td>{{ number_format($t->montant, 0, ',', ' ') }} FCFA</td>
                <td>{{ $t->motif }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align:center;">Aucune transaction enregistrée.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Document officiel SAMA ASC - Téléchargé par le bureau exécutif (Président / Trésorier)
    </div>
</body>
</html>
