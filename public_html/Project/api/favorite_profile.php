<?php
require_once(__DIR__ . "/../../../lib/functions.php");

header('Content-Type: application/json'); // Ensure JSON response
$response = ["success" => false, "message" => "Unknown error"];

session_start(); // Add this line to ensure session is started

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        http_response_code(403);
        $response = ["success" => false, "message" => "Must be logged in"];
        echo json_encode($response);
        exit;
    }

    $profile_id = se($_POST, "profile_id", "", false);
    
    if (!empty($profile_id)) {
        $db = getDB();
        try {
            // First verify the profile exists and belongs to the user
            $stmt = $db->prepare(
                "SELECT id, is_favorited FROM LinkedInProfiles 
                WHERE id = :pid AND user_id = :uid"
            );
            $stmt->execute([":pid" => $profile_id, ":uid" => get_user_id()]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($profile) {
                // Toggle the favorite status
                $stmt = $db->prepare(
                    "UPDATE LinkedInProfiles 
                    SET is_favorited = NOT is_favorited 
                    WHERE id = :pid AND user_id = :uid"
                );
                $stmt->execute([":pid" => $profile_id, ":uid" => get_user_id()]);
                
                // Get updated favorite count
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM LinkedInProfiles 
                    WHERE user_id = :uid AND is_favorited = 1"
                );
                $stmt->execute([":uid" => get_user_id()]);
                $favCount = $stmt->fetchColumn();
                
                // Get new favorite status
                $stmt = $db->prepare(
                    "SELECT is_favorited FROM LinkedInProfiles 
                    WHERE id = :pid AND user_id = :uid"
                );
                $stmt->execute([":pid" => $profile_id, ":uid" => get_user_id()]);
                $is_favorited = (bool)$stmt->fetchColumn();
                
                $response = [
                    "success" => true,
                    "is_favorited" => $is_favorited,
                    "favorite_count" => $favCount,
                    "message" => $is_favorited ? "Profile added to favorites" : "Profile removed from favorites"
                ];
            } else {
                $response = [
                    "success" => false, 
                    "message" => "Profile not found or you don't have permission"
                ];
            }
        } catch (PDOException $e) {
            error_log("Error updating favorite status: " . var_export($e->errorInfo, true));
            $response = [
                "success" => false, 
                "message" => "Error updating favorite status"
            ];
        }
    } else {
        $response = [
            "success" => false, 
            "message" => "Invalid profile ID"
        ];
    }
}

echo json_encode($response);
?> 