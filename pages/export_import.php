<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Verify CSRF token for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request - CSRF token mismatch');
    }
}

// Handle export
if (isset($_POST['export'])) {
    logSecurityEvent('DATA_EXPORT', $_SESSION['user_id'] ?? null, 'CSV export initiated');
    
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT i.make, i.model, i.serial_number, i.property_number, 
                     i.warranty_end_date, i.excess_date, 
                     uc.name as use_case, l.name as location, 
                     i.status, i.notes
              FROM inventory i
              LEFT JOIN locations l ON i.location_id = l.id
              LEFT JOIN use_cases uc ON i.use_case_id = uc.id
              ORDER BY i.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $items = $stmt->fetchAll();
    
    // Create CSV content
    $csv = "Make,Model,Serial Number,Property Number,Warranty End Date,Excess Date,Use Case,Location,Status,Notes\n";
    
    foreach ($items as $item) {
        $csv .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
            $item['make'],
            $item['model'],
            $item['serial_number'],
            $item['property_number'],
            $item['warranty_end_date'],
            $item['excess_date'],
            $item['use_case'] ?? 'N/A',
            $item['location'] ?? 'N/A',
            $item['status'],
            str_replace('"', '""', $item['notes'] ?? '')
        );
    }
    
    // Send CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_export_' . date('Y-m-d_H-i-s') . '.csv"');
    echo $csv;
    exit();
}

// Handle import
$import_error = '';
$import_success = '';

if (isset($_POST['import']) && isset($_FILES['csv_file'])) {
    logSecurityEvent('DATA_IMPORT', $_SESSION['user_id'] ?? null, 'CSV import initiated');
    
    $file = $_FILES['csv_file'];
    
    if (!validateFileUpload($file, ['csv'])) {
        $import_error = "Invalid file. Please upload a valid CSV file (max 10MB).";
        logSecurityEvent('INVALID_FILE_UPLOAD', $_SESSION['user_id'] ?? null, "File type: {$file['type']}");
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        
        if ($handle !== false) {
            $header = fgetcsv($handle);
            $expected_headers = ['Make', 'Model', 'Serial Number', 'Property Number', 'Warranty End Date', 'Excess Date', 'Use Case', 'Location', 'Status', 'Notes'];
            
            if ($header === $expected_headers) {
                $database = new Database();
                $db = $database->connect();
                
                $success_count = 0;
                $error_count = 0;
                
                while (($data = fgetcsv($handle)) !== false) {
                    // Validate required fields
                    if (empty($data[0]) || empty($data[1]) || empty($data[2]) || empty($data[3])) {
                        $error_count++;
                        continue;
                    }
                    
                    // Validate serial and property numbers
                    if (!validateSerialNumber($data[2]) || !validatePropertyNumber($data[3])) {
                        $error_count++;
                        continue;
                    }
                    
                    // Get location ID
                    $location_id = null;
                    if (!empty($data[7]) && $data[7] !== 'N/A') {
                        $stmt = $db->prepare("SELECT id FROM locations WHERE name = :name");
                        $stmt->bindParam(':name', $data[7]);
                        $stmt->execute();
                        $location = $stmt->fetch();
                        $location_id = $location['id'] ?? null;
                    }
                    
                    // Get use case ID
                    $use_case_id = null;
                    if (!empty($data[6]) && $data[6] !== 'N/A') {
                        $stmt = $db->prepare("SELECT id FROM use_cases WHERE name = :name");
                        $stmt->bindParam(':name', $data[6]);
                        $stmt->execute();
                        $use_case = $stmt->fetch();
                        $use_case_id = $use_case['id'] ?? null;
                    }
                    
                    try {
                        $query = "INSERT INTO inventory (make, model, serial_number, property_number, 
                                  warranty_end_date, excess_date, use_case_id, location_id, 
                                  notes, status, created_by) 
                                  VALUES (:make, :model, :serial_number, :property_number, 
                                  :warranty_end_date, :excess_date, :use_case_id, :location_id, 
                                  :notes, :status, :created_by)";
                        
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':make', $data[0]);
                        $stmt->bindParam(':model', $data[1]);
                        $stmt->bindParam(':serial_number', $data[2]);
                        $stmt->bindParam(':property_number', $data[3]);
                        $stmt->bindParam(':warranty_end_date', $data[4]);
                        $stmt->bindParam(':excess_date', $data[5]);
                        $stmt->bindParam(':use_case_id', $use_case_id);
                        $stmt->bindParam(':location_id', $location_id);
                        $stmt->bindParam(':notes', $data[9]);
                        $stmt->bindParam(':status', $data[8]);
                        $stmt->bindParam(':created_by', $_SESSION['user_id']);
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    } catch (PDOException $e) {
                        $error_count++;
                    }
                }
                
                fclose($handle);
                $import_success = "Import completed: $success_count items imported, $error_count errors.";
                logSecurityEvent('DATA_IMPORT_COMPLETED', $_SESSION['user_id'] ?? null, "Success: $success_count, Errors: $error_count");
            } else {
                $import_error = "Invalid CSV format. Please use the export format.";
                logSecurityEvent('INVALID_CSV_FORMAT', $_SESSION['user_id'] ?? null);
            }
        } else {
            $import_error = "Could not read the uploaded file.";
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Export & Import Data</h1>
    <a href="index.php?page=inventory" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Inventory
    </a>
</div>

<?php if ($import_error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($import_error); ?></div>
<?php endif; ?>

<?php if ($import_success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($import_success); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-download"></i> Export Data
                </h5>
            </div>
            <div class="card-body">
                <p>Export all inventory data as CSV (Excel compatible).</p>
                <form method="POST" action="index.php?page=export">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" name="export" class="btn btn-success">
                        <i class="fas fa-download"></i> Export to CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-upload"></i> Import Data
                </h5>
            </div>
            <div class="card-body">
                <p>Import inventory data from CSV file.</p>
                <form method="POST" action="index.php?page=export" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                               accept=".csv" required>
                    </div>
                    <button type="submit" name="import" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import from CSV
                    </button>
                </form>
                <div class="mt-3">
                    <small class="text-muted">
                        <strong>Format:</strong> Make, Model, Serial Number, Property