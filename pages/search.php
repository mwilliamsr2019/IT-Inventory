<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$search = $_GET['search'] ?? '';
$make = $_GET['make'] ?? '';
$model = $_GET['model'] ?? '';
$serial = $_GET['serial'] ?? '';
$property = $_GET['property'] ?? '';
$location = $_GET['location'] ?? '';
$use_case = $_GET['use_case'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(i.make LIKE :search OR i.model LIKE :search2 OR i.serial_number LIKE :search3 OR i.property_number LIKE :search4)";
    $searchParam = "%$search%";
    $params[':search'] = $searchParam;
    $params[':search2'] = $searchParam;
    $params[':search3'] = $searchParam;
    $params[':search4'] = $searchParam;
}

if ($make) {
    $where[] = "i.make LIKE :make";
    $params[':make'] = "%$make%";
}

if ($model) {
    $where[] = "i.model LIKE :model";
    $params[':model'] = "%$model%";
}

if ($serial) {
    $where[] = "i.serial_number LIKE :serial";
    $params[':serial'] = "%$serial%";
}

if ($property) {
    $where[] = "i.property_number LIKE :property";
    $params[':property'] = "%$property%";
}

if ($location) {
    $where[] = "i.location_id = :location";
    $params[':location'] = $location;
}

if ($use_case) {
    $where[] = "i.use_case_id = :use_case";
    $params[':use_case'] = $use_case;
}

if ($status) {
    $where[] = "i.status = :status";
    $params[':status'] = $status;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$database = new Database();
$db = $database->connect();

$query = "SELECT i.*, l.name as location_name, uc.name as use_case_name 
          FROM inventory i 
          LEFT JOIN locations l ON i.location_id = l.id 
          LEFT JOIN use_cases uc ON i.use_case_id = uc.id 
          $whereClause
          ORDER BY i.created_at DESC";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$items = $stmt->fetchAll();

$locations = getLocations();
$use_cases = getUseCases();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Search Inventory</h1>
    <a href="index.php?page=inventory" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Inventory
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Search Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="search">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">General Search</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search all fields..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Make</label>
                    <input type="text" class="form-control" name="make" 
                           value="<?php echo htmlspecialchars($make); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Model</label>
                    <input type="text" class="form-control" name="model" 
                           value="<?php echo htmlspecialchars($model); ?>">
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-3">
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-control" name="serial" 
                           value="<?php echo htmlspecialchars($serial); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Property Number</label>
                    <input type="text" class="form-control" name="property" 
                           value="<?php echo htmlspecialchars($property); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <select class="form-select" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo $loc['id']; ?>" 
                                    <?php echo $location == $loc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Use Case</label>
                    <select class="form-select" name="use_case">
                        <option value="">All Use Cases</option>
                        <?php foreach ($use_cases as $uc): ?>
                            <option value="<?php echo $uc['id']; ?>" 
                                    <?php echo $use_case == $uc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($uc['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="excess" <?php echo $status == 'excess' ? 'selected' : ''; ?>>Excess</option>
                        <option value="disposed" <?php echo $status == 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                        <option value="maintenance" <?php echo $status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="index.php?page=search" class="btn btn-secondary">
                            <i class="fas fa-refresh"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            Search Results (<?php echo count($items); ?> items found)
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($items)): ?>
            <p class="text-muted">No items found matching your criteria.</p>
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
                        <?php foreach ($items as $item): ?>
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
</div>