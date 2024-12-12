<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$profiles = [];
$users = [];

// Handle search form submission
if (isset($_POST["search"])) {
    $db = getDB();
    
    // Search for LinkedIn profiles
    $profile_search = se($_POST, "profile_identifier", "", false);
    if (!empty($profile_search)) {
        $stmt = $db->prepare(
            "SELECT lp.*, 
                    CONCAT(lp.first_name, ' ', lp.last_name) as full_name,
                    (SELECT COUNT(*) FROM UserProfileAssociations 
                     WHERE profile_id = lp.id AND is_active = 1) as association_count
             FROM LinkedInProfiles lp 
             WHERE lp.linkedin_username LIKE :identifier 
                OR lp.first_name LIKE :identifier 
                OR lp.last_name LIKE :identifier
             ORDER BY lp.created DESC 
             LIMIT 25"
        );
        try {
            $stmt->execute([":identifier" => "%$profile_search%"]);
            $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            flash("Error searching profiles: " . var_export($e->errorInfo, true), "danger");
            error_log(var_export($e->errorInfo, true));
        }
    }

    // Search for users
    $username_search = se($_POST, "username", "", false);
    if (!empty($username_search)) {
        $stmt = $db->prepare(
            "SELECT u.id, u.username, 
                    (SELECT COUNT(*) FROM UserProfileAssociations 
                     WHERE user_id = u.id AND is_active = 1) as profile_count
             FROM Users u 
             WHERE u.username LIKE :username 
             ORDER BY u.username 
             LIMIT 25"
        );
        try {
            $stmt->execute([":username" => "%$username_search%"]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            flash("Error searching users", "danger");
            error_log(var_export($e->errorInfo, true));
        }
    }
}

// Handle association form submission
if (isset($_POST["associate"])) {
    $selected_users = isset($_POST["selected_users"]) ? $_POST["selected_users"] : [];
    $selected_profiles = isset($_POST["selected_profiles"]) ? $_POST["selected_profiles"] : [];

    if (empty($selected_users) || empty($selected_profiles)) {
        flash("Please select both users and profiles", "warning");
    } else {
        $db = getDB();
        $success_count = 0;
        $error_count = 0;

        try {
            $db->beginTransaction();

            // Debug information
            error_log("Selected Users: " . var_export($selected_users, true));
            error_log("Selected Profiles: " . var_export($selected_profiles, true));

            // Create/toggle profile association
            $toggle_profile_assoc_stmt = $db->prepare(
                "INSERT INTO UserProfileAssociations (user_id, profile_id, is_active) 
                 VALUES (:uid, :pid, 1) 
                 ON DUPLICATE KEY UPDATE is_active = NOT is_active"
            );

            foreach ($selected_profiles as $profile_id) {
                try {
                    error_log("Processing profile ID: " . $profile_id);
                    
                    // Create/toggle profile associations for each selected user
                    foreach ($selected_users as $user_id) {
                        try {
                            error_log("Processing association - User: $user_id, Profile: $profile_id");
                            
                            // Check if association exists and get current status
                            $check_stmt = $db->prepare(
                                "SELECT is_active FROM UserProfileAssociations 
                                 WHERE user_id = :uid AND profile_id = :pid"
                            );
                            $check_stmt->execute([
                                ":uid" => (int)$user_id,
                                ":pid" => $profile_id
                            ]);
                            $current_status = $check_stmt->fetchColumn();
                            error_log("Current association status: " . var_export($current_status, true));

                            // Execute toggle
                            $toggle_profile_assoc_stmt->execute([
                                ":uid" => (int)$user_id,
                                ":pid" => $profile_id
                            ]);
                            
                            if ($toggle_profile_assoc_stmt->rowCount() > 0) {
                                $success_count++;
                                error_log("Successfully toggled association");
                            } else {
                                error_log("No changes made to association");
                            }
                        } catch (PDOException $e) {
                            $error_message = "Association error: " . var_export($e->errorInfo, true);
                            error_log($error_message);
                            flash($error_message, "danger");
                            $error_count++;
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Profile processing error: " . var_export($e->errorInfo, true);
                    error_log($error_message);
                    flash($error_message, "danger");
                    $error_count++;
                }
            }

            $db->commit();
            error_log("Transaction committed. Successes: $success_count, Errors: $error_count");

            if ($success_count > 0) {
                flash("Successfully toggled $success_count association(s)", "success");
            }
            if ($error_count > 0) {
                flash("Failed to toggle $error_count association(s)", "danger");
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Error processing associations: " . $e->getMessage();
            error_log($error_message);
            flash($error_message, "danger");
            error_log("Association error: " . var_export($e, true));
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card admin-card">
                <div class="card-header bg-gradient">
                    <h2 class="text-center mb-0">Assign LinkedIn Profiles</h2>
                </div>
                <div class="card-body">
                    <!-- Search Form -->
                    <form method="POST" class="row g-3 mb-4">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fab fa-linkedin"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="profile_identifier" 
                                       placeholder="Search profiles by URL or name..."
                                       value="<?php se($_POST, 'profile_identifier', ''); ?>" />
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       name="username" 
                                       placeholder="Search users..."
                                       value="<?php se($_POST, 'username', ''); ?>" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="search" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($profiles) || !empty($users)) : ?>
                        <form method="POST">
                            <div class="row">
                                <!-- LinkedIn Profiles Column -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="mb-0">
                                                LinkedIn Profiles 
                                                <span class="badge bg-secondary">
                                                    <?php echo count($profiles); ?>
                                                </span>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Name</th>
                                                            <th>Associations</th>
                                                            <th>Select</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($profiles as $profile) : ?>
                                                            <tr>
                                                                <td>
                                                                    <?php se($profile, "full_name"); ?>
                                                                    <small class="text-muted d-block">
                                                                        @<?php se($profile, "linkedin_username"); ?>
                                                                    </small>
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-info">
                                                                        <?php se($profile, "association_count"); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <input type="checkbox" 
                                                                           class="form-check-input" 
                                                                           name="selected_profiles[]" 
                                                                           value="<?php se($profile, 'id'); ?>" />
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Users Column -->
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h4 class="mb-0">
                                                Users 
                                                <span class="badge bg-secondary">
                                                    <?php echo count($users); ?>
                                                </span>
                                            </h4>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Username</th>
                                                            <th>Profiles</th>
                                                            <th>Select</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($users as $user) : ?>
                                                            <tr>
                                                                <td><?php se($user, "username"); ?></td>
                                                                <td>
                                                                    <span class="badge bg-info">
                                                                        <?php se($user, "profile_count"); ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <input type="checkbox" 
                                                                           class="form-check-input" 
                                                                           name="selected_users[]" 
                                                                           value="<?php se($user, 'id'); ?>" />
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" name="associate" class="btn btn-primary">
                                    <i class="fas fa-link"></i> Toggle Associations
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
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
</style>

<?php require_once(__DIR__ . "/../../../partials/flash.php"); ?>