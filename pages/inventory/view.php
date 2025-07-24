<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);

$database = new Database();
$db = $database->connect();

$query = "SELECT i.*, l.name as location_name, uc.name as use_case_name, u.username as created_by_username
          FROM inventory i 
          LEFT JOIN locations l ON i.location_id = l.id 
          LEFT JOIN use_cases uc ON i.use_case_id = uc.id
          LEFT JOIN users u ON i.created_by = u.id
          WHERE i.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['error'] = 'Inventory item not found.';
    header('Location: index.php?page=inventory');
    exit();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Inventory Item Details</h1>
    <a href="index.php?page=inventory" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Item Information</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Property Number</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['property_number']); ?></dd>
                    
                    <dt class="col-sm-4">Make</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['make']); ?></dd>
                    
                    <dt class="col-sm-4">Model</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['model']); ?></dd>
                    
                    <dt class="col-sm-4">Serial Number</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['serial_number']); ?></dd>
                    
                    <dt class="col-sm-4">Location</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Use Case</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['use_case_name'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Warranty End Date</dt>
                    <dd class="col-sm-8"><?php echo formatDate($item['warranty_end_date']); ?></dd>
                    
                    <dt class="col-sm-4">Excess Date</dt>
                    <dd class="col-sm-8"><?php echo formatDate($item['excess_date']); ?></dd>
                    
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8"><?php echo displayStatusBadge($item['status']); ?></dd>
                    
                    <dt class="col-sm-4">Created By</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($item['created_by_username'] ?? 'Unknown'); ?></dd>
                    
                    <dt class="col-sm-4">Created At</dt>
                    <dd class="col-sm-8"><?php echo formatDate($item['created_at']); ?></dd>
                    
                    <dt class="col-sm-4">Updated At</dt>
                    <dd class="col-sm-8"><?php echo formatDate($item['updated_at']); ?></dd>
                    
                    <?php if ($item['notes']): ?>
                    <dt class="col-sm-4">Notes</dt>
                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($item['notes'])); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Actions</h5>
            </div>
            <div class="card-body">
                <a href="index.php?page=inventory&action=edit&id=<?php echo $item['id']; ?>" 
                   class="btn btn-warning w-100 mb-2">
                    <i class="fas fa-edit"></i> Edit Item
                </a>
                <a href="index.php?page=inventory&action=delete&id=<?php echo $item['id']; ?>" 
                   class="btn btn-danger w-100 mb-2" 
                   onclick="return confirm('Are you sure you want to delete this item?')">
                    <i class="fas fa-trash"></i> Delete Item
                </a>
                <a href="index.php?page=inventory" class="btn btn-secondary w-100">
                    <i class="fas fa-list"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>