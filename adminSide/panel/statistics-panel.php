<?php
session_start(); // Ensure session is started
require_once '../posBackend/checkIfLoggedIn.php';
?>
<?php include '../inc/dashHeader.php'; 
require_once '../config.php';

// Get current date
$currentDate = date('Y-m-d');

// Helper calculation query for total revenue (properly handles legacy orders and omnichannel orders)
$totalRevenueTodayQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE DATE(Bills.bill_time) = '$currentDate'";
$totalRevenueTodayResult = mysqli_query($link, $totalRevenueTodayQuery);
$totalRevenueTodayRow = mysqli_fetch_assoc($totalRevenueTodayResult);
$totalRevenueToday = floatval($totalRevenueTodayRow['total_revenue']);

// Calculate total revenue for this week (assuming week starts on Monday)
$currentWeekStart = date('Y-m-d', strtotime('monday this week'));
$totalRevenueThisWeekQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE DATE(Bills.bill_time) >= '$currentWeekStart'";
$totalRevenueThisWeekResult = mysqli_query($link, $totalRevenueThisWeekQuery);
$totalRevenueThisWeekRow = mysqli_fetch_assoc($totalRevenueThisWeekResult);
$totalRevenueThisWeek = floatval($totalRevenueThisWeekRow['total_revenue']);

// Calculate total revenue for this month
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');
$totalRevenueThisMonthQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills WHERE DATE(Bills.bill_time) >= '$currentMonthStart'";
$totalRevenueThisMonthResult = mysqli_query($link, $totalRevenueThisMonthQuery);
$totalRevenueThisMonthRow = mysqli_fetch_assoc($totalRevenueThisMonthResult);
$totalRevenueThisMonth = floatval($totalRevenueThisMonthRow['total_revenue']);

// Calculate all-time total revenue
$totalRevenueQuery = "SELECT SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS total_revenue FROM Bills";
$totalRevenueResult = mysqli_query($link, $totalRevenueQuery);
$totalRevenueRow = mysqli_fetch_assoc($totalRevenueResult);
$totalRevenue = floatval($totalRevenueRow['total_revenue']);

// Calculate channel breakdown for today
$channelBreakdownQuery = "
    SELECT 
        order_source,
        SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS source_revenue,
        COUNT(*) AS order_count,
        SUM(commission_amount) AS total_commission,
        SUM(packaging_charge) AS total_packaging,
        SUM(tax_amount) AS total_tax
    FROM Bills
    WHERE DATE(bill_time) = '$currentDate'
    GROUP BY order_source
";
$channelBreakdownResult = mysqli_query($link, $channelBreakdownQuery);
$channels = [
    'Dine-In' => ['revenue' => 0.0, 'count' => 0],
    'Takeaway' => ['revenue' => 0.0, 'count' => 0],
    'Swiggy' => ['revenue' => 0.0, 'count' => 0],
    'Zomato' => ['revenue' => 0.0, 'count' => 0],
    'ONDC' => ['revenue' => 0.0, 'count' => 0],
    'Website' => ['revenue' => 0.0, 'count' => 0],
    'QR-Table' => ['revenue' => 0.0, 'count' => 0],
    'Direct-Store' => ['revenue' => 0.0, 'count' => 0]
];
$totalCommissionToday = 0;
$totalPackagingToday = 0;
$totalTaxToday = 0;
while ($row = mysqli_fetch_assoc($channelBreakdownResult)) {
    $src = $row['order_source'];
    if (isset($channels[$src])) {
        $channels[$src]['revenue'] = floatval($row['source_revenue']);
        $channels[$src]['count'] = intval($row['order_count']);
    } else {
        $channels[$src] = ['revenue' => floatval($row['source_revenue']), 'count' => intval($row['order_count'])];
    }
    $totalCommissionToday += floatval($row['total_commission']);
    $totalPackagingToday += floatval($row['total_packaging']);
    $totalTaxToday += floatval($row['total_tax']);
}

// Get current month source breakdown for the new Source chart
$monthSourceQuery = "
    SELECT 
        order_source,
        SUM(CASE WHEN net_revenue > 0 THEN net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = Bills.bill_id) END) AS source_revenue
    FROM Bills
    WHERE bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
    GROUP BY order_source
";
$monthSourceResult = mysqli_query($link, $monthSourceQuery);
$monthSources = [];
while ($row = mysqli_fetch_assoc($monthSourceResult)) {
    $monthSources[] = [
        'source' => $row['order_source'],
        'revenue' => floatval($row['source_revenue'])
    ];
}
?>

