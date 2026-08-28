---
paths:
  - 'app/Actions/Payments/**'
---

# Payments

## Moteur financier: ledger immuable, cascade, idempotence
Toute mutation d'argent passe par Wallet::postEntry (met à jour available_balance/outstanding_debt + écrit un LedgerEntry) DANS une DB::transaction avec Wallet::lockForUpdate. LedgerEntry est IMMUABLE (events updating/deleting throw) → corrections = écritures compensatoires (type Refund/WithdrawalReversal). Invariant: available_balance == Σ balance_delta, outstanding_debt == Σ debt_delta. Commission = round(total × config('allodakar.commission.rate'), défaut 5%). CASH = ProcessCashPayment (commission → dette). MOBILE MONEY = ProcessMobileMoneyPayment, cascade STRICTE: 1) commission, 2) anciennes dettes (min(proceeds, debt)), 3) solde. Idempotence: payments.booking_id unique (1 paiement/résa) + provider_reference unique (webhook rejouable, re-check sous lock). Webhook /webhooks/mobile-money: secret X-Webhook-Secret + CSRF except. Retraits: RequestWithdrawal débite le solde immédiatement sous lock (≤ available_balance, jamais la dette) ; reject = RejectWithdrawal (reversal). Seuil dette config('allodakar.debt.maximum') via Transporter::hasExceededDebtCeiling() bloque CreateBooking et PublishTrip. Colonnes monétaires unsignedBigInteger (garde-fou négatif côté MySQL). Cash encaissé à l'embarquement (BoardingController), remboursement à l'annulation d'une résa payée (CancelBooking→RefundPayment).
