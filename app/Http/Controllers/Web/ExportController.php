<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function transactions(Request $request): StreamedResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $user       = auth()->user();
        $networkIds = $user->networkUsers()->pluck('id')->all();

        $transactions = Transaction::with(['category', 'type', 'paymentMethod'])
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $networkIds))
            ->whereBetween('due_date', [$request->start_date, $request->end_date])
            ->orderBy('due_date', 'desc')
            ->get();

        $filename = 'economyx-' . $request->start_date . '-a-' . $request->end_date . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // BOM para Excel abrir UTF-8 corretamente
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Data',
                'Descrição',
                'Categoria',
                'Tipo',
                'Forma de pagamento',
                'Valor',
            ], ';');

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    Carbon::parse($t->due_date)->format('d/m/Y'),
                    $t->description,
                    $t->category?->name ?? '—',
                    $t->type?->name ?? '—',
                    $t->paymentMethod?->name ?? '—',
                    number_format($t->amount, 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
