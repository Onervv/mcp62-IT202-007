<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}
//handle the toggle first so select pulls fresh data
if (isset($_POST["role_id"])) {
    $role_id = se($_POST, "role_id", "", false);
    if (!empty($role_id)) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE Roles SET is_active = !is_active WHERE id = :rid");
        try {
            $stmt->execute([":rid" => $role_id]);
            flash("Updated Role", "success");
        } catch (PDOException $e) {
            flash(var_export($e->errorInfo, true), "danger");
        }
    }
}
$query = "SELECT id, name, description, is_active from Roles";
$params = null;
if (isset($_POST["role"])) {
    $search = se($_POST, "role", "", false);
    $query .= " WHERE name LIKE :role";
    $params =  [":role" => "%$search%"];
}
$query .= " ORDER BY modified desc LIMIT 10";
$db = getDB();
$stmt = $db->prepare($query);
$roles = [];
try {
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($results) {
        $roles = $results;
    } else {
        flash("No matches found", "warning");
    }
} catch (PDOException $e) {
    flash(var_export($e->errorInfo, true), "danger");
}

?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card admin-card">
                <div class="card-header bg-gradient">
                    <h2 class="text-center mb-0">List Roles</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="mb-4">
                        <div class="input-group">
                            <input type="search" name="role" class="form-control" placeholder="Role Filter" />
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($roles)) : ?>
                                    <tr>
                                        <td colspan="100%" class="text-center">No roles found</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($roles as $role) : ?>
                                        <tr>
                                            <td><?php se($role, "id"); ?></td>
                                            <td><?php se($role, "name"); ?></td>
                                            <td><?php se($role, "description"); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo (se($role, "is_active", 0, false) ? "success" : "danger"); ?>">
                                                    <?php echo (se($role, "is_active", 0, false) ? "Active" : "Disabled"); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="role_id" value="<?php se($role, 'id'); ?>" />
                                                    <?php if (isset($search) && !empty($search)) : ?>
                                                        <input type="hidden" name="role" value="<?php se($search, null); ?>" />
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn btn-sm btn-toggle <?php echo (se($role, "is_active", 0, false) ? "active" : ""); ?>">
                                                        <div class="handle"></div>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Add the common admin styling */
.admin-card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 15px;
    overflow: hidden;
    margin-top: 2rem;
}

.card-header {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    color: white;
    padding: 1.2rem;
    border-bottom: none;
}

.card-header h2 {
    font-size: 2rem;
    font-weight: 600;
}

.btn-primary {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-outline-primary {
    color: #0a66c2;
    border-color: #0a66c2;
}

.btn-outline-primary:hover {
    background-color: #0a66c2;
    color: white;
}

.table {
    margin-top: 1rem;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.table thead {
    background: #f8f9fa;
}

.table th {
    color: #2c3e50;
    font-weight: 600;
    padding: 1rem;
    border-bottom: 2px solid #e1e8ed;
}

.badge {
    padding: 0.5em 1em;
    font-weight: 500;
}

.btn-toggle {
    width: 60px;
    height: 30px;
    padding: 0;
    position: relative;
    border: none;
    background: #e9ecef;
    border-radius: 15px;
    transition: background-color 0.3s ease;
}

.btn-toggle .handle {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 24px;
    height: 24px;
    background-color: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: transform 0.3s ease;
}

.btn-toggle.active {
    background: #0a66c2;
}

.btn-toggle.active .handle {
    transform: translateX(30px);
}

.btn-toggle:hover {
    opacity: 0.85;
}
</style>

<!-- Add Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>