<?php
session_start(); // Ensure session is started
require_once '../posBackend/checkIfLoggedIn.php';
?>
<?php 
include '../inc/dashHeader.php'; 
require_once '../config.php';
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

// Get the current month and year in the format 'YYYY-MM'
$currentMonth = date('Y-m');


?>

<div class="container-fluid" style="margin-top: 3rem; padding-left: 16rem; padding-right: 2rem;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Most Purchased Items (<?php echo $currentMonth; ?>)</h3>
    </div>
    
    <div class="row">
        <!-- Left Column: Stacked charts (Most Sold, Least Sold) -->
        <div class="col-lg-4 col-md-5">
            <div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 16px;">
                <h6 class="font-weight-bold mb-2 text-center text-primary"><i class="fa fa-arrow-up text-success mr-2"></i> Most Sold Items</h6>
                <div id="mostPurchased" style="width: 100%; height: 320px;"></div>
            </div>
            <div class="card p-3 mb-4 shadow-sm border-0" style="border-radius: 16px;">
                <h6 class="font-weight-bold mb-2 text-center text-primary"><i class="fa fa-arrow-down text-danger mr-2"></i> Least Sold Items</h6>
                <div id="leastPurchased" style="width: 100%; height: 320px;"></div>
            </div>
        </div>
        
        <!-- Right Column: Comparison Card & Table -->
        <div class="col-lg-8 col-md-7">
            <div class="row">
                <!-- Comparison Box -->
                <div class="col-md-7">
                    <div class="card p-4 mb-4 shadow-sm border-0" style="border-radius: 20px; min-height: 740px;">
                        <h5 class="font-weight-bold"><i class="fa fa-balance-scale mr-2 text-primary"></i> Product Detail & Comparison</h5>
                        <p class="text-muted small">Select one or more products below to compare their sales performance.</p>
                        
                        <div class="form-group mb-3 position-relative">
                            <label class="font-weight-bold mb-2">Search Product by Name or ID:</label>
                            <input type="text" id="chk-search" placeholder="Type name or ID (e.g. MD1, Margherita...)" class="form-control" oninput="showSearchAutocomplete()" onfocus="showSearchAutocomplete()" autocomplete="off" style="border-radius: 12px !important;">
                            
                            <!-- Autocomplete Floating Dropdown Menu -->
                            <div id="search-autocomplete-dropdown" class="dropdown-menu shadow-lg p-2" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 250px; overflow-y: auto; z-index: 1050; border-radius: 12px; background: #ffffff; border: 1px solid #ced4da;">
                                <!-- Autocomplete items -->
                            </div>
                        </div>
                        
                        <!-- Selected tag chips container -->
                        <div id="selected-compare-tags" class="d-flex flex-wrap mb-3">
                            <!-- Dynamic tags -->
                        </div>
                        
                        <div id="comparison-details" class="mb-3">
                            <div class="text-center py-4 text-muted small"><i class="fa fa-info-circle mr-1"></i> Check products above to load details</div>
                        </div>
                        
                        <div class="card text-center mb-0 mt-3 p-3 border-0" style="background: rgba(0,0,0,0.01); border-radius: 12px;">
                            <h6 class="font-weight-bold mb-2">Comparative Sales Proportion</h6>
                            <div id="comparisonChart" style="width: 100%; height: 230px;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Log Table -->
                <div class="col-md-5">
                    <div class="card p-4 mb-4 shadow-sm border-0" style="border-radius: 20px; min-height: 740px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold mb-0">Sales Log</h5>
                            <div class="btn-group btn-group-sm">
                                <a href="?sortOrder=desc" class="btn btn-outline-primary font-weight-bold <?php echo $sortOrder === 'desc' ? 'active' : ''; ?>">Most</a>
                                <a href="?sortOrder=asc" class="btn btn-outline-primary font-weight-bold <?php echo $sortOrder === 'asc' ? 'active' : ''; ?>">Least</a>
                            </div>
                        </div>
                        
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <?php
                            $jsSalesData = [];
                            $menuItemSalesQuery = "SELECT Menu.item_name, Menu.item_category, COALESCE(SUM(Bill_Items.quantity), 0) AS total_quantity
                                                   FROM Menu
                                                   LEFT JOIN Bill_Items ON Bill_Items.item_id = Menu.item_id
                                                   LEFT JOIN Bills ON Bill_Items.bill_id = Bills.bill_id AND Bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                                   GROUP BY Menu.item_name, Menu.item_category
                                                   ORDER BY total_quantity $sortOrder";
                            $menuItemSalesResult = mysqli_query($link, $menuItemSalesQuery);
                            
                            echo '<table class="table table-hover mb-0" style="font-size: 0.9em;">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th>Item Name</th>';
                            echo '<th class="text-right">Units</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';
                            
                            while ($row = mysqli_fetch_assoc($menuItemSalesResult)) {
                                $jsSalesData[] = [
                                    'name' => $row['item_name'],
                                    'qty' => (int)$row['total_quantity'],
                                    'category' => $row['item_category']
                                ];
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars($row['item_name']) . '</td>';
                                echo '<td class="text-right font-weight-bold">' . $row['total_quantity'] . '</td>';
                                echo '</tr>';
                            }
                            echo '</tbody>';
                            echo '</table>';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Load Google Charts library -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    const salesData = <?php echo json_encode($jsSalesData); ?>;
    
    // Cache all items from salesData
    const allMenuItems = salesData.map(item => ({
        id: item.id || "??",
        name: item.name,
        category: item.category || "Main Dish"
    }));
    
    let selectedCompareItems = []; // Selected item names for comparison

    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(initDashboard);

    let mostChart, leastChart, compChart;

    function initDashboard() {
        drawMostChart();
        drawLeastChart();
        populateProductChecklist(); // For tag list setup
        
        // Close autocomplete dropdown when clicking outside
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("search-autocomplete-dropdown");
            const input = document.getElementById("chk-search");
            if (dropdown && input && !dropdown.contains(e.target) && e.target !== input) {
                dropdown.style.display = "none";
            }
        });
    }

    function drawMostChart() {
        const sorted = [...salesData].sort((a,b) => b.qty - a.qty).slice(0, 5);
        
        const chartData = [['Item Name', 'Units Sold']];
        sorted.forEach(item => {
            chartData.push([item.name, item.qty]);
        });
        
        const data = google.visualization.arrayToDataTable(chartData);
        
        const options = {
            pieHole: 0.4,
            chartArea: {width: '90%', height: '80%'},
            legend: {position: 'bottom'},
            pieSliceText: 'value'
        };

        mostChart = new google.visualization.PieChart(document.getElementById('mostPurchased'));
        mostChart.draw(data, options);
    }

    function drawLeastChart() {
        const sorted = [...salesData].sort((a,b) => a.qty - b.qty).slice(0, 5);
        
        const chartData = [['Item Name', 'Units Sold']];
        sorted.forEach(item => {
            chartData.push([item.name, item.qty]);
        });
        
        const data = google.visualization.arrayToDataTable(chartData);
        
        const options = {
            pieHole: 0.4,
            chartArea: {width: '90%', height: '80%'},
            legend: {position: 'bottom'},
            pieSliceText: 'value'
        };

        leastChart = new google.visualization.PieChart(document.getElementById('leastPurchased'));
        leastChart.draw(data, options);
    }

    function populateProductChecklist() {
        renderCompareTags();
        onCompareItemsChange();
    }

    function showSearchAutocomplete() {
        const query = document.getElementById("chk-search").value.toLowerCase().trim();
        const dropdown = document.getElementById("search-autocomplete-dropdown");
        
        const matches = allMenuItems.filter(item => {
            const matchesName = item.name.toLowerCase().includes(query);
            const matchesId = item.id.toLowerCase().includes(query);
            return matchesName || matchesId;
        });
        
        if (matches.length === 0) {
            dropdown.innerHTML = `<div class="dropdown-item text-muted text-center py-2 small">No matching items found</div>`;
            dropdown.style.display = "block";
            return;
        }
        
        let html = "";
        matches.forEach(item => {
            const isSelected = selectedCompareItems.includes(item.name);
            const checkIcon = isSelected ? `<i class="fa fa-check text-success mr-2"></i>` : "";
            const selectedStyle = isSelected ? "font-weight-bold" : "";
            
            html += `
                <a class="dropdown-item py-2 d-flex justify-content-between align-items-center ${selectedStyle}" href="javascript:void(0)" onclick="selectCompareItem('${item.name}')" style="cursor: pointer; border-radius: 8px; color: #1F2937;">
                    <span><strong class="text-secondary mr-2">[${item.id}]</strong> ${item.name}</span>
                    ${checkIcon}
                </a>
            `;
        });
        
        dropdown.innerHTML = html;
        dropdown.style.display = "block";
    }

    function selectCompareItem(name) {
        if (!selectedCompareItems.includes(name)) {
            selectedCompareItems.push(name);
        } else {
            selectedCompareItems = selectedCompareItems.filter(n => n !== name);
        }
        
        document.getElementById("chk-search").value = "";
        document.getElementById("search-autocomplete-dropdown").style.display = "none";
        
        renderCompareTags();
        onCompareItemsChange();
    }

    function removeCompareItem(name) {
        selectedCompareItems = selectedCompareItems.filter(n => n !== name);
        renderCompareTags();
        onCompareItemsChange();
    }

    function renderCompareTags() {
        const container = document.getElementById("selected-compare-tags");
        if (!container) return;
        
        if (selectedCompareItems.length === 0) {
            container.innerHTML = "";
            return;
        }
        
        let html = "";
        selectedCompareItems.forEach(name => {
            const menuItem = allMenuItems.find(i => i.name === name) || { id: "??", name: name };
            html += `
                <span class="badge badge-pill badge-primary p-2 mr-2 mb-2 d-inline-flex align-items-center" style="font-size: 0.85em; background-color: #2563EB !important; color: white !important; border-radius: 20px;">
                    <span class="mr-1"><strong class="text-warning">[${menuItem.id}]</strong> ${menuItem.name}</span>
                    <a href="javascript:void(0)" onclick="removeCompareItem('${menuItem.name}')" class="text-white ml-2 font-weight-bold" style="text-decoration: none; font-size: 1.1em; line-height: 1;">&times;</a>
                </span>
            `;
        });
        container.innerHTML = html;
    }

    const getSalesQty = (name) => {
        const record = salesData.find(i => i.name === name);
        return record ? record.qty : 0;
    };

    function onCompareItemsChange() {
        const detailsContainer = document.getElementById("comparison-details");
        
        if (selectedCompareItems.length === 0) {
            detailsContainer.innerHTML = `
                <div class="text-center py-4 text-muted small"><i class="fa fa-info-circle mr-1"></i> Search and select products above to load details</div>
            `;
            drawComparisonChart([]);
            return;
        }
        
        const totalSales = salesData.reduce((acc, curr) => acc + curr.qty, 0);
        
        let html = "";
        if (selectedCompareItems.length === 1) {
            const name = selectedCompareItems[0];
            const menuItem = allMenuItems.find(i => i.name === name) || { id: "??", name: name, category: "Main Dish" };
            const qty = getSalesQty(name);
            const percentage = totalSales > 0 ? ((qty / totalSales) * 100).toFixed(1) : 0;
            
            html = `
                <div class="p-3 mb-2 rounded border" style="background: rgba(0,0,0,0.01);">
                    <h6 class="font-weight-bold mb-2"><i class="fa fa-info-circle mr-2 text-info"></i> Product Details</h6>
                    <div class="row">
                        <div class="col-6 text-muted small">Item ID:</div>
                        <div class="col-6 font-weight-bold text-right">${menuItem.id}</div>
                        <div class="col-6 text-muted small">Name:</div>
                        <div class="col-6 font-weight-bold text-right">${menuItem.name}</div>
                        <div class="col-6 text-muted small">Category:</div>
                        <div class="col-6 text-right">${menuItem.category}</div>
                        <div class="col-6 text-muted small">Units Sold:</div>
                        <div class="col-6 text-warning font-weight-bold text-right">${qty} units</div>
                        <div class="col-6 text-muted small">Market Share:</div>
                        <div class="col-6 text-info font-weight-bold text-right">${percentage}%</div>
                    </div>
                </div>
            `;
        } else {
            html = `
                <div class="p-3 mb-2 rounded border" style="background: rgba(0,0,0,0.01);">
                    <h6 class="font-weight-bold mb-2"><i class="fa fa-columns mr-2 text-info"></i> Comparison Details</h6>
                    <div style="max-height: 150px; overflow-y: auto;">
                        <table class="table table-sm table-borderless mb-0">
                            <thead>
                                <tr class="text-muted border-bottom">
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th class="text-right">Units</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            selectedCompareItems.forEach(name => {
                const menuItem = allMenuItems.find(i => i.name === name) || { id: "??", name: name, category: "Main Dish" };
                const qty = getSalesQty(name);
                html += `
                                <tr>
                                    <td><strong class="text-secondary">${menuItem.id}</strong></td>
                                    <td class="font-weight-bold">${menuItem.name}</td>
                                    <td>${menuItem.category}</td>
                                    <td class="text-right text-warning font-weight-bold">${qty}</td>
                                </tr>
                `;
            });
            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
        
        detailsContainer.innerHTML = html;
        
        // Prep comparison chart data
        const chartItems = selectedCompareItems.map(name => {
            const menuItem = allMenuItems.find(i => i.name === name) || { id: "??", name: name, category: "Main Dish" };
            return {
                name: name,
                qty: getSalesQty(name),
                category: menuItem.category
            };
        });
        
        drawComparisonChart(chartItems);
    }

    function drawComparisonChart(itemsList) {
        const container = document.getElementById('comparisonChart');
        if (!container) return;
        
        if (itemsList.length === 0) {
            container.innerHTML = `<div class="text-center py-5 text-muted small">No items selected</div>`;
            return;
        }
        
        const chartData = [['Item Name', 'Units Sold']];
        itemsList.forEach(item => {
            chartData.push([item.name, item.qty]);
        });
        
        const data = google.visualization.arrayToDataTable(chartData);
        
        const options = {
            pieHole: 0.4,
            chartArea: {width: '90%', height: '80%'},
            legend: {position: 'bottom'},
            pieSliceText: 'value'
        };

        compChart = new google.visualization.PieChart(container);
        compChart.draw(data, options);
    }
</script>
