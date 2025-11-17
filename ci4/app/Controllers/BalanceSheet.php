<?php
namespace App\Controllers;

use App\Controllers\Core\CommonController;
use App\Models\AccountingPeriods_model;
use App\Models\Accounts_model;

class BalanceSheet extends CommonController
{
    protected $accounts_model;
    protected $periods_model;

    public function __construct()
    {
        parent::__construct();
        $this->accounts_model = new Accounts_model();
        $this->periods_model  = new AccountingPeriods_model();
    }

    /**
     * Display Balance Sheet Report
     */
    public function index()
    {
        $this->data['page_title'] = "Balance Sheet";

        // Get current period
        $currentPeriod = $this->periods_model->getCurrentPeriod($this->businessUuid);

        // Get date range from request or use current period
        $asOfDate = $this->request->getGet('as_of_date') ?? ($currentPeriod['end_date'] ?? date('Y-m-d'));

        $this->data['as_of_date']  = $asOfDate;
        $this->data['report_data'] = $this->generateBalanceSheet($asOfDate);
        $this->data['periods']     = $this->periods_model
            ->where('uuid_business_id', $this->businessUuid)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        echo view('common/header', $this->data);
        echo view('common/sidebar', $this->data);
        echo view('reports/balance_sheet', $this->data);
    }

    /**
     * Generate Balance Sheet data
     */
    private function generateBalanceSheet($asOfDate)
    {
        $db = \Config\Database::connect();

        // Get all accounts with their balances
        $query = "
            SELECT
                a.id,
                a.account_code,
                a.account_name,
                a.account_type,
                a.account_subtype,
                a.normal_balance,
                a.opening_balance,
                COALESCE(SUM(jel.debit_amount), 0) as total_debit,
                COALESCE(SUM(jel.credit_amount), 0) as total_credit,
                (a.opening_balance +
                    CASE
                        WHEN a.normal_balance = 'Debit' THEN COALESCE(SUM(jel.debit_amount - jel.credit_amount), 0)
                        ELSE COALESCE(SUM(jel.credit_amount - jel.debit_amount), 0)
                    END
                ) as balance
            FROM accounts a
            LEFT JOIN journal_entry_lines jel ON jel.uuid_account_id = a.uuid
            LEFT JOIN journal_entries je ON je.uuid = jel.uuid_journal_entry_id
                AND je.is_posted = 1
                AND je.entry_date <= ?
            WHERE a.uuid_business_id = ?
                AND a.is_active = 1
                AND a.account_type IN ('Asset', 'Liability', 'Equity')
            GROUP BY a.id, a.account_code, a.account_name, a.account_type, a.account_subtype, a.normal_balance, a.opening_balance
            ORDER BY a.account_code ASC
        ";

        $accounts = $db->query($query, [$asOfDate, $this->businessUuid])->getResultArray();

        // Organize by type
        $assets      = [];
        $liabilities = [];
        $equity      = [];

        foreach ($accounts as $account) {
            if ($account['account_type'] === 'Asset') {
                $assets[] = $account;
            } elseif ($account['account_type'] === 'Liability') {
                $liabilities[] = $account;
            } elseif ($account['account_type'] === 'Equity') {
                $equity[] = $account;
            }
        }

        // Calculate totals
        $totalAssets      = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity      = array_sum(array_column($equity, 'balance'));

        // Get net income from P&L
        $netIncome = $this->getNetIncome($asOfDate);
        $totalEquity += $netIncome;

        return [
            'assets'                   => $assets,
            'liabilities'              => $liabilities,
            'equity'                   => $equity,
            'total_assets'             => $totalAssets,
            'total_liabilities'        => $totalLiabilities,
            'total_equity'             => $totalEquity,
            'net_income'               => $netIncome,
            'total_liabilities_equity' => $totalLiabilities + $totalEquity,
        ];
    }

    /**
     * Get Net Income for period
     */
    private function getNetIncome($asOfDate)
    {
        $db = \Config\Database::connect();

        $query = "
            SELECT
                COALESCE(SUM(
                    CASE
                        WHEN a.account_type = 'Revenue' THEN jel.credit_amount - jel.debit_amount
                        WHEN a.account_type = 'Expense' THEN jel.debit_amount - jel.credit_amount
                        ELSE 0
                    END
                ), 0) as net_income
            FROM journal_entry_lines jel
            INNER JOIN accounts a ON a.uuid = jel.uuid_account_id
            INNER JOIN journal_entries je ON je.uuid = jel.uuid_journal_entry_id
            WHERE je.uuid_business_id = ?
                AND je.is_posted = 1
                AND je.entry_date <= ?
                AND a.account_type IN ('Revenue', 'Expense')
        ";

        $result = $db->query($query, [$this->businessUuid, $asOfDate])->getRowArray();
        return $result['net_income'] ?? 0;
    }

    /**
     * Export to PDF - Override parent method
     */
    public function exportPDF($uuid = 0, $view = '')
    {
        // PDF export functionality
        $asOfDate   = $this->request->getGet('as_of_date') ?? date('Y-m-d');
        $reportData = $this->generateBalanceSheet($asOfDate);

        // Implement PDF generation here
        return $this->response->setJSON([
            'status'  => true,
            'message' => 'PDF export feature - to be implemented',
        ]);
    }
}
