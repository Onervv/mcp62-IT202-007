<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: " . get_url("home.php")));
}

if (isset($_POST["name"]) && isset($_POST["description"])) {
    $name = se($_POST, "name", "", false);
    $desc = se($_POST, "description", "", false);
    if (empty($name)) {
        flash("Name is required", "warning");
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Roles (name, description, is_active) VALUES(:name, :desc, 1)");
        try {
            $stmt->execute([":name" => $name, ":desc" => $desc]);
            flash("Successfully created role $name!", "success");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                flash("A role with this name already exists, please try another", "warning");
            } else {
                flash("Unknown error occurred, please try again", "danger");
                error_log(var_export($e->errorInfo, true));
            }
        }
    }
}
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card admin-card">
                <div class="card-header bg-gradient">
                    <h2 class="text-center mb-0">Create Role</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-4">
                            <label for="name" class="form-label">Role Name</label>
                            <input type="text" class="form-control" id="name" name="name" required />
                        </div>
                        <div class="mb-4">
                            <label for="d" class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="d" rows="4"></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Create Role
                            </button>
                        </div>
                    </form>
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

.form-control {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    border: 1px solid #e1e8ed;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0a66c2;
    box-shadow: 0 0 0 0.2rem rgba(10, 102, 194, 0.15);
}

.form-label {
    color: #2c3e50;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.btn-primary {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    border: none;
    padding: 0.75rem 2rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
</style>

<!-- Add Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
//note we need to go up 1 more directory
require_once(__DIR__ . "/../../../partials/flash.php");
?>