<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$profile_id = se($_GET, "id", -1, false);
if ($profile_id <= 0) {
    flash("Invalid profile ID", "danger");
    die(header("Location: view_profiles.php"));
}

if (isset($_POST["save"])) {
    $linkedin_username = se($_POST, "linkedin_username", "", false);
    $first_name = se($_POST, "first_name", "", false);
    $last_name = se($_POST, "last_name", "", false);
    $headline = se($_POST, "headline", "", false);
    $summary = se($_POST, "summary", "", false);
    
    $hasError = false;
    
    // Basic validation
    if (empty($linkedin_username)) {
        flash("LinkedIn username is required", "warning");
        $hasError = true;
    }
    if (empty($first_name)) {
        flash("First name is required", "warning");
        $hasError = true;
    }
    if (empty($last_name)) {
        flash("Last name is required", "warning");
        $hasError = true;
    }

    if (!$hasError) {
        $db = getDB();
        $stmt = $db->prepare(
            "UPDATE LinkedInProfiles 
            SET linkedin_username = :username,
                first_name = :fname,
                last_name = :lname,
                headline = :headline,
                summary = :summary
            WHERE id = :pid"
        );
        
        try {
            $stmt->execute([
                ":username" => $linkedin_username,
                ":fname" => $first_name,
                ":lname" => $last_name,
                ":headline" => $headline,
                ":summary" => $summary,
                ":pid" => $profile_id
            ]);
            flash("Profile updated successfully", "success");
            die(header("Location: view_profiles.php"));
        } catch (PDOException $e) {
            flash("Error updating profile", "danger");
            error_log(var_export($e->errorInfo, true));
        }
    }
}

// Fetch profile data
$db = getDB();
$stmt = $db->prepare("SELECT * FROM LinkedInProfiles WHERE id = :pid");
try {
    $stmt->execute([":pid" => $profile_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$profile) {
        flash("Profile not found", "danger");
        die(header("Location: view_profiles.php"));
    }
} catch (PDOException $e) {
    flash("Error fetching profile", "danger");
    error_log(var_export($e->errorInfo, true));
    die(header("Location: view_profiles.php"));
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card admin-card">
                <div class="card-header">
                    <h2 class="text-center mb-0">Edit LinkedIn Profile</h2>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validate(this)">
                        <div class="mb-3">
                            <label for="linkedin_username" class="form-label">LinkedIn Username</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="linkedin_username" 
                                   name="linkedin_username" 
                                   value="<?php se($profile, 'linkedin_username'); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?php se($profile, 'first_name'); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?php se($profile, 'last_name'); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="headline" class="form-label">Headline</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="headline" 
                                   name="headline" 
                                   value="<?php se($profile, 'headline'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="summary" class="form-label">Summary</label>
                            <textarea class="form-control" 
                                      id="summary" 
                                      name="summary" 
                                      rows="4"><?php se($profile, 'summary'); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="view_profiles.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button type="submit" name="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    color: white;
    padding: 1.2rem;
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

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.btn i {
    margin-right: 0.5rem;
}
</style>

<script>
function validate(form) {
    let isValid = true;
    
    // Basic validation
    if (!form.linkedin_username.value.trim()) {
        flash("LinkedIn username is required", "warning");
        isValid = false;
    }
    if (!form.first_name.value.trim()) {
        flash("First name is required", "warning");
        isValid = false;
    }
    if (!form.last_name.value.trim()) {
        flash("Last name is required", "warning");
        isValid = false;
    }
    
    return isValid;
}
</script>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?> 