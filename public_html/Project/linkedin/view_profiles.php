<?php
require(__DIR__ . "/../../../partials/nav.php");
if (!is_logged_in()) {
    flash("Please log in first", "warning");
    die(header("Location: " . get_url("login.php")));
}

// Add this code block at the beginning of the file, after the initial require statement
if (isset($_POST["delete"]) && isset($_POST["profile_id"])) {
    $profile_id = se($_POST, "profile_id", -1, false);
    if ($profile_id > 0) {
        $db = getDB();
        $stmt = $db->prepare(
            "DELETE FROM LinkedInProfiles 
            WHERE id = :pid AND (user_id = :uid OR :is_admin = 1)"
        );
        try {
            $stmt->execute([
                ":pid" => $profile_id,
                ":uid" => get_user_id(),
                ":is_admin" => has_role("Admin")
            ]);
            if ($stmt->rowCount() > 0) {
                flash("Profile successfully deleted", "success");
            } else {
                flash("Profile not found or you don't have permission to delete it", "warning");
            }
        } catch (PDOException $e) {
            flash("Error deleting profile", "danger");
            error_log(var_export($e->errorInfo, true));
        }
    }
}

// Fetch profiles for current user
$db = getDB();
$stmt = $db->prepare("SELECT * FROM LinkedInProfiles WHERE user_id = :user_id ORDER BY created DESC");
$stmt->execute([":user_id" => get_user_id()]);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="title-wrapper mb-5 text-center">
        <h1 class="display-4 fw-bold text-gradient">Network Hub</h1>
        <div class="subtitle-divider mx-auto my-4"></div>
        <p class="lead text-muted">
            Your curated collection of professional connections and industry leaders.
            Stay connected, informed, and inspired.
        </p>
    </div>

    <div class="row justify-content-center g-4">
        <?php if (empty($profiles)) : ?>
            <div class="col-12">
                <div class="empty-state text-center py-5">
                    <i class="fas fa-users-slash empty-icon mb-4"></i>
                    <h3 class="fw-bold mb-3">Your Network Awaits</h3>
                    <p class="text-muted mb-4">Start building your professional network by adding LinkedIn profiles</p>
                    <a href="<?php echo get_url('linkedin/search.php'); ?>" class="btn btn-primary btn-lg rounded-pill">
                        <i class="fas fa-search me-2"></i>Discover Professionals
                    </a>
                </div>
            </div>
        <?php else : ?>
            <?php foreach ($profiles as $profile) : ?>
                <div class="col-md-6 col-lg-4">
                    <div class="profile-card">
                        <div class="profile-banner"></div>
                        <div class="profile-content">
                            <div class="profile-image-wrapper">
                                <img src="<?php se($profile, 'profile_picture', 'https://via.placeholder.com/150'); ?>" 
                                     class="profile-picture" 
                                     alt="<?php se($profile, "first_name"); ?>'s Profile Picture"
                                     onerror="this.src='https://via.placeholder.com/150'">
                            </div>
                            <h3 class="profile-name">
                                <?php se($profile, "first_name"); ?> 
                                <?php se($profile, "last_name"); ?>
                            </h3>
                            <div class="profile-headline">
                                <?php se($profile, "headline"); ?>
                            </div>
                            <div class="profile-summary">
                                <?php se($profile, "summary"); ?>
                            </div>
                            <div class="profile-footer">
                                <div class="d-flex align-items-center gap-2">
                                    <a href="https://linkedin.com/in/<?php se($profile, "linkedin_username"); ?>" 
                                       target="_blank" 
                                       class="linkedin-link">
                                        <i class="fab fa-linkedin me-2"></i>View Profile
                                    </a>
                                    <?php if (has_role("Admin")): ?>
                                        <a href="edit_profile.php?id=<?php se($profile, 'id'); ?>" 
                                           class="btn btn-action btn-edit">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </a>
                                        <button type="button" 
                                                class="btn btn-action btn-delete" 
                                                onclick="confirmDelete(<?php se($profile, 'id'); ?>)">
                                            <i class="fas fa-trash me-1"></i>Delete
                                        </button>
                                    <?php endif; ?>
                                    <span class="saved-date">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?php echo date("M j, Y", strtotime($profile["created"])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Common Gradients */
.text-gradient {
    background: linear-gradient(120deg, #2b4162, #12100e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.subtitle-divider {
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #2b4162, #12100e);
    border-radius: 2px;
}

/* Empty State */
.empty-state {
    background: #f8f9fa;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    color: #2b4162;
}

/* Profile Card */
.profile-card {
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.profile-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
}

.profile-banner {
    height: 80px;
    background: linear-gradient(120deg, #1a2b43, #4a90e2);
}

.profile-content {
    padding: 0 1.5rem 1.5rem;
    position: relative;
}

/* Profile Image */
.profile-image-wrapper {
    margin: -40px 0 1rem;
}

.profile-picture {
    width: 100px;
    height: 100px;
    border: 4px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    object-fit: cover;
}

/* Profile Text Content */
.profile-name {
    color: #2b4162;
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-align: center;
}

.profile-headline {
    color: #6c757d;
    font-size: 0.95rem;
    font-style: italic;
    margin-bottom: 1rem;
    text-align: center;
}

.profile-summary {
    background: #f8f9fa;
    border-radius: 10px;
    color: #495057;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    max-height: 100px;
    overflow-y: auto;
    padding: 1rem;
}

/* Profile Footer */
.profile-footer {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}

.profile-footer .d-flex {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.linkedin-link {
    color: #0077b5;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s ease;
    white-space: nowrap;
}

.linkedin-link:hover {
    background: rgba(0,119,181,0.1);
}

/* Action Buttons */
.btn-action {
    border: none;
    border-radius: 8px;
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    text-decoration: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    white-space: nowrap;
    cursor: pointer;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.btn-edit {
    background: linear-gradient(145deg, #0077b5, #0a66c2);
}

.btn-delete {
    background: linear-gradient(145deg, #dc3545, #c82333);
}

.saved-date {
    color: #6c757d;
    font-size: 0.85rem;
    margin-left: auto;
    white-space: nowrap;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .profile-footer .d-flex {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .saved-date {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}
</style>

<script>
function confirmDelete(profileId) {
    if (confirm("Are you sure you want to delete this profile?")) {
        // Create a form dynamically
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';

        // Add profile_id input
        const profileInput = document.createElement('input');
        profileInput.type = 'hidden';
        profileInput.name = 'profile_id';
        profileInput.value = profileId;
        form.appendChild(profileInput);

        // Add delete action input
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete';
        deleteInput.value = '1';
        form.appendChild(deleteInput);

        // Add form to document and submit
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>
