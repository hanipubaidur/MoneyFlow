<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Fill, Border, Alignment, NumberFormat, Font, Protection};
use PhpOffice\PhpSpreadsheet\Chart\{Chart, DataSeriesValues, Legend, PlotArea, Title};

try {
    $db = new Database();
    $conn = $db->getConnection();
    $spreadsheet = new Spreadsheet();
    
    $theme = [
        'primary' => '0F1419',
        'secondary' => '1A365D', 
        'accent' => '2B6CB0',
        'success' => '047857',
        'danger' => 'B91C1C',
        'warning' => 'D97706',
        'info' => '0369A1',
        'light' => 'F8FAFC',
        'gradient_start' => '1E3A8A',
        'gradient_end' => '3B82F6',
        'gold' => 'F59E0B',
        'silver' => '6B7280'
    ];

    // EXECUTIVE DASHBOARD
    $executive = $spreadsheet->getActiveSheet();
    $executive->setTitle('📊 Executive Dashboard');
    
    $executive->setCellValue('A1', '💰 MONEYFLOW');
    $executive->setCellValue('A2', 'Advanced Financial Intelligence Platform');
    $executive->setCellValue('A3', 'EXECUTIVE DASHBOARD & ANALYTICS');
    $executive->setCellValue('A4', '📅 ' . date('l, F j, Y • g:i A T'));
    
    foreach(['A1:H1', 'A2:H2', 'A3:H3', 'A4:H4'] as $i => $range) {
        $executive->mergeCells($range);
        $sizes = [24, 11, 16, 10];
        $colors = [$theme['primary'], $theme['secondary'], $theme['accent'], $theme['info']];
        
        $executive->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => $sizes[$i], 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors[$i]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $theme['primary']]]]
        ]);
    }

    $summaryQuery = "SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount END), 0) as total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount END), 0) as total_expense,
        COUNT(CASE WHEN type = 'income' THEN 1 END) as income_count,
        COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_count,
        COALESCE(SUM(CASE WHEN type = 'expense' AND expense_category_id IN 
            (SELECT id FROM expense_categories WHERE category_name LIKE '%saving%' OR category_name LIKE '%invest%') 
            THEN amount END), 0) as savings,
        COALESCE(SUM(CASE WHEN type = 'expense' AND expense_category_id IN 
            (SELECT id FROM expense_categories WHERE category_name LIKE '%debt%' OR category_name LIKE '%loan%') 
            THEN amount END), 0) as debt,
        COALESCE(AVG(CASE WHEN type = 'income' THEN amount END), 0) as avg_income,
        COALESCE(AVG(CASE WHEN type = 'expense' THEN amount END), 0) as avg_expense,
        COUNT(DISTINCT DATE_FORMAT(date, '%Y-%m')) as active_months
        FROM transactions WHERE status != 'deleted'";

    $summary = $conn->query($summaryQuery)->fetch(PDO::FETCH_ASSOC);
    
    $netWorth = $summary['total_income'] - $summary['total_expense'];
    $savingsRate = $summary['total_income'] > 0 ? ($summary['savings'] / $summary['total_income'] * 100) : 0;
    $burnRate = $summary['active_months'] > 0 ? ($summary['total_expense'] / $summary['active_months']) : 0;
    $runwayMonths = $netWorth > 0 && $burnRate > 0 ? ($netWorth / $burnRate) : 0;

    $executive->setCellValue('A6', '🎯 FINANCIAL PERFORMANCE METRICS');
    $executive->mergeCells('A6:H6');
    $executive->getStyle('A6')->applyFromArray([
        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $theme['primary']]],
        'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => $theme['light']], 'endColor' => ['rgb' => 'E2E8F0']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => $theme['accent']]]]
    ]);

    $metrics = [
        ['💰 Total Revenue', number_format($summary['total_income'], 0, ',', '.'), 'Rp', $theme['success'], '📈'],
        ['💸 Total Expenses', number_format($summary['total_expense'], 0, ',', '.'), 'Rp', $theme['danger'], '📉'],
        ['💎 Net Worth', number_format($netWorth, 0, ',', '.'), 'Rp', $netWorth >= 0 ? $theme['success'] : $theme['danger'], $netWorth >= 0 ? '🚀' : '⚠️'],
        ['🏦 Savings Pool', number_format($summary['savings'], 0, ',', '.'), 'Rp', $theme['info'], '💎'],
        ['⚡ Burn Rate', number_format($burnRate, 0, ',', '.'), 'Rp/month', $theme['warning'], '🔥'],
        ['📊 Savings Rate', number_format($savingsRate, 1), '%', $savingsRate >= 20 ? $theme['success'] : $theme['warning'], $savingsRate >= 20 ? '✨' : '⚡'],
        ['🛡️ Financial Runway', number_format($runwayMonths, 1), 'months', $runwayMonths >= 6 ? $theme['success'] : $theme['danger'], $runwayMonths >= 6 ? '🛡️' : '🚨'],
        ['📈 Transaction Volume', number_format($summary['income_count'] + $summary['expense_count']), 'transactions', $theme['accent'], '📋']
    ];

    // EXECUTIVE DASHBOARD
    $row = 8;
    foreach($metrics as $metric) {
        // Perbaiki data metrics agar tidak duplikat
        $executive->setCellValue('A'.$row, $metric[4] . ' ' . $metric[0]);
        $executive->setCellValue('D'.$row, $metric[2] . ' ' . $metric[1]);
        
        $executive->mergeCells("A$row:C$row");
        $executive->mergeCells("D$row:F$row");
        
        $executive->getStyle("A$row:C$row")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['light']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        $executive->getStyle("D$row:F$row")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $metric[3]]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $metric[3]]]]
        ]);
        
        $row += 2;  // Kurangi spacing
    }

    $executive->setCellValue('A' . ($row + 1), '📊 FINANCIAL HEALTH SCORE');
    $executive->mergeCells('A' . ($row + 1) . ':F' . ($row + 1));
    
    $healthScore = min(100, max(0, 
        ($savingsRate >= 20 ? 25 : $savingsRate * 1.25) +
        ($netWorth > 0 ? 25 : 0) +
        ($runwayMonths >= 6 ? 25 : $runwayMonths * 4.17) +
        ($summary['total_income'] > $summary['total_expense'] ? 25 : 0)
    ));
    
    $scoreColor = $healthScore >= 80 ? $theme['success'] : ($healthScore >= 60 ? $theme['warning'] : $theme['danger']);
    $scoreEmoji = $healthScore >= 80 ? '🌟' : ($healthScore >= 60 ? '⚡' : '🚨');
    
    $executive->setCellValue('A' . ($row + 3), $scoreEmoji . ' ' . number_format($healthScore, 1) . '%');
    $executive->mergeCells('A' . ($row + 3) . ':F' . ($row + 3));
    $executive->getStyle('A' . ($row + 3))->applyFromArray([
        'font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => $scoreColor]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['light']]]
    ]);

    foreach(range('A', 'H') as $col) {
        $executive->getColumnDimension($col)->setWidth($col == 'A' ? 30 : 18);
    }

    // INCOME INTELLIGENCE
    $income = $spreadsheet->createSheet();
    $income->setTitle('💚 Income Intelligence');
    
    $income->setCellValue('A1', '💚 INCOME INTELLIGENCE & OPTIMIZATION');
    $income->mergeCells('A1:G1');
    $income->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['success']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['outline' => ['borderStyle' => Border::BORDER_THICK]]
    ]);

    $incomeQuery = "SELECT 
        i.source_name,
        COUNT(t.id) as frequency,
        SUM(t.amount) as total,
        AVG(t.amount) as average,
        MAX(t.amount) as peak,
        MIN(t.amount) as minimum,
        STDDEV(t.amount) as volatility,
        COUNT(DISTINCT DATE_FORMAT(t.date, '%Y-%m')) as active_months
        FROM income_sources i
        LEFT JOIN transactions t ON i.id = t.income_source_id 
        WHERE t.type = 'income' AND t.status != 'deleted'
        GROUP BY i.id, i.source_name
        ORDER BY total DESC";

    $incomeData = $conn->query($incomeQuery)->fetchAll(PDO::FETCH_ASSOC);

    $headers = ['💰 Income Source', '📊 Frequency', '💎 Total Value', '📈 Average', '🚀 Peak', '💫 Consistency', '⚡ Growth Rate'];
    foreach($headers as $i => $header) {
        $col = chr(65 + $i);
        $income->setCellValue($col . '3', $header);
    }

    $income->getStyle('A3:G3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => $theme['success']], 'endColor' => ['rgb' => '065F46']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
    ]);

    $row = 4;
    foreach($incomeData as $data) {
        $consistency = $data['average'] > 0 ? (100 - min(100, ($data['volatility'] / $data['average']) * 100)) : 0;
        $growthRate = $data['active_months'] > 1 ? (($data['total'] / $data['active_months']) / $data['average'] - 1) * 100 : 0;
        
        $values = [
            '🏢 ' . $data['source_name'],
            $data['frequency'] . 'x',
            'Rp ' . number_format($data['total'], 0, ',', '.'),
            'Rp ' . number_format($data['average'], 0, ',', '.'),
            'Rp ' . number_format($data['peak'], 0, ',', '.'),
            number_format($consistency, 1) . '%',
            ($growthRate >= 0 ? '+' : '') . number_format($growthRate, 1) . '%'
        ];

        foreach($values as $i => $value) {
            $col = chr(65 + $i);
            $income->setCellValue($col . $row, $value);
        }

        $bgColor = $row % 2 == 0 ? $theme['light'] : 'FFFFFF';
        $income->getStyle("A$row:G$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
        
        $income->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        if($growthRate >= 10) {
            $income->getStyle("G$row")->getFont()->getColor()->setRGB($theme['success']);
            $income->getStyle("G$row")->getFont()->setBold(true);
        } elseif($growthRate < -5) {
            $income->getStyle("G$row")->getFont()->getColor()->setRGB($theme['danger']);
            $income->getStyle("G$row")->getFont()->setBold(true);
        }
        
        $row++;
    }

    foreach(range('A', 'G') as $col) {
        $income->getColumnDimension($col)->setAutoSize(true);
    }

    // EXPENSE ANALYTICS
    $expense = $spreadsheet->createSheet();
    $expense->setTitle('🔥 Expense Analytics');
    
    $expense->setCellValue('A1', '🔥 EXPENSE ANALYTICS & OPTIMIZATION');
    $expense->mergeCells('A1:H1');
    $expense->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => $theme['danger']], 'endColor' => ['rgb' => '991B1B']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['outline' => ['borderStyle' => Border::BORDER_THICK]]
    ]);

    $expenseQuery = "SELECT 
        ec.category_name,
        COUNT(t.id) as transactions,
        SUM(t.amount) as total,
        AVG(t.amount) as average,
        MAX(t.amount) as highest,
        STDDEV(t.amount) as volatility,
        COUNT(DISTINCT DATE_FORMAT(t.date, '%Y-%m')) as months_active
        FROM expense_categories ec
        LEFT JOIN transactions t ON ec.id = t.expense_category_id 
        WHERE t.type = 'expense' AND t.status != 'deleted'
        GROUP BY ec.id, ec.category_name
        ORDER BY total DESC";

    $expenseData = $conn->query($expenseQuery)->fetchAll(PDO::FETCH_ASSOC);

    $expenseHeaders = ['💸 Category', '📊 Volume', '💰 Total Spend', '📈 Average', '🔥 Peak', '📊 Share', '⚡ Volatility', '🎯 Priority'];
    foreach($expenseHeaders as $i => $header) {
        $col = chr(65 + $i);
        $expense->setCellValue($col . '3', $header);
    }

    $expense->getStyle('A3:H3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => $theme['danger']], 'endColor' => ['rgb' => '7F1D1D']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
    ]);

    $row = 4;
    foreach($expenseData as $data) {
        $percentage = $summary['total_expense'] > 0 ? ($data['total'] / $summary['total_expense'] * 100) : 0;
        $volatilityScore = $data['average'] > 0 ? min(100, ($data['volatility'] / $data['average']) * 100) : 0;
        
        $priority = 'LOW';
        $priorityColor = $theme['info'];
        if($percentage > 20) { $priority = 'CRITICAL'; $priorityColor = $theme['danger']; }
        elseif($percentage > 10) { $priority = 'HIGH'; $priorityColor = $theme['warning']; }
        elseif($percentage > 5) { $priority = 'MEDIUM'; $priorityColor = $theme['accent']; }

        $values = [
            '💳 ' . $data['category_name'],
            $data['transactions'] . 'x',
            'Rp ' . number_format($data['total'], 0, ',', '.'),
            'Rp ' . number_format($data['average'], 0, ',', '.'),
            'Rp ' . number_format($data['highest'], 0, ',', '.'),
            number_format($percentage, 1) . '%',
            number_format($volatilityScore, 1) . '%',
            $priority
        ];

        foreach($values as $i => $value) {
            $col = chr(65 + $i);
            $expense->setCellValue($col . $row, $value);
        }

        $bgColor = $percentage > 15 ? 'FEE2E2' : ($row % 2 == 0 ? $theme['light'] : 'FFFFFF');
        
        $expense->getStyle("A$row:H$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
        
        $expense->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $expense->getStyle("H$row")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $priorityColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $row++;
    }

    foreach(range('A', 'H') as $col) {
        $expense->getColumnDimension($col)->setAutoSize(true);
    }

    // TREND ANALYSIS
    $trends = $spreadsheet->createSheet();
    $trends->setTitle('📈 Trend Analysis');
    
    $trends->setCellValue('A1', '📈 ADVANCED TREND ANALYSIS & FORECASTING');
    $trends->mergeCells('A1:I1');
    $trends->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => $theme['accent']], 'endColor' => ['rgb' => $theme['primary']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    $trendsQuery = "SELECT 
        DATE_FORMAT(date, '%Y-%m') as period,
        DATE_FORMAT(date, '%b %Y') as display_period,
        SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
        COUNT(CASE WHEN type = 'income' THEN 1 END) as income_txn,
        COUNT(CASE WHEN type = 'expense' THEN 1 END) as expense_txn,
        AVG(CASE WHEN type = 'income' THEN amount END) as avg_income,
        AVG(CASE WHEN type = 'expense' THEN amount END) as avg_expense
        FROM transactions 
        WHERE status != 'deleted' AND date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(date, '%Y-%m'), DATE_FORMAT(date, '%b %Y')
        ORDER BY period DESC
        LIMIT 12";

    $trendsData = $conn->query($trendsQuery)->fetchAll(PDO::FETCH_ASSOC);

    $trendHeaders = ['📅 Period', '💰 Income', '💸 Expenses', '💎 Net Flow', '📊 Savings', '⚡ Efficiency', '📈 Growth', '🎯 Score', '🔮 Forecast'];
    foreach($trendHeaders as $i => $header) {
        $col = chr(65 + $i);
        $trends->setCellValue($col . '3', $header);
    }

    $trends->getStyle('A3:I3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_GRADIENT_LINEAR, 'startColor' => ['rgb' => $theme['primary']], 'endColor' => ['rgb' => $theme['accent']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
    ]);

    $row = 4;
    $prevIncome = 0;
    foreach($trendsData as $i => $data) {
        $netFlow = $data['income'] - $data['expense'];
        $efficiency = $data['income'] > 0 ? (($data['income'] - $data['expense']) / $data['income'] * 100) : 0;
        $growth = $prevIncome > 0 ? (($data['income'] - $prevIncome) / $prevIncome * 100) : 0;
        $score = min(100, max(0, $efficiency + ($growth * 0.5)));
        $forecast = $data['income'] * (1 + ($growth / 100));
        
        $values = [
            '📅 ' . $data['display_period'],
            'Rp ' . number_format($data['income'], 0, ',', '.'),
            'Rp ' . number_format($data['expense'], 0, ',', '.'),
            'Rp ' . number_format($netFlow, 0, ',', '.'),
            'Rp ' . number_format(max(0, $netFlow), 0, ',', '.'),
            number_format($efficiency, 1) . '%',
            ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '%',
            number_format($score, 0),
            'Rp ' . number_format($forecast, 0, ',', '.')
        ];

        foreach($values as $j => $value) {
            $col = chr(65 + $j);
            $trends->setCellValue($col . $row, $value);
        }

        $performanceColor = $netFlow >= 0 ? 'DCFCE7' : 'FEE2E2';
        $trends->getStyle("A$row:I$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $performanceColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);
        
        $trends->getStyle("A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        $netColor = $netFlow >= 0 ? $theme['success'] : $theme['danger'];
        $trends->getStyle("D$row")->getFont()->getColor()->setRGB($netColor);
        $trends->getStyle("D$row")->getFont()->setBold(true);
        
        $prevIncome = $data['income'];
        $row++;
    }

    foreach(range('A', 'I') as $col) {
        $trends->getColumnDimension($col)->setAutoSize(true);
    }

   // TRANSACTION MASTER
    $transactions = $spreadsheet->createSheet();
    $transactions->setTitle('📋 Transaction Master');

    // Header styling
    $transactions->setCellValue('A1', '📋 COMPLETE TRANSACTION MASTER DATABASE');
    $transactions->mergeCells('A1:E1');
    $transactions->getStyle('A1')->applyFromArray([
        'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['secondary']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    // PERBAIKAN 1: Definisi headers yang hilang
    $transHeaders = ['📅 Date', '🔄 Type', '📂 Category', '💰 Amount', '📝 Description'];

    // Column headers styling
    foreach($transHeaders as $i => $header) {
        $col = chr(65 + $i);
        $transactions->setCellValue($col.'3', $header);
        $transactions->getColumnDimension($col)->setAutoSize(true);
    }

    $transactions->getStyle('A3:E3')->applyFromArray([
        'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['accent']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
    ]);

    $transQuery = "SELECT 
        t.id, t.date, t.type, t.amount, t.description,
        CASE WHEN t.type = 'income' THEN i.source_name ELSE e.category_name END as category
        FROM transactions t
        LEFT JOIN income_sources i ON t.income_source_id = i.id
        LEFT JOIN expense_categories e ON t.expense_category_id = e.id
        WHERE t.status != 'deleted'
        ORDER BY t.date DESC, t.id DESC
        LIMIT 500";

    $transData = $conn->query($transQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Data population and styling
    $row = 4;
    foreach($transData as $t) {
        $typeEmoji = $t['type'] === 'income' ? '💰' : '💸';
        $amount = floatval($t['amount']);
        
        // Set priority and colors
        $priority = 'LOW';
        $priorityColor = $theme['info'];
        if($amount > 1000000) {
            $priority = 'HIGH';
            $priorityColor = $theme['danger'];
        } elseif($amount > 500000) {
            $priority = 'MEDIUM';
            $priorityColor = $theme['warning'];
        }
        
        // PERBAIKAN 2: Perbaikan struktur data values
        $values = [
            ['A', date('d/m/Y', strtotime($t['date'])), Alignment::HORIZONTAL_CENTER],
            ['B', $typeEmoji . ' ' . ucfirst($t['type']), Alignment::HORIZONTAL_CENTER],
            ['C', $t['category'] ?: '-', Alignment::HORIZONTAL_LEFT],
            ['D', $amount, Alignment::HORIZONTAL_RIGHT],
            ['E', $t['description'] ?: '-', Alignment::HORIZONTAL_LEFT]
        ];

        // Set values and styling for each cell
        foreach($values as $data) {
            $col = $data[0];
            $value = $data[1];
            $align = $data[2];
            $cell = $col.$row;
            
            $transactions->setCellValue($cell, $value);
            $transactions->getStyle($cell)->getAlignment()->setHorizontal($align);
            
            // Special formatting for amount
            if($col === 'D') {
                $transactions->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
            }
        }

        // Row styling
        $bgColor = $t['type'] === 'income' ? 'E8F5E9' : 'FBE9E7';
        $transactions->getStyle("A$row:E$row")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Priority styling
        $transactions->getStyle("H$row")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => $priorityColor]]
        ]);

        // Set row height
        $transactions->getRowDimension($row)->setRowHeight(20);

        $row++;
    }

    // PERBAIKAN 3: Perbaikan summary section
    $summaryRow = $row + 2;
    $transactions->setCellValue("A$summaryRow", "📊 TRANSACTION SUMMARY");
    $transactions->mergeCells("A$summaryRow:H$summaryRow");
    $transactions->getStyle("A$summaryRow:H$summaryRow")->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['primary']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]]
    ]);

    // PERBAIKAN 4: Perbaikan formula range dan add manual calculation
    $totalIncome = 0;
    $totalExpense = 0;
    $transactionCount = count($transData);
    
    // Calculate totals manually from data
    foreach($transData as $t) {
        if($t['type'] === 'income') {
            $totalIncome += floatval($t['amount']);
        } else {
            $totalExpense += floatval($t['amount']);
        }
    }
    
    $netFlow = $totalIncome - $totalExpense;
    $avgTransaction = $transactionCount > 0 ? ($totalIncome + $totalExpense) / $transactionCount : 0;

    $summaryData = [
        ['💰 Total Income', 'Rp ' . number_format($totalIncome, 0, ',', '.')],
        ['💸 Total Expense', 'Rp ' . number_format($totalExpense, 0, ',', '.')],
        ['💎 Net Flow', 'Rp ' . number_format($netFlow, 0, ',', '.')],
        ['📊 Transaction Count', $transactionCount . ' transactions'],
        ['📈 Average Transaction', 'Rp ' . number_format($avgTransaction, 0, ',', '.')]
    ];

    $summaryStartRow = $summaryRow + 1;
    foreach ($summaryData as $i => $data) {
        $currentRow = $summaryStartRow + $i;
        $transactions->setCellValue("A$currentRow", $data[0]);
        $transactions->setCellValue("D$currentRow", $data[1]);
        $transactions->mergeCells("A$currentRow:C$currentRow");
        $transactions->mergeCells("D$currentRow:F$currentRow");
        
        // Style summary rows
        $transactions->getStyle("A$currentRow:F$currentRow")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        
        // Color coding for net flow
        if($i === 2) { // Net Flow row
            $netColor = $netFlow >= 0 ? $theme['success'] : $theme['danger'];
            $transactions->getStyle("D$currentRow")->getFont()->getColor()->setRGB($netColor);
        }
    }

    // PERBAIKAN 5: Simplified chart creation (removed complex chart due to potential issues)
    // Add simple statistics instead
    $statsRow = $summaryStartRow + 6;
    $transactions->setCellValue("A$statsRow", "📈 QUICK INSIGHTS");
    $transactions->mergeCells("A$statsRow:H$statsRow");
    $transactions->getStyle("A$statsRow")->applyFromArray([
        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => $theme['accent']]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ]);

    $insights = [
        ['💡 Financial Health', $netFlow >= 0 ? '✅ Positive' : '⚠️ Needs Attention'],
        ['📊 Activity Level', $transactionCount > 50 ? '🔥 High Activity' : ($transactionCount > 20 ? '⚡ Moderate' : '📉 Low Activity')],
        ['💰 Income Ratio', number_format(($totalIncome / ($totalIncome + $totalExpense)) * 100, 1) . '%'],
        ['🎯 Recommendation', $netFlow >= 0 ? '🚀 Great! Keep it up!' : '💪 Focus on income growth']
    ];

    foreach($insights as $i => $insight) {
        $currentRow = $statsRow + 1 + $i;
        $transactions->setCellValue("A$currentRow", $insight[0]);
        $transactions->setCellValue("D$currentRow", $insight[1]);
        $transactions->mergeCells("A$currentRow:C$currentRow");
        $transactions->mergeCells("D$currentRow:F$currentRow");
        
        $transactions->getStyle("A$currentRow:F$currentRow")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $theme['light']]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'font' => ['size' => 10]
        ]);
    }

    // PERBAIKAN 6: Simplified protection
    $transactions->getProtection()->setSheet(false); // Disable protection to avoid issues

    // PERBAIKAN 7: Improved header/footer
    $transactions->getHeaderFooter()
        ->setOddHeader('&L&B📋 MoneyFlow&C&BTransaction Master Report&R&B' . date('d/m/Y'))
        ->setOddFooter('&L&BGenerated: ' . date('Y-m-d H:i:s') . '&C&BMoneyFlow Platform&R&BPage &P of &N');

    // Set column widths for better display
    $columnWidths = ['A' => 12, 'B' => 15, 'C' => 20, 'D' => 15, 'E' => 25, 'F' => 15, 'G' => 15, 'H' => 12];
    foreach($columnWidths as $col => $width) {
        $transactions->getColumnDimension($col)->setWidth($width);
    }
            
    // Set active sheet to Overview
    $spreadsheet->setActiveSheetIndex(0);

    // Set filename
    $filename = "MoneyFlow_Report_" . date('Y-m-d_His') . ".xlsx";
    
    // Clear any previous output
    ob_clean();
    
    // Set correct headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    error_log('Export Error: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}