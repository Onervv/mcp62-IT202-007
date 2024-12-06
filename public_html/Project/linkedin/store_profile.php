<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!is_logged_in()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_data'])) {
    try {
        $db = getDB();
        $profile_data = json_decode($_POST['profile_data'], true);
        
        // Extracting the fields to store here
        $data = [
            'username' => $profile_data['username'] ?? '',
            'first_name' => $profile_data['firstName'] ?? '',
            'last_name' => $profile_data['lastName'] ?? '',
            'headline' => $profile_data['headline'] ?? '',
            'summary' => $profile_data['summary'] ?? '',
            'profile_picture' => $profile_data['profilePicture'] ?? ''
        ];
        
        // Updated query to include profile_picture
        $stmt = $db->prepare("INSERT INTO LinkedInProfiles 
            (user_id, linkedin_username, first_name, last_name, headline, summary, profile_picture) 
            VALUES (:user_id, :username, :first_name, :last_name, :headline, :summary, :profile_picture)
            ON DUPLICATE KEY UPDATE 
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            headline = VALUES(headline),
            summary = VALUES(summary),
            profile_picture = VALUES(profile_picture)");

        $stmt->execute([
            ':user_id' => get_user_id(),
            ':username' => $data['username'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':headline' => $data['headline'],
            ':summary' => $data['summary'],
            ':profile_picture' => $data['profile_picture']
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Store Profile Error: " . $e->getMessage());
        error_log("Profile Data: " . print_r($profile_data, true));
        http_response_code(500);
        echo json_encode(['error' => 'Failed to store profile']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
