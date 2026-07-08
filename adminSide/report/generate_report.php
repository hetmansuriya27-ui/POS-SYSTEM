<?php
// adminSide/report/generate_report.php
require('../posBackend/fpdf186/fpdf.php'); // Include the FPDF library
require_once '../config.php';

function executeQuery($link, $sql) {
    $result = $link->query($sql);
    if ($result === false) {
        error_log("Error in PDF Report Query: " . $link->error);
        return null;
    }
    return $result;
}

function getCategoryRevenue($link, $sql) {
    return executeQuery($link, $sql);
}

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 20);
        // Premium Dark Slate Accent Header
        $this->SetTextColor(30, 41, 59);
        $this->Cell(0, 10, "X Hotel Omnichannel Revenue Report", 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, "Generated on " . date('Y-m-d H:i:s') . " | Admin Panel Reporting", 0, 1, 'C');
        $this->Ln(4);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | X Hotel POS System', 0, 0, 'C');
    }

    function ChapterTitle($title)
    {
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 8, $title, 0, 1, 'L');
        $this->Ln(2);
    }

    function ChapterBody($body)
    {
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(51, 65, 85);
        $this->MultiCell(0, 6, $body);
        $this->Ln(2);
    }

    function CustomTable($header, $data)
    {
        $w = array(90, 90);
        
        // Header
        $this->SetFillColor(30, 41, 59);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Data
        $this->SetTextColor(51, 65, 85);
        $this->SetFont('Arial', '', 10);
        $fill = false;
        foreach ($data as $row) {
            $this->Cell($w[0], 8, $row[0], 1, 0, 'L');
            $this->Cell($w[1], 8, $row[1], 1, 0, 'R');
            $this->Ln();
        }
    }
    
    function CustomTableThreeColumn($header, $data)
    {
        $w = array(60, 60, 60);
        $this->SetFillColor(30, 41, 59);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 11);
        foreach ($header as $col) {
            $this->Cell(60, 8, $col, 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetTextColor(51, 65, 85);
        $this->SetFont('Arial', '', 10);
        foreach ($data as $row) {
            $this->Cell(60, 8, $row[0], 1, 0, 'L');
            $this->Cell(60, 8, $row[1], 1, 0, 'C');
            $this->Cell(60, 8, $row[2], 1, 0, 'R');
            $this->Ln();
        }
    }
    
    function CustomTableFourColumn($header, $data)
    {
        $columnWidths = array(40, 40, 50, 50);

        $this->SetFillColor(30, 41, 59);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($columnWidths[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetTextColor(51, 65, 85);
        $this->SetFont('Arial', '', 10);
        foreach ($data as $row) {
            for ($i = 0; $i < count($row); $i++) {
                $align = ($i == 0 || $i == 2) ? 'L' : 'R';
                if ($i == 1) $align = 'C';
                $this->Cell($columnWidths[$i], 8, $row[$i], 1, 0, $align);
            }
            $this->Ln();
        }
    }

    function CustomTableFiveColumn($header, $data)
    {
        $columnWidths = array(40, 25, 40, 40, 40); // Sum matches printable width (185mm)

        $this->SetFillColor(30, 41, 59);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($columnWidths[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();

        $this->SetTextColor(51, 65, 85);
        $this->SetFont('Arial', '', 9);
        foreach ($data as $row) {
            for ($i = 0; $i < count($row); $i++) {
                $align = ($i == 0) ? 'L' : 'R';
                if ($i == 1) $align = 'C';
                $this->Cell($columnWidths[$i], 8, $row[$i], 1, 0, $align);
            }
            $this->Ln();
        }
    }
}

$pdf = new PDF();
$pdf->AddPage();

// KDS Section
$kitchenQuery = "SELECT 
    CONCAT(YEAR(time_submitted), '-', LPAD(MONTH(time_submitted), 2, '0')) AS year_and_month,
    COUNT(*) AS total_items_cooked,
    SUM(quantity) AS total_quantity,
    0 AS average_cook_time
FROM 
    Kitchen
WHERE 
    YEAR(time_submitted) = YEAR(NOW()) AND MONTH(time_submitted) BETWEEN 1 AND 12
GROUP BY 
    YEAR(time_submitted), MONTH(time_submitted);
";

$kitchenResult = getCategoryRevenue($link, $kitchenQuery);
$pdf->ChapterTitle('Kitchen Performance Monthly');
$header = array('Month','Items Cooked' , 'Total Qty', 'Avg Prep Time');
$data = array();
if ($kitchenResult) {
    while ($row = mysqli_fetch_assoc($kitchenResult)) {
        $data[] = array($row['year_and_month'], $row['total_items_cooked'], $row['total_quantity'], $row['average_cook_time'] . " mins");
    }
}
if (empty($data)) {
    $data[] = array(date('Y-m'), '0', '0', '0 mins');
}
$pdf->CustomTableFourColumn($header, $data);
$pdf->Ln(6);

// Omnichannel Financial Indicators (Handles legacy and new bills)
$currentDate = date('Y-m-d');

$todayQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE DATE(Bills.bill_time) = '$currentDate'";
$todayResult = mysqli_query($link, $todayQuery);
$todayRow = mysqli_fetch_assoc($todayResult);
$totalRevenueToday = floatval($todayRow['total_revenue']);

$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$weekQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE DATE(Bills.bill_time) >= '$currentWeekStart'";
$weekResult = mysqli_query($link, $weekQuery);
$weekRow = mysqli_fetch_assoc($weekResult);
$totalRevenueThisWeek = floatval($weekRow['total_revenue']);

$currentMonthStart = date('Y-m-01');
$monthQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE DATE(Bills.bill_time) >= '$currentMonthStart'";
$monthResult = mysqli_query($link, $monthQuery);
$monthRow = mysqli_fetch_assoc($monthResult);
$totalRevenueThisMonth = floatval($monthRow['total_revenue']);

$currentYear = date('Y');
$yearQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE YEAR(Bills.bill_time) = '$currentYear'";
$yearResult = mysqli_query($link, $yearQuery);
$yearRow = mysqli_fetch_assoc($yearResult);
$totalRevenueThisYear = floatval($yearRow['total_revenue']);

// Financial summaries chapter
$pdf->ChapterTitle('Omnichannel Temporal Performance');
$pdf->ChapterBody("Date Range: " . date('Y-m-d') . " | Curated POS summary stats.");
$temporalData = array(
    array("Today's Aggregated Net Revenue", "Rs " . number_format($totalRevenueToday, 2)),
    array("Weekly Aggregated Net Revenue", "Rs " . number_format($totalRevenueThisWeek, 2)),
    array("Monthly Aggregated Net Revenue", "Rs " . number_format($totalRevenueThisMonth, 2)),
    array("Yearly Aggregated Net Revenue", "Rs " . number_format($totalRevenueThisYear, 2))
);
$pdf->CustomTable(array('Metric / Period', 'Net Settled Revenue'), $temporalData);
$pdf->Ln(6);

// Omnichannel Channel Performance Breakdown Chapter
$pdf->AddPage();
$pdf->ChapterTitle("Omnichannel Sales & Platform Breakdown (Current Month)");
$pdf->ChapterBody("Month Start: " . $currentMonthStart . " | Detailed breakdown per ingestion source channel.");

$channelSQL = "
    SELECT 
        order_source,
        COUNT(*) AS orders_count,
        SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS net_settlement,
        SUM(commission_amount) AS total_commission,
        SUM(packaging_charge) AS total_packaging
    FROM Bills
    WHERE bill_time BETWEEN '$currentMonthStart 00:00:00' AND '" . date('Y-m-t') . " 23:59:59'
    GROUP BY order_source
";
$channelResult = mysqli_query($link, $channelSQL);
$channelHeader = array('Channel Source', 'Orders', 'Gross Est.', 'Commissions', 'Net Settled');
$channelData = array();
if ($channelResult) {
    while ($row = mysqli_fetch_assoc($channelResult)) {
        $net = floatval($row['net_settlement']);
        $comm = floatval($row['total_commission']);
        $pack = floatval($row['total_packaging']);
        $gross = $net + $comm - $pack;
        
        $channelData[] = array(
            $row['order_source'],
            $row['orders_count'],
            "Rs " . number_format($gross, 2),
            "Rs " . number_format($comm, 2),
            "Rs " . number_format($net, 2)
        );
    }
}
if (empty($channelData)) {
    $channelData[] = array('Dine-In', '0', 'Rs 0.00', 'Rs 0.00', 'Rs 0.00');
}
$pdf->CustomTableFiveColumn($channelHeader, $channelData);
$pdf->Ln(6);

// Settlement & Reconciliation Summary
$pdf->ChapterTitle("Settlement & Platform Reconciliation Summary");
$reconSQL = "
    SELECT 
        platform_name, 
        COUNT(*) AS count, 
        SUM(expected_revenue) AS total_expected,
        SUM(actual_settlement) AS total_actual,
        SUM(variance) AS total_variance
    FROM settlements_reconciliation
    GROUP BY platform_name
";
$reconResult = mysqli_query($link, $reconSQL);
$reconHeader = array('Platform Channel', 'Trans.', 'Expected Rev.', 'Actual Paid', 'Variance');
$reconData = array();
if ($reconResult) {
    while ($row = mysqli_fetch_assoc($reconResult)) {
        $reconData[] = array(
            $row['platform_name'],
            $row['count'],
            "Rs " . number_format($row['total_expected'], 2),
            "Rs " . number_format($row['total_actual'] ?? 0, 2),
            "Rs " . number_format($row['total_variance'] ?? 0, 2)
        );
    }
}
if (empty($reconData)) {
    $reconData[] = array('Swiggy', '0', 'Rs 0.00', 'Rs 0.00', 'Rs 0.00');
}
$pdf->CustomTableFiveColumn($reconHeader, $reconData);

// Daily/Weekly/Monthly Net breakdown
$pdf->AddPage();

// Get daily revenue breakdown 
$dailySQL = "SELECT DATE(b.bill_time) AS date, DAY(b.bill_time) AS day, 
             SUM(CASE WHEN b.net_revenue > 0 THEN b.net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = b.bill_id) END) AS daily_category_revenue
             FROM Bills b
             GROUP BY DATE(b.bill_time), DAY(b.bill_time)
             ORDER BY date DESC
             LIMIT 15";
$dailyCategoryRevenue = getCategoryRevenue($link, $dailySQL);
$pdf->ChapterTitle('Daily Omnichannel Net Settlements');
$header = array('Settlement Date','Day' , 'Net Settled (Rs)');
$data = array();
if ($dailyCategoryRevenue) {
    while ($row = mysqli_fetch_assoc($dailyCategoryRevenue)) {
        $data[] = array($row['date'], $row['day'], "Rs " . number_format($row['daily_category_revenue'], 2));
    }
}
$pdf->CustomTableThreeColumn($header, $data);

// Get weekly revenue breakdown 
$weeklySQL = "SELECT CONCAT(YEAR(b.bill_time), '-', MONTH(b.bill_time)) AS year, WEEK(b.bill_time) AS week, 
              SUM(CASE WHEN b.net_revenue > 0 THEN b.net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = b.bill_id) END) AS weekly_category_revenue
              FROM Bills b
              GROUP BY YEAR(b.bill_time), WEEK(b.bill_time)
              ORDER BY year ASC, week ASC
              LIMIT 15";
$weeklyCategoryRevenue = getCategoryRevenue($link, $weeklySQL);
$pdf->AddPage();
$pdf->ChapterTitle('Weekly Omnichannel Net Settlements');
$header = array('Year-Month','Week Number' , 'Net Settled (Rs)');
$data = array();
if ($weeklyCategoryRevenue) {
    while ($row = mysqli_fetch_assoc($weeklyCategoryRevenue)) {
        $data[] = array($row['year'], $row['week'], "Rs " . number_format($row['weekly_category_revenue'], 2));
    }
}
$pdf->CustomTableThreeColumn($header, $data);

// Get monthly revenue breakdown 
$monthlySQL = "SELECT CONCAT(YEAR(b.bill_time), '-', MONTH(b.bill_time)) AS year, MONTH(b.bill_time) AS month, 
               SUM(CASE WHEN b.net_revenue > 0 THEN b.net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = b.bill_id) END) AS monthly_category_revenue
               FROM Bills b
               GROUP BY YEAR(b.bill_time), MONTH(b.bill_time)
               ORDER BY year ASC, month ASC
               LIMIT 15";
$monthlyCategoryRevenue = getCategoryRevenue($link, $monthlySQL);
$pdf->AddPage();
$pdf->ChapterTitle('Monthly Omnichannel Net Settlements');
$header = array('Year-Month','Month Number' , 'Net Settled (Rs)');
$data = array();
if ($monthlyCategoryRevenue) {
    while ($row = mysqli_fetch_assoc($monthlyCategoryRevenue)) {
        $data[] = array($row['year'], $row['month'], "Rs " . number_format($row['monthly_category_revenue'], 2));
    }
}
$pdf->CustomTableThreeColumn($header, $data);

// Daily breakdown by item category
$dailyCatSQL = "SELECT DATE(b.bill_time) AS date, DAY(b.bill_time) AS day, Menu.item_category, SUM(bi.quantity * Menu.item_price) AS daily_category_revenue
             FROM Bills b
             JOIN Bill_Items bi ON b.bill_id = bi.bill_id
             JOIN Menu ON bi.item_id = Menu.item_id
             GROUP BY DATE(b.bill_time), DAY(b.bill_time), Menu.item_category
             ORDER BY date DESC
             LIMIT 15";
$dailyCatRevenue = getCategoryRevenue($link, $dailyCatSQL);
$pdf->AddPage();
$pdf->ChapterTitle('Daily Revenue by Item Category (Gross Product Sales)');
$header = array('Date','Day' , 'Category', 'Revenue (Rs)');
$data = array();
if ($dailyCatRevenue) {
    while ($row = mysqli_fetch_assoc($dailyCatRevenue)) {
        $data[] = array($row['date'], $row['day'], $row['item_category'], "Rs " . number_format($row['daily_category_revenue'], 2));
    }
}
$pdf->CustomTableFourColumn($header, $data);

// Menu item sales
$pdf->AddPage();
$currentMonthEnd = date('Y-m-t');
$sortOrder = 'DESC';

$menuItemSalesQuery = "SELECT Menu.item_name AS item_name, SUM(Bill_Items.quantity) AS total_quantity
                       FROM Bill_Items
                       INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                       INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                       WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                       GROUP BY item_name
                       ORDER BY total_quantity $sortOrder
                       LIMIT 10";
$menuItemSalesResult = mysqli_query($link, $menuItemSalesQuery);
$menuItemSalesResultData = array();
if ($menuItemSalesResult) {
    while ($row = mysqli_fetch_assoc($menuItemSalesResult)) {
        $menuItemSalesResultData[] = array($row['item_name'], $row['total_quantity']);
    }
}
$pdf->ChapterBody("10 Most Ordered Menu Items this Month ( "  . $currentMonthStart . " to " . $currentMonthEnd . " ) :\n");
$pdf->CustomTable(array('Item Name', 'Quantity Sold'), $menuItemSalesResultData);

$sortOrder = 'ASC';
$menuItemSalesLeastQuery = "SELECT Menu.item_name AS item_name, SUM(Bill_Items.quantity) AS total_quantity
                       FROM Bill_Items
                       INNER JOIN Menu ON Bill_Items.item_id = Menu.item_id
                       INNER JOIN Bills ON Bill_Items.bill_id = Bills.bill_id
                       WHERE Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                       GROUP BY item_name
                       ORDER BY total_quantity $sortOrder
                       LIMIT 10";
$menuItemSalesLeastResult = mysqli_query($link, $menuItemSalesLeastQuery);
$pdf->AddPage();
$menuItemSalesLeastResultData = array();
if ($menuItemSalesLeastResult) {
    while ($row = mysqli_fetch_assoc($menuItemSalesLeastResult)) {
        $menuItemSalesLeastResultData[] = array($row['item_name'], $row['total_quantity']);
    }
}
$pdf->ChapterBody("10 Least Ordered Menu Items this Month ( "  . $currentMonthStart . " to " . $currentMonthEnd . " ) :\n");
$pdf->CustomTable(array('Item Name', 'Quantity Sold'), $menuItemSalesLeastResultData);

$pdf->AddPage();
$menuItemNoOrdersQuery = "SELECT
    Menu.item_name,
    0 AS total_quantity
FROM
    Menu
WHERE NOT EXISTS (
    SELECT 1
    FROM Bill_Items
    WHERE Menu.item_id = Bill_Items.item_id
);";
$menuItemNoOrdersResult = mysqli_query($link, $menuItemNoOrdersQuery);
$menuItemNoOrdersResultData = array();
if ($menuItemNoOrdersResult) {
    while ($row = mysqli_fetch_assoc($menuItemNoOrdersResult)) {
        $menuItemNoOrdersResultData[] = array($row['item_name'], '0');
    }
}
$pdf->ChapterBody("Menu Items with Zero Orders recorded this Month :\n");
$pdf->CustomTable(array('Item Name', 'Quantity Sold'), $menuItemNoOrdersResultData);

$pdf->Output('RevenueReport.pdf', 'I'); // Use 'I' to send inline to browser or 'D' for forcing download
?>
