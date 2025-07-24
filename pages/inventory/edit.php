<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = intval($_GET['id'] ?? 0);
$error = '';

$database = new Database();
$db = $database->connect();

$query = "SELECT * FROM inventory WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$item = $stmt->fetch();

if (!$item) {
    $_SESSION['error'] = 'Item not found';
    header('Location: index.php?page=inventory');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = sanitizeInput($_POST['make'] ?? '');
    $model = sanitizeInput($_POST['model'] ?? '');
    $serial_number = sanitizeInput($_POST['serial_number'] ?? '');
    $property_number = sanitizeInput($_POST['property_number'] ?? '');
    $warranty_end_date = $_POST['warranty_end_date'] ?? '';
    $excess_date = $_POST['excess_date'] ?? '';
    $use_case_id = intval($_POST['use_case_id'] ?? 0);
    $location_id = intval($_POST['location_id'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? 'active');

    $data = [
        'id' => $id,
        'make' => $make,
        'model' => $model,
        'serial_number' => $serial_number,
        'property_number' => $property_number,
        'warranty_end_date' => $warranty_end_date,
        'excess_date' => $excess_date,
        'use_case_id' => $use_case_id,
        'location_id' => $location_id,
        'notes' => $notes,
        'status' => $status
    ];
    
    $errors = validateInventoryData($data);
    
    if (empty($errors)) {
        $query = "UPDATE inventory SET 
                  make = :make, model = :model, serial_number = :serial_number, 
                  property_number = :property_number, warranty_end_date = :warranty_end_date, 
                  excess_date = :excess_date, use_case_id = :use_case_id, 
                  location_id = :location_id, notes = :notes, status = :status
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':make', $make);
        $stmt->bindParam(':model', $model);
        $stmt->bindParam(':serial_number', $serial_number);
        $stmt->bindParam(':property_number', $property_number);
        $stmt->bindParam(':warranty_end_date', $warranty_end_date);
        $stmt->bindParam(':excess_date', $excess_date);
        $stmt->bindParam(':use_case_id', $use_case_id);
        $stmt->bindParam(':location_id', $location_id);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Updated successfully!';
            header('Location: index.php?page=inventory');
            exit();
        } else {
            $error = 'Error updating item';
        }
    }
}

$locations = getLocations();
$use_cases = getUseCases();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Edit Inventory Item</h1>
    <a href="index.php?page=inventory" class="btn btn-secondary">Back to List</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Make *</label>
                        <input type="text" name="make" class="form-control" 
                               value="<?php echo htmlspecialchars($item['make']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Model *</label>
                        <input type="text" name="model" class="form-control" 
                               value="<?php echo htmlspecialchars($item['model']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Serial Number *</label>
                        <input type="text" name="serial_number" class="form-control" 
                               value="<?php echo htmlspecialchars($item['serial_number']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Property Number *</label>
                        <input type="text" name="property_number" class="form-control" 
                               value="<?php echo htmlspecialchars($item['property_number']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Location</label>
                        <select name="location_id" class="form-select">
                            <option value="">Select Location</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo $location['id']; ?>" 
                                        <?php echo ($item['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Use Case</label>
                        <select name="use_case_id" class="form-select">
                            <option value="">Select Use Case</option>
                            <?php foreach ($use_cases as $use_case): ?>
                                <option value="<?php echo $use_case['id']; ?>" 
                                        <?php echo ($item['use_case_id'] == $use_case['id']) ? 'selected' : ''; ?>>
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
                        <label>Warranty End Date</label>
                        <input type="date" name="warranty_end_date" class="form-control" 
                               value="<?php echo htmlspecialchars($item['warranty_end_date']); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Excess Date</label>
                        <input type="date" name="excess_date" class="form-control" 
                               value="<?php echo htmlspecialchars($item['excess_date']); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($item['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="excess" <?php echo ($item['status'] == 'excess') ? 'selected' : ''; ?>>Excess</option>
                            <option value="disposed" <?php echo ($item['status'] == 'disposed') ? 'selected' : ''; ?>>Disposed</option>
                            <option value="maintenance" <?php echo ($item['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($item['notes']); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php?page=inventory" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>