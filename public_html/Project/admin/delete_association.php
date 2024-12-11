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
            // Delete the profile and any associated data
            $stmt = $db->prepare("DELETE FROM LinkedInProfiles WHERE id = :pid");
            $stmt->execute([":pid" => $profile_id]);
            
            flash("Profile successfully deleted", "success");
        } catch (PDOException $e) {
            flash("Error deleting profile", "danger");
        }
    }
}

header("Location: watchlist.php");
die();
?>