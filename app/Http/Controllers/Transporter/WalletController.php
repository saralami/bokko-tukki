<?php

namespace App\Http\Controllers\Transporter;

use App\Actions\Withdrawals\RequestWithdrawal;
use App\Http\Controllers\Controller;
use App\Http\Requests\RequestWithdrawalRequest;
use App\Models\LedgerEntry;
use App\Models\Withdrawal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    /**
     * Display the transporter wallet: balance, debt, ledger and withdrawals.
     */
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporter ?? abort(403);
        $wallet = $transporter->walletOrCreate();

        $ledger = $wallet->ledgerEntries()->latest()->limit(50)->get()->map(fn (LedgerEntry $entry): array => [
            'id' => $entry->id,
            'type' => $entry->type->label(),
            'amount' => $entry->amount,
            'balance_delta' => $entry->balance_delta,
            'debt_delta' => $entry->debt_delta,
            'balance_after' => $entry->balance_after,
            'debt_after' => $entry->debt_after,
            'description' => $entry->description,
            'date' => $entry->created_at?->toDateTimeString(),
        ]);

        $withdrawals = $transporter->withdrawals()->latest()->get()->map(fn (Withdrawal $withdrawal): array => [
            'id' => $withdrawal->id,
            'amount' => $withdrawal->amount,
            'status' => $withdrawal->status->value,
            'date' => $withdrawal->created_at?->toDateTimeString(),
        ]);

        return Inertia::render('transporter/Wallet', [
            'wallet' => [
                'available_balance' => $wallet->available_balance,
                'outstanding_debt' => $wallet->outstanding_debt,
            ],
            'ledger' => $ledger,
            'withdrawals' => $withdrawals,
        ]);
    }

    /**
     * Request a withdrawal of the available balance.
     */
    public function requestWithdrawal(RequestWithdrawalRequest $request, RequestWithdrawal $requestWithdrawal): RedirectResponse
    {
        $transporter = $request->user()->transporter ?? abort(403);

        $requestWithdrawal($transporter, (int) $request->validated('amount'));

        return to_route('transporter.wallet.index');
    }
}
