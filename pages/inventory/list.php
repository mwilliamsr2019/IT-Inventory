<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 25;
$offset = ($page - 1) * $limit;

// Search parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$use_case = $_GET['use_case'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
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

// Get total count
$database = new Database();
$db = $database->connect();

$countQuery = "SELECT COUNT(*) FROM inventory i $whereClause";
$countStmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Get inventory items
$query = "SELECT i.*, l.name as location_name, uc.name as use_case_name 
          FROM inventory i 
          LEFT JOIN locations l ON i.location_id = l.id 
          LEFT JOIN use_cases uc ON i.use_case_id = uc.id 
          $whereClause
          ORDER BY i.created_at DESC 
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$locations = getLocations();
$use_cases = getUseCases();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Inventory Items</h1>
    <a href="index.php?page=inventory&action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Item
    </a>
</div>

<!-- Search and Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-search"></i> Search & Filter
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="inventory">
            <div class="row">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Make, model, serial or property #" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-select" id="location" name="location">
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
                    <label for="use_case" class="form-label">Use Case</label>
                    <select class="form-select" id="use_case" name="use_case">
                        <option value="">All Use Cases</option>
                        <?php foreach ($use_cases as $uc): ?>
                            <option value="<?php echo $uc['id']; ?>" 
                                    <?php echo $use_case == $uc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($uc['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="excess" <?php echo $status == 'excess' ? 'selected' : ''; ?>>Excess</option>
                        <option value="disposed" <?php echo $status == 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                        <option value="maintenance" <?php echo $status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="index.php?page=inventory" class="btn btn-secondary">
                        <i class="fas fa-refresh"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Property #</th>
                        <th>Make</th>
                        <th>Model</th>
                        <th>Serial #</th>
                        <th>Location</th>
                        <th>Use Case</th>
                        <th>Warranty End</th>
                        <th>Excess Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="10" class="text-center">No inventory items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['property_number']); ?></td>
                                <td><?php echo htmlspecialchars($item['make']); ?></td>
                                <td><?php echo htmlspecialchars($item['model']); ?></td>
                                <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                                <td><?php echo htmlspecialchars($item['location_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['use_case_name'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($item['warranty_end_date']); ?></td>
                                <td><?php echo formatDate($item['excess_date']); ?></td>
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
                                    <a href="index.php?page=inventory&action=delete&id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-danger" title="Delete" 
                                       onclick="return confirm('Are you sure you want to delete this item?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=inventory&p=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&use_case=<?php echo urlencode($use_case); ?>&status=<?php echo urlencode($status); ?>">Previous</a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="index.php?page=inventory&p=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&use_case=<?php echo urlencode($use_case); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="index.php?page=inventory&p=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&location=<?php echo urlencode($location); ?>&use_case=<?php echo urlencode($use_case); ?>&status=<?php echo urlencode($status); ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>