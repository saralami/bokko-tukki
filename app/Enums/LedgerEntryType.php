<?php

namespace App\Enums;

enum LedgerEntryType: string
{
    case CashCommissionDebt = 'cash_commission_debt';
    case MobileMoneyProceeds = 'mobile_money_proceeds';
    case Commission = 'commission';
    case DebtSettlement = 'debt_settlement';
    case Withdrawal = 'withdrawal';
    case WithdrawalReversal = 'withdrawal_reversal';
    case Refund = 'refund';

    /**
     * Get the human-readable label for the ledger entry type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CashCommissionDebt => 'Commission cash (dette)',
            self::MobileMoneyProceeds => 'Recette Mobile Money',
            self::Commission => 'Commission Allo Dakar',
            self::DebtSettlement => 'Apurement de dette',
            self::Withdrawal => 'Retrait',
            self::WithdrawalReversal => 'Annulation de retrait',
            self::Refund => 'Remboursement',
        };
    }
}
