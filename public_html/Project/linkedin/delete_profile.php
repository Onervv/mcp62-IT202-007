<?php
require_once(__DIR__ . "/../../../lib/functions.php");
session_start();

header('Content-Type: application/json');

$response = ["success" => false, "message" => ""];

if (!is_logged_in() || !has_role("Admin")) {
    $response["message"] = "Unauthorized access";
    echo json_encode($response);
    exit;
}

$profile_id = se($_POST, "profile_id", "", false);
if (empty($profile_id)) {
    $response["message"] = "No profile ID provided";
    echo json_encode($response);
    exit;
}

$db = getDB();

try {
    // Start transaction
    $db->beginTransaction();

    // Delete from UserProfileAssociations first
    $stmt = $db->prepare("DELETE FROM UserProfileAssociations WHERE profile_id = ?");
    $stmt->execute([$profile_id]);

    // Delete from UserFavorites if it exists
    $stmt = $db->prepare("DELETE FROM UserFavorites WHERE profile_id = ?");
    $stmt->execute([$profile_id]);

    // Finally delete the profile
    $stmt = $db->prepare("DELETE FROM LinkedInProfiles WHERE id = ?");
    $stmt->execute([$profile_id]);

    // Commit the transaction
    $db->commit();
    
    $response["success"] = true;
    $response["message"] = "Profile deleted successfully";

} catch (PDOException $e) {
    // Rollback the transaction on error
    $db->rollBack();
    error_log("Delete error: " . var_export($e, true));
    $response["message"] = "Failed to delete profile: " . $e->getMessage();
} catch (Exception $e) {
    // Rollback the transaction on error
    $db->rollBack();
    error_log("General error: " . var_export($e, true));
    $response["message"] = "An error occurred";
}

echo json_encode($response); 