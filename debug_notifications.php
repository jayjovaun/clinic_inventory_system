<?php
require_once 'config/Database.php';
require_once 'models/Medicine.php';

// Set header to display as HTML
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .expired { background-color: rgba(231, 74, 59, 0.15); }
        .expiring-soon { background-color: rgba(246, 194, 62, 0.15); }
        .low-stock { background-color: rgba(52, 152, 219, 0.15); }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Notifications</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Current Date Information</h2>
            </div>
            <div class="card-body">
                <?php
                $currentDate = date('Y-m-d');
                $currentTimestamp = time();
                ?>
                <p><strong>Current Date (Y-m-d):</strong> <?php echo $currentDate; ?></p>
                <p><strong>Current Timestamp:</strong> <?php echo $currentTimestamp; ?></p>
                <p><strong>Formatted Date:</strong> <?php echo date('F j, Y', $currentTimestamp); ?></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>All Medicines</h2>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Expiration Date</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Days Until Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $db = Database::getInstance();
                            $stmt = $db->prepare("SELECT id, name, brand, expiration_date, quantity FROM medicines");
                            $stmt->execute();
                            $medicines = $stmt->fetchAll();
                            
                            foreach ($medicines as $med) {
                                $expDate = strtotime($med['expiration_date']);
                                $daysUntil = floor(($expDate - $currentTimestamp) / (60 * 60 * 24));
                                $isExpired = ($expDate <= $currentTimestamp);
                                $isExpiringSoon = (!$isExpired && $daysUntil <= 30);
                                $isLowStock = ($med['quantity'] <= 10);
                                
                                $rowClass = '';
                                $status = 'Normal';
                                
                                if ($isExpired) {
                                    $rowClass = 'expired';
                                    $status = 'EXPIRED';
                                } elseif ($isExpiringSoon) {
                                    $rowClass = 'expiring-soon';
                                    $status = 'EXPIRING SOON';
                                }
                                
                                if ($isLowStock) {
                                    $status .= ($status == 'Normal' ? '' : ' & ') . 'LOW STOCK';
                                    if ($rowClass == '') {
                                        $rowClass = 'low-stock';
                                    }
                                }
                                
                                echo "<tr class='$rowClass'>";
                                echo "<td>{$med['id']}</td>";
                                echo "<td>{$med['name']}</td>";
                                echo "<td>{$med['brand']}</td>";
                                echo "<td>{$med['expiration_date']} (" . date('F j, Y', $expDate) . ")</td>";
                                echo "<td>{$med['quantity']}</td>";
                                echo "<td>$status</td>";
                                echo "<td>$daysUntil</td>";
                                echo "</tr>";
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Direct SQL Query Results</h2>
            </div>
            <div class="card-body">
                <h3>Expired Items (expiration_date <= '<?php echo $currentDate; ?>')</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Expiration Date</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->prepare("SELECT id, name, brand, expiration_date, quantity FROM medicines WHERE expiration_date <= ?");
                            $stmt->execute([$currentDate]);
                            $expired = $stmt->fetchAll();
                            
                            if (count($expired) > 0) {
                                foreach ($expired as $med) {
                                    echo "<tr class='expired'>";
                                    echo "<td>{$med['id']}</td>";
                                    echo "<td>{$med['name']}</td>";
                                    echo "<td>{$med['brand']}</td>";
                                    echo "<td>{$med['expiration_date']}</td>";
                                    echo "<td>{$med['quantity']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No expired items found</td></tr>";
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <h3 class="mt-4">Expiring Soon Items</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Expiration Date</th>
                            <th>Quantity</th>
                            <th>Days Until Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $futureDate = date('Y-m-d', strtotime("+30 days"));
                            $stmt = $db->prepare("SELECT id, name, brand, expiration_date, quantity FROM medicines WHERE expiration_date > ? AND expiration_date <= ?");
                            $stmt->execute([$currentDate, $futureDate]);
                            $expiringSoon = $stmt->fetchAll();
                            
                            if (count($expiringSoon) > 0) {
                                foreach ($expiringSoon as $med) {
                                    $expDate = strtotime($med['expiration_date']);
                                    $daysUntil = floor(($expDate - $currentTimestamp) / (60 * 60 * 24));
                                    
                                    echo "<tr class='expiring-soon'>";
                                    echo "<td>{$med['id']}</td>";
                                    echo "<td>{$med['name']}</td>";
                                    echo "<td>{$med['brand']}</td>";
                                    echo "<td>{$med['expiration_date']}</td>";
                                    echo "<td>{$med['quantity']}</td>";
                                    echo "<td>$daysUntil</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No expiring soon items found</td></tr>";
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='6'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Medicine Model Results</h2>
            </div>
            <div class="card-body">
                <h3>Using Medicine->getExpired()</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Expiration Date</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $medicine = new Medicine();
                            $expired = $medicine->getExpired();
                            
                            if (count($expired) > 0) {
                                foreach ($expired as $med) {
                                    echo "<tr class='expired'>";
                                    echo "<td>{$med['id']}</td>";
                                    echo "<td>{$med['name']}</td>";
                                    echo "<td>{$med['brand']}</td>";
                                    echo "<td>{$med['expiration_date']}</td>";
                                    echo "<td>{$med['quantity']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No expired items found</td></tr>";
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <h3 class="mt-4">Using Medicine->getExpiringSoon()</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Expiration Date</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $expiringSoon = $medicine->getExpiringSoon();
                            
                            if (count($expiringSoon) > 0) {
                                foreach ($expiringSoon as $med) {
                                    echo "<tr class='expiring-soon'>";
                                    echo "<td>{$med['id']}</td>";
                                    echo "<td>{$med['name']}</td>";
                                    echo "<td>{$med['brand']}</td>";
                                    echo "<td>{$med['expiration_date']}</td>";
                                    echo "<td>{$med['quantity']}</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No expiring soon items found</td></tr>";
                            }
                        } catch (Exception $e) {
                            echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
