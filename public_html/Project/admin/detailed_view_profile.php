<?php
require_once(__DIR__ . "/../../../partials/nav.php");

if (!has_role("Admin")) {
    flash("You don't have permission to view this page", "warning");
    die(header("Location: $BASE_PATH" . "/home.php"));
}

$profile_id = se($_GET, "id", -1, false);
if ($profile_id < 0) {
    flash("Invalid profile ID", "danger");
    die(header("Location: watchlist.php"));
}

// Fetch profile details
$db = getDB();
try {
    $stmt = $db->prepare(
        "SELECT p.*, u.username,
        CASE WHEN p.is_favorited = 1 THEN 'Yes' ELSE 'No' END as is_favorited,
        CASE WHEN p.is_manual = 1 THEN 'Manual' ELSE 'API' END as source
        FROM LinkedInProfiles p 
        LEFT JOIN Users u ON p.user_id = u.id
        WHERE p.id = :pid"
    );
    $stmt->execute([":pid" => $profile_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        flash("Profile not found", "danger");
        die(header("Location: watchlist.php"));
    }
} catch (PDOException $e) {
    flash("Error fetching profile: " . var_export($e->errorInfo, true), "danger");
    die(header("Location: watchlist.php"));
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2>LinkedIn Profile Details</h2>
            <a href="watchlist.php" class="btn btn-secondary">Back to Watchlist</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Basic Information</h4>
                    <table class="table">
                        <tr>
                            <th>System Username:</th>
                            <td><?php se($profile, "username"); ?></td>
                        </tr>
                        <tr>
                            <th>LinkedIn Username:</th>
                            <td><?php se($profile, "linkedin_username"); ?></td>
                        </tr>
                        <tr>
                            <th>Full Name:</th>
                            <td>
                                <?php echo se($profile, "first_name", "") . " " . se($profile, "last_name", ""); ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h4>Profile Status</h4>
                    <table class="table">
                        <tr>
                            <th>Source:</th>
                            <td><?php se($profile, "source"); ?></td>
                        </tr>
                        <tr>
                            <th>Favorited:</th>
                            <td><?php se($profile, "is_favorited"); ?></td>
                        </tr>
                        <tr>
                            <th>Added On:</th>
                            <td><?php se($profile, "created"); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h4>Professional Details</h4>
                    <table class="table">
                        <tr>
                            <th>Headline:</th>
                            <td><?php se($profile, "headline"); ?></td>
                        </tr>
                        <tr>
                            <th>Summary:</th>
                            <td><?php se($profile, "summary"); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <button onclick="confirmDelete(<?php se($profile, 'id'); ?>)" 
                        class="btn btn-danger">
                    <i class="fas fa-trash"></i> Remove Profile
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(profileId) {
    if (confirm("Are you sure you want to remove this profile association?")) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_association.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'profile_id';
        input.value = profileId;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?> 