<style>
    /* Styling for glassmorphism premium dashboard */
    .dashboard-header {
        font-family: 'Outfit', 'Inter', sans-serif;
        font-weight: 700;
        letter-spacing: -0.5px;
        color: #1e293b;
        margin-bottom: 1.5rem;
    }
    .kpi-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        overflow: hidden;
        background: #ffffff;
    }
    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .kpi-card-header {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        font-weight: 600;
    }
    .kpi-card-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #0f172a;
        margin-top: 0.5rem;
        margin-bottom: 0.25rem;
    }
    .kpi-card-sub {
        font-size: 0.75rem;
        color: #94a3b8;
    }
    .source-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25em 0.6em;
        border-radius: 9999px;
        color: #fff;
    }
    .badge-dine-in { background-color: #6366f1; }
    .badge-takeaway { background-color: #3b82f6; }
    .badge-swiggy { background-color: #f97316; }
    .badge-zomato { background-color: #e11d48; }
    .badge-ondc { background-color: #06b6d4; }
    .badge-website { background-color: #10b981; }
    .badge-qr { background-color: #8b5cf6; }
    .badge-direct { background-color: #6b7280; }
    
    .table-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .section-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.25rem;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Main Panel area offset for navigation sidebar -->
        <div class="col-md-10 order-md-1 col" style="margin-top: 3rem; margin-left: 13rem;">
            <div class="container pt-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="dashboard-header">Omnichannel Sales Statistics</h1>
                        <p class="text-muted">Real-time aggregated view of restaurant store, delivery, and direct channels.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="../report/generate_report.php" class="btn btn-dark d-flex align-items-center gap-2" style="border-radius: 10px; padding: 0.6rem 1.2rem; font-weight: 600;">
                            <i class="fa fa-file-pdf"></i> Export PDF Report
                        </a>
                    </div>
                </div>

                <!-- Today's Omnichannel KPI Cards -->
                <h4 class="section-title"><i class="fa fa-calendar-day text-primary mr-2"></i> Today's Sales by Ingestion Channel</h4>
                <div class="row mb-4">
                    <!-- Dine-In KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">Dine-In Sales</span>
                                <span class="source-badge badge-dine-in">Dine-In</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['Dine-In']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['Dine-In']['count']; ?> orders completed today</div>
                        </div>
                    </div>

                    <!-- Takeaway KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">Takeaway Sales</span>
                                <span class="source-badge badge-takeaway">Takeaway</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['Takeaway']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['Takeaway']['count']; ?> orders completed today</div>
                        </div>
                    </div>

                    <!-- Swiggy KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">Swiggy Sales</span>
                                <span class="source-badge badge-swiggy">Swiggy</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['Swiggy']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['Swiggy']['count']; ?> orders processed</div>
                        </div>
                    </div>

                    <!-- Zomato KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">Zomato Sales</span>
                                <span class="source-badge badge-zomato">Zomato</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['Zomato']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['Zomato']['count']; ?> orders processed</div>
                        </div>
                    </div>

                    <!-- ONDC KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">ONDC Sales</span>
                                <span class="source-badge badge-ondc">ONDC</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['ONDC']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['ONDC']['count']; ?> orders processed</div>
                        </div>
                    </div>

                    <!-- Website KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">Website Sales</span>
                                <span class="source-badge badge-website">Website</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['Website']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['Website']['count']; ?> direct web orders</div>
                        </div>
                    </div>

                    <!-- QR Table KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">QR Self-Order</span>
                                <span class="source-badge badge-qr">QR Table</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['QR-Table']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['QR-Table']['count']; ?> tables self-ordered</div>
                        </div>
                    </div>

                    <!-- Direct Store / Call-In KPI -->
                    <div class="col-xl-3 col-sm-6 mb-4">
                        <div class="card kpi-card p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="kpi-card-header">Direct-Store Orders</span>
                                <span class="source-badge badge-direct">Direct</span>
                            </div>
                            <div class="kpi-card-value">Rs <?php echo number_format($channels['Direct-Store']['revenue'], 2); ?></div>
                            <div class="kpi-card-sub"><?php echo $channels['Direct-Store']['count']; ?> direct store calls</div>
                        </div>
                    </div>
                </div>

                <!-- Financial Performance Tables -->
                <div class="row">
                    <!-- Revenue Metrics Table -->
                    <div class="col-lg-7">
                        <div class="table-container">
                            <h4 class="section-title"><i class="fa fa-chart-line text-success mr-2"></i> Omnichannel Revenue Performance</h4>
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Temporal Range</th>
                                        <th scope="col" class="text-right">Aggregated Net Revenue (Rs)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th scope="row"><i class="far fa-clock text-muted mr-2"></i> Total Revenue Today</th>
                                        <td class="text-right font-weight-bold text-dark">Rs <?php echo number_format($totalRevenueToday, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><i class="far fa-calendar-alt text-muted mr-2"></i> Total Revenue This Week</th>
                                        <td class="text-right font-weight-bold text-dark">Rs <?php echo number_format($totalRevenueThisWeek, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><i class="far fa-calendar-check text-muted mr-2"></i> Total Revenue This Month</th>
                                        <td class="text-right font-weight-bold text-dark">Rs <?php echo number_format($totalRevenueThisMonth, 2); ?></td>
                                    </tr>
                                    <tr style="background-color: #f8fafc;">
                                        <th scope="row" class="text-primary"><i class="fa fa-coins mr-2"></i> Total Revenue (All-Time)</th>
                                        <td class="text-right font-weight-bold text-primary" style="font-size: 1.15rem;">Rs <?php echo number_format($totalRevenue, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Platform Commissions Funnel -->
                    <div class="col-lg-5">
                        <div class="table-container">
                            <h4 class="section-title"><i class="fa fa-funnel-dollar text-warning mr-2"></i> Platform Revenue Adjustments (Today)</h4>
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <td><strong>Gross Sales:</strong></td>
                                        <td class="text-right">Rs <?php echo number_format($totalRevenueToday + $totalCommissionToday, 2); ?></td>
                                    </tr>
                                    <tr class="text-danger">
                                        <td><i class="fa fa-percentage mr-1"></i> Platform Commissions:</td>
                                        <td class="text-right">- Rs <?php echo number_format($totalCommissionToday, 2); ?></td>
                                    </tr>
                                    <tr class="text-success">
                                        <td><i class="fa fa-box mr-1"></i> Packaging Charges:</td>
                                        <td class="text-right">+ Rs <?php echo number_format($totalPackagingToday, 2); ?></td>
                                    </tr>
                                    <tr class="text-info">
                                        <td><i class="fa fa-receipt mr-1"></i> Taxes (GST 10%):</td>
                                        <td class="text-right">Rs <?php echo number_format($totalTaxToday, 2); ?></td>
                                    </tr>
                                    <tr class="border-top" style="font-size: 1.05rem;">
                                        <td><strong>Net Settlements Today:</strong></td>
                                        <td class="text-right text-success font-weight-bold">Rs <?php echo number_format($totalRevenueToday, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="alert alert-light border mt-3 mb-0" style="border-radius: 10px; font-size: 0.8rem;">
                                <i class="fa fa-info-circle text-muted mr-1"></i> Commissions and packaging charges are dynamically parsed per provider channel based on active merchant API templates.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Google Charts Visual Analytics -->
                <div class="row mt-2">
                    <div class="col-12">
                        <div class="table-container">
                            <h4 class="section-title mb-4"><i class="fa fa-chart-pie text-info mr-2"></i> Visual Analytics & Trends</h4>
                            
                            <div class="row">
                                <!-- Donut Chart Order Source -->
                                <div class="col-lg-4 col-12 text-center border-right">
                                    <div id="sourceRevenueChart" style="width: 100%; height: 350px;"></div>
                                </div>
                                
                                <!-- Bar Chart Payment Method -->
                                <div class="col-lg-4 col-12 text-center border-right">
                                    <div id="paymentMethodChart" style="width: 100%; height: 350px;"></div>
                                </div>
                                
                                <!-- Donut Chart Payment Method -->
                                <div class="col-lg-4 col-12 text-center">
                                    <div id="paymentMethodDonutChart" style="width: 100%; height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>         
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<?php
// Retrieve payment methods for the month
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

$cardQuery = "
    SELECT
        IFNULL(SUM(CASE WHEN b.net_revenue > 0 THEN b.net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = b.bill_id) END), 0) AS card_revenue
    FROM Bills b
    WHERE b.payment_method LIKE 'Card'
      AND b.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59';
";

$cashQuery = "
    SELECT
        IFNULL(SUM(CASE WHEN b.net_revenue > 0 THEN b.net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = b.bill_id) END), 0) AS cash_revenue
    FROM Bills b
    WHERE b.payment_method LIKE 'Cash'
      AND b.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59';
";

$onlineQuery = "
    SELECT
        IFNULL(SUM(CASE WHEN b.net_revenue > 0 THEN b.net_revenue ELSE (SELECT COALESCE(SUM(bi.quantity * m.item_price), 0) * 1.1 FROM Bill_Items bi JOIN Menu m ON bi.item_id = m.item_id WHERE bi.bill_id = b.bill_id) END), 0) AS online_revenue
    FROM Bills b
    WHERE b.payment_method LIKE 'Online'
      AND b.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59';
";

$cardResult = $link->query($cardQuery);
$cashResult = $link->query($cashQuery);
$onlineResult = $link->query($onlineQuery);

$cardRevenue = ($cardResult && $row = $cardResult->fetch_assoc()) ? floatval($row['card_revenue']) : 0.0;
$cashRevenue = ($cashResult && $row = $cashResult->fetch_assoc()) ? floatval($row['cash_revenue']) : 0.0;
$onlineRevenue = ($onlineResult && $row = $onlineResult->fetch_assoc()) ? floatval($row['online_revenue']) : 0.0;
?>

<script>
// Load the Google Charts library
google.charts.load('current', { packages: ['corechart'] });
google.charts.setOnLoadCallback(drawCharts);

function drawCharts() {
  // 1. Data table for Order Source breakdown (Donut)
  const sourceData = new google.visualization.DataTable();
  sourceData.addColumn('string', 'Order Channel');
  sourceData.addColumn('number', 'Monthly Revenue');
  sourceData.addRows([
    <?php
    foreach ($monthSources as $mSrc) {
        echo "['" . htmlspecialchars($mSrc['source']) . "', " . $mSrc['revenue'] . "],";
    }
    if (empty($monthSources)) {
        echo "['No Sales Yet', 0]";
    }
    ?>
  ]);

  const sourceOptions = {
    title: 'Revenue by Channel - <?php echo date('F Y'); ?>',
    pieHole: 0.4,
    colors: ['#6366f1', '#3b82f6', '#f97316', '#e11d48', '#06b6d4', '#10b981', '#8b5cf6', '#6b7280'],
    chartArea: { width: '90%', height: '80%' },
    legend: { position: 'bottom' }
  };

  const sourceChart = new google.visualization.PieChart(document.getElementById('sourceRevenueChart'));
  sourceChart.draw(sourceData, sourceOptions);

  // 2. Data table for Bar Chart (Payment Methods)
  const barChartData = new google.visualization.DataTable();
  barChartData.addColumn('string', 'Payment Method');
  barChartData.addColumn('number', 'Revenue (Rs)');
  barChartData.addRows([
    ['Card', <?php echo $cardRevenue; ?>],
    ['Cash', <?php echo $cashRevenue; ?>],
    ['Online API', <?php echo $onlineRevenue; ?>]
  ]);

  const barChartOptions = {
    title: 'Revenue Generated - <?php echo date('F Y'); ?>',
    colors: ['#0f172a'],
    legend: { position: 'none' },
    chartArea: { width: '80%', height: '70%' },
    vAxis: { title: 'Amount in Rupees' }
  };

  const barChart = new google.visualization.BarChart(document.getElementById('paymentMethodChart'));
  barChart.draw(barChartData, barChartOptions);

  // 3. Data table for Donut Chart (Payment Methods)
  const donutChartData = new google.visualization.DataTable();
  donutChartData.addColumn('string', 'Payment Method');
  donutChartData.addColumn('number', 'Revenue');
  donutChartData.addRows([
    ['Card', <?php echo $cardRevenue; ?>],
    ['Cash', <?php echo $cashRevenue; ?>],
    ['Online API', <?php echo $onlineRevenue; ?>]
  ]);

  const donutChartOptions = {
    title: 'Payment Settlement Shares - <?php echo date('F Y'); ?>',
    pieHole: 0.4,
    colors: ['#3b82f6', '#10b981', '#f59e0b'],
    chartArea: { width: '90%', height: '80%' },
    legend: { position: 'bottom' }
  };

  const donutChart = new google.visualization.PieChart(document.getElementById('paymentMethodDonutChart'));
  donutChart.draw(donutChartData, donutChartOptions);
}
</script>

<?php include '../inc/dashFooter.php'; ?>
