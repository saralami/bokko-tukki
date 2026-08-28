<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\RefundPayment;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Wallet;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    /**
     * List financial transactions (payments) with running totals.
     */
    public function transactions(Request $request): Response
    {
        $filters = $request->only(['method', 'status']);

        $payments = Payment::query()
            ->when($filters['method'] ?? null, fn ($query, $method) => $query->where('method', $method))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with(['transporter:id,company_name', 'booking:id,reference'])
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Payment $payment): array => $this->paymentRow($payment));

        return Inertia::render('admin/finance/Transactions', [
            'payments' => $payments,
            'filters' => $filters,
            'methods' => PaymentMethod::values(),
            'statuses' => PaymentStatus::values(),
            'totals' => [
                'volume' => (int) Payment::query()->where('status', PaymentStatus::Completed)->sum('amount'),
                'commissions' => (int) Payment::query()->where('status', PaymentStatus::Completed)->sum('commission_amount'),
            ],
        ]);
    }

    /**
     * Show the commissions earned by Allo Dakar, broken down by method.
     */
    public function commissions(): Response
    {
        $byMethod = DB::table('payments')
            ->where('status', PaymentStatus::Completed->value)
            ->groupBy('method')
            ->selectRaw('method, COUNT(*) as count, SUM(commission_amount) as total')
            ->get()
            ->map(fn (object $row): array => [
                'method' => $row->method,
                'method_label' => PaymentMethod::from($row->method)->label(),
                'count' => (int) $row->count,
                'total' => (int) $row->total,
            ])
            ->all();

        return Inertia::render('admin/finance/Commissions', [
            'byMethod' => $byMethod,
            'total' => (int) Payment::query()->where('status', PaymentStatus::Completed)->sum('commission_amount'),
        ]);
    }

    /**
     * Show outstanding transporter debts per wallet.
     */
    public function debts(): Response
    {
        $wallets = Wallet::query()
            ->where('outstanding_debt', '>', 0)
            ->with('transporter:id,company_name')
            ->orderByDesc('outstanding_debt')
            ->get()
            ->map(fn (Wallet $wallet): array => [
                'id' => $wallet->id,
                'transporter_id' => $wallet->transporter_id,
                'transporter' => $wallet->transporter?->company_name,
                'outstanding_debt' => $wallet->outstanding_debt,
                'available_balance' => $wallet->available_balance,
            ])
            ->all();

        return Inertia::render('admin/finance/Debts', [
            'wallets' => $wallets,
            'total' => (int) Wallet::query()->sum('outstanding_debt'),
        ]);
    }

    /**
     * List refunded payments and their compensating entries.
     */
    public function refunds(): Response
    {
        $refunds = Payment::query()
            ->where('status', PaymentStatus::Refunded)
            ->with(['transporter:id,company_name', 'booking:id,reference'])
            ->latest('updated_at')
            ->paginate(25)
            ->through(fn (Payment $payment): array => [
                ...$this->paymentRow($payment),
                'reversal' => LedgerEntry::query()
                    ->where('payment_id', $payment->id)
                    ->where('type', LedgerEntryType::Refund)
                    ->value('description'),
            ]);

        return Inertia::render('admin/finance/Refunds', [
            'refunds' => $refunds,
        ]);
    }

    /**
     * Show the immutable ledger with type filtering.
     */
    public function ledger(Request $request): Response
    {
        $filters = $request->only(['type']);

        $entries = LedgerEntry::query()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->with('wallet.transporter:id,company_name')
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'type_label' => $entry->type->label(),
                'transporter' => $entry->wallet?->transporter?->company_name,
                'amount' => $entry->amount,
                'balance_delta' => $entry->balance_delta,
                'debt_delta' => $entry->debt_delta,
                'balance_after' => $entry->balance_after,
                'debt_after' => $entry->debt_after,
                'description' => $entry->description,
                'date' => $entry->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/finance/Ledger', [
            'entries' => $entries,
            'filters' => $filters,
            'types' => array_map(fn (LedgerEntryType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ], LedgerEntryType::cases()),
        ]);
    }

    /**
     * Refund a payment through a compensating ledger entry (never an edit).
     *
     * A justification is mandatory and recorded both in the ledger description
     * and the audit trail.
     */
    public function refund(Request $request, Payment $payment, RefundPayment $refundPayment): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $refundPayment($payment, $validated['reason']);

        AuditLogger::log(
            'payment.refunded',
            "Remboursement du paiement #{$payment->id} ({$payment->amount} FCFA) : {$validated['reason']}",
            $payment,
            ['amount' => $payment->amount, 'reason' => $validated['reason']],
        );

        return back();
    }

    /**
     * Shape a payment for a finance table row.
     *
     * @return array<string, mixed>
     */
    private function paymentRow(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->booking?->reference,
            'transporter' => $payment->transporter?->company_name,
            'method' => $payment->method->value,
            'method_label' => $payment->method->label(),
            'amount' => $payment->amount,
            'commission' => $payment->commission_amount,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'date' => $payment->created_at?->toIso8601String(),
        ];
    }
}
