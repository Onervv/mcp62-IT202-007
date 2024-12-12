<?php
require_once(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

// Pagination settings
$per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($per_page < 1 || $per_page > 100) {
    $per_page = 10;
}
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$username_filter = se($_GET, "username", "", false);
$sort = se($_GET, "sort", "created", false);
$order = se($_GET, "order", "desc", false);

// Build base query for fetching profiles
$base_query = "SELECT DISTINCT lp.*, 
        u.username as owner_username,
        COALESCE(
            (SELECT COUNT(*) 
             FROM UserProfileAssociations 
             WHERE profile_id = lp.id AND is_active = 1), 0
        ) as association_count,
        CASE 
            WHEN lp.is_manual = 0 THEN 'API'
            ELSE 'Manual'
        END as creation_type
        FROM LinkedInProfiles lp
        LEFT JOIN UserProfileAssociations upa ON lp.id = upa.profile_id
        LEFT JOIN Users u ON lp.user_id = u.id";

// Initialize params array
$params = [];

// Add username filter if provided
if (!empty($username_filter)) {
    $base_query .= " WHERE (lp.linkedin_username LIKE :username OR u.username LIKE :username)";
    $params[":username"] = "%$username_filter%";
}

// Add creation type filter
if (isset($_GET['creation_type']) && $_GET['creation_type'] !== 'all') {
    $is_manual = $_GET['creation_type'] === 'manual' ? 1 : 0;
    if (!empty($username_filter)) {
        $base_query .= " AND lp.is_manual = :is_manual";
    } else {
        $base_query .= " WHERE lp.is_manual = :is_manual";
    }
    $params[":is_manual"] = $is_manual;
}

// Add sorting and pagination for the main query
$query = $base_query . " ORDER BY lp.$sort $order LIMIT :limit OFFSET :offset";

// Execute main query for profiles
$db = getDB();
try {
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(":limit", $per_page, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count using a separate query
    $count_query = "SELECT COUNT(DISTINCT lp.id) as total 
                    FROM LinkedInProfiles lp
                    LEFT JOIN UserProfileAssociations upa ON lp.id = upa.profile_id
                    LEFT JOIN Users u ON lp.user_id = u.id";
    
    if (!empty($username_filter)) {
        $count_query .= " WHERE (lp.linkedin_username LIKE :username OR u.username LIKE :username)";
    }
    
    if (isset($_GET['creation_type']) && $_GET['creation_type'] !== 'all') {
        $is_manual = $_GET['creation_type'] === 'manual' ? 1 : 0;
        $count_query .= " AND lp.is_manual = :is_manual";
    }
    
    $stmt = $db->prepare($count_query);
    if (!empty($username_filter)) {
        $stmt->bindValue(":username", "%$username_filter%");
    }
    if (isset($_GET['creation_type']) && $_GET['creation_type'] !== 'all') {
        $is_manual = $_GET['creation_type'] === 'manual' ? 1 : 0;
        $stmt->bindValue(":is_manual", $is_manual);
    }
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    // Calculate total pages
    $total_pages = ceil($total / $per_page);
} catch (PDOException $e) {
    flash("Error fetching profiles: " . var_export($e->errorInfo, true), "danger");
    $results = [];
    $total_pages = 0;
}
?>

<div class="container">
    <h1>Admin Watchlist</h1>
    <h3>Total Profiles: <?php echo $total; ?> (Showing <?php echo count($results); ?>)</h3>

    <!-- Filter Form -->
    <form method="GET" class="mb-3">
        <div class="row">
            <div class="col">
                <label for="username">Filter by Username:</label>
                <input type="text" name="username" id="username" value="<?php se($username_filter); ?>" class="form-control">
            </div>
            <div class="col">
                <label for="limit">Items per page (1-100):</label>
                <input type="number" name="limit" id="limit" min="1" max="100" value="<?php se($per_page); ?>" class="form-control">
            </div>
            <div class="col">
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort" class="form-control">
                    <option value="created" <?php echo $sort === "created" ? "selected" : ""; ?>>Created Date</option>
                    <option value="username" <?php echo $sort === "username" ? "selected" : ""; ?>>Username</option>
                    <option value="is_favorited" <?php echo $sort === "is_favorited" ? "selected" : ""; ?>>Is Favorited</option>
                    <option value="is_manual" <?php echo $sort === "is_manual" ? "selected" : ""; ?>>Source</option>
                </select>
            </div>
            <div class="col">
                <label for="creation_type">Creation Type:</label>
                <select name="creation_type" id="creation_type" class="form-control">
                    <option value="all" <?php echo !isset($_GET['creation_type']) || $_GET['creation_type'] === 'all' ? "selected" : ""; ?>>All</option>
                    <option value="api" <?php echo isset($_GET['creation_type']) && $_GET['creation_type'] === 'api' ? "selected" : ""; ?>>API</option>
                    <option value="manual" <?php echo isset($_GET['creation_type']) && $_GET['creation_type'] === 'manual' ? "selected" : ""; ?>>Manual</option>
                </select>
            </div>
            <div class="col">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block">Apply Filters</button>
            </div>
        </div>
    </form>

    <?php if (empty($results)): ?>
        <div class="alert alert-info">No results available</div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>LinkedIn Profile</th>
                    <th>Creation Type</th>
                    <th>Is Favorited</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php se($row, "owner_username"); ?></td>
                        <td><?php se($row, "linkedin_username"); ?></td>
                        <td><?php se($row, "creation_type"); ?></td>
                        <td><?php echo $row["is_favorited"] ? "Yes" : "No"; ?></td>
                        <td>
                            <a href="<?php echo get_url('admin/detailed_view_profile.php?id=' . se($row, "id", "", false)); ?>" 
                               class="btn btn-primary btn-sm">View</a>
                            <button onclick="deleteAssociation(<?php se($row, 'id'); ?>)" 
                                    class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo $i; ?>&limit=<?php echo $per_page; ?>&username=<?php echo $username_filter; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function deleteAssociation(profileId) {
    if (confirm("Are you sure you want to delete this association?")) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_association.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'profile_id';
        input.value = profileId;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<style>
    /* Watchlist Page Styling */
.container {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

/* Header Styling */
h1 {
    color: #0d6efd;
    margin-bottom: 1rem;
}

h3 {
    color: #6c757d;
    font-size: 1.2rem;
    margin-bottom: 2rem;
}

/* Form Styling */
.mb-3 {
    background-color: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-control, .form-select {
    border: 1px solid #ced4da;
    border-radius: 4px;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25);
}

/* Table Styling */
.table {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table thead th {
    background-color: #0d6efd;
    color: white;
    border-bottom: none;
    padding: 1rem;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

/* Button Styling */
.btn-primary {
    background-color: #0d6efd;
    border: none;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #0b5ed7;
    transform: translateY(-1px);
}

.btn-danger {
    background-color: #dc3545;
    border: none;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.btn-danger:hover {
    background-color: #bb2d3b;
    transform: translateY(-1px);
}

/* Pagination Styling */
.pagination {
    margin-top: 2rem;
}

.page-link {
    color: #0d6efd;
    border: 1px solid #dee2e6;
    padding: 0.5rem 1rem;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.page-link:hover {
    background-color: #e9ecef;
    color: #0d6efd;
}

/* Alert Styling */
.alert {
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .container {
        padding-top: 1rem;
    }
    
    .table {
        font-size: 0.9rem;
    }
    
    .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
}
</style>
<?php require(__DIR__ . "/../../../partials/flash.php"); ?> 