<?php
require_once(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to perform this action", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

if (isset($_POST["profile_id"])) {
    $profile_id = se($_POST, "profile_id", -1, false);
    
    if ($profile_id > 0) {
        $db = getDB();
        try {
            // Simply delete the profile
            $stmt = $db->prepare("DELETE FROM LinkedInProfiles WHERE id = :pid");
            $stmt->execute([":pid" => $profile_id]);
            
            if ($stmt->rowCount() > 0) {
                flash("Profile removed successfully", "success");
            } else {
                flash("Profile not found", "warning");
            }
        } catch (PDOException $e) {
            flash("Error removing profile", "danger");
            error_log(var_export($e->errorInfo, true));
        }
    }
}

die(header("Location: watchlist.php"));
?>