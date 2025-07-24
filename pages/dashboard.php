<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$totalInventory = getInventoryCount();
$warrantyExpiring = getWarrantyExpiringCount();
$excessItems = getExcessCount();

// Get recent inventory items
$database = new Database();
$db = $database->connect();

$query = "SELECT i.*, l.name as location_name, uc.name as use_case_name 
          FROM inventory i 
          LEFT JOIN locations l ON i.location_id = l.id 
          LEFT JOIN use_cases uc ON i.use_case_id = uc.id 
          ORDER BY i.created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recentItems = $stmt->fetchAll();
?>
<div class="row">
    <div class="col-md-12">
        <h1 class="h2 mb-4">Dashboard</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $totalInventory; ?></h4>
                        <p class="card-text">Total Active Items</p>
                    </div>
                    <div>
                        <i class="fas fa-server fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="index.php?page=inventory" class="text-white">
                    View All <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $warrantyExpiring; ?></h4>
                        <p class="card-text">Warranty Expiring Soon</p>
                    </div>
                    <div>
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="index.php?page=search&warranty=expiring" class="text-white">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title"><?php echo $excessItems; ?></h4>
                        <p class="card-text">Excess Items</p>
                    </div>
                    <div>
                        <i class="fas fa-archive fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="index.php?page=search&status=excess" class="text-white">
                    View Excess <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Inventory Items</h5>
                <a href="index.php?page=inventory&action=add" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recentItems)): ?>
                    <p class="text-muted">No inventory items found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Property #</th>
                                    <th>Make/Model</th>
                                    <th>Serial #</th>
                                    <th>Location</th>
                                    <th>Use Case</th>
                                    <th>Warranty End</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentItems as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['property_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['make'] . ' ' . $item['model']); ?></td>
                                        <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['use_case_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo formatDate($item['warranty_end_date']); ?></td>
                                        <td><?php echo displayStatusBadge($item['status']); ?></td>
                                        <td>
                                            <a href="index.php?page=inventory&action=view&id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="index.php?page=inventory&action=edit&id=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="index.php?page=inventory" class="btn btn-primary">
                    View All Inventory Items
                </a>
            </div>
        </div>
    </div>
</div>