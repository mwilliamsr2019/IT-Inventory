<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'make' => sanitizeInput($_POST['make'] ?? ''),
        'model' => sanitizeInput($_POST['model'] ?? ''),
        'serial_number' => sanitizeInput($_POST['serial_number'] ?? ''),
        'property_number' => sanitizeInput($_POST['property_number'] ?? ''),
        'warranty_end_date' => $_POST['warranty_end_date'] ?? '',
        'excess_date' => $_POST['excess_date'] ?? '',
        'use_case_id' => intval($_POST['use_case_id'] ?? 0),
        'location_id' => intval($_POST['location_id'] ?? 0),
        'notes' => sanitizeInput($_POST['notes'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? 'active')
    ];
    
    $errors = validateInventoryData($data);
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->connect();
        
        $query = "INSERT INTO inventory (make, model, serial_number, property_number, warranty_end_date, 
                  excess_date, use_case_id, location_id, notes, status, created_by) 
                  VALUES (:make, :model, :serial_number, :property_number, :warranty_end_date, 
                  :excess_date, :use_case_id, :location_id, :notes, :status, :created_by)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':make', $data['make']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':serial_number', $data['serial_number']);
        $stmt->bindParam(':property_number', $data['property_number']);
        $stmt->bindParam(':warranty_end_date', $data['warranty_end_date']);
        $stmt->bindParam(':excess_date', $data['excess_date']);
        $stmt->bindParam(':use_case_id', $data['use_case_id']);
        $stmt->bindParam(':location_id', $data['location_id']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Inventory item added successfully!';
            header('Location: index.php?page=inventory');
            exit();
        } else {
            $error = 'Error adding inventory item. Please try again.';
        }
    }
}

$locations = getLocations();
$use_cases = getUseCases();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Add New Inventory Item</h1>
    <a href="index.php?page=inventory" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo htmlspecialchars($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="make" class="form-label">Make *</label>
                        <input type="text" class="form-control" id="make" name="make" 
                               value="<?php echo htmlspecialchars($_POST['make'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="model" class="form-label">Model *</label>
                        <input type="text" class="form-control" id="model" name="model" 
                               value="<?php echo htmlspecialchars($_POST['model'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="serial_number" class="form-label">Serial Number *</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" 
                               value="<?php echo htmlspecialchars($_POST['serial_number'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="property_number" class="form-label">Property Number *</label>
                        <input type="text" class="form-control" id="property_number" name="property_number" 
                               value="<?php echo htmlspecialchars($_POST['property_number'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="location_id" class="form-label">Location</label>
                        <select class="form-select" id="location_id" name="location_id">
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" 
                                        <?php echo (intval($_POST['location_id'] ?? 0) == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="use_case_id" class="form-label">Use Case</label>
                        <select class="form-select" id="use_case_id" name="use_case_id">
                            <option value="">Select Use Case</option>
                            <?php foreach ($use_cases as $use_case): ?>
                                <option value="<?php echo $use_case['id']; ?>" 
                                        <?php echo (intval($_POST['use_case_id'] ?? 0) == $use_case['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($use_case['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="warranty_end_date" class="form-label">Warranty End Date</label>
                        <input type="date" class="form-control" id="warranty_end_date" name="warranty_end_date" 
                               value="<?php echo htmlspecialchars($_POST['warranty_end_date'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="excess_date" class="form-label">Excess Date</label>
                        <input type="date" class="form-control" id="excess_date" name="excess_date" 
                               value="<?php echo htmlspecialchars($_POST['excess_date'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo ($_POST['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="excess" <?php echo ($_POST['status'] ?? '') == 'excess' ? 'selected' : ''; ?>>Excess</option>
                            <option value="disposed" <?php echo ($_POST['status'] ?? '') == 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                            <option value="maintenance" <?php echo ($_POST['status'] ?? '') == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php?page=inventory" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Item</button>
            </div>
        </form>
    </div>
</div>