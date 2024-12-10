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
            // Start transaction
            $db->beginTransaction();
            
            // Remove all favorites for this profile
            $stmt = $db->prepare("DELETE FROM UserFavorites WHERE profile_id = :pid");
            $stmt->execute([":pid" => $profile_id]);
            
            // Remove the profile association and set is_favorited to 0
            $stmt = $db->prepare("UPDATE LinkedInProfiles SET user_id = NULL, is_favorited = 0 WHERE id = :pid");
            $stmt->execute([":pid" => $profile_id]);
            
            // Commit transaction
            $db->commit();
            flash("Association removed successfully", "success");
        } catch (PDOException $e) {
            // Rollback on error
            $db->rollBack();
            flash("Error removing association", "danger");
            error_log(var_export($e->errorInfo, true));
        }
    }
}

die(header("Location: watchlist.php"));
require_once(__DIR__ . "/../../../partials/flash.php");
?> 