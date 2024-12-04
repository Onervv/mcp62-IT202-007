<?php
require(__DIR__ . "/../../../partials/nav.php");
if (!is_logged_in()) {
    flash("Please log in first", "warning");
    die(header("Location: " . get_url("login.php")));
}

// Fetch profiles for current user
$db = getDB();
$stmt = $db->prepare("SELECT * FROM LinkedInProfiles WHERE user_id = :user_id ORDER BY created DESC");
$stmt->execute([":user_id" => get_user_id()]);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <h2>Saved LinkedIn Profiles</h2>
    <div class="row">
        <?php if (empty($profiles)) : ?>
            <div class="col-12">
                <p>No profiles saved yet. <a href="<?php echo get_url('linkedin/search.php'); ?>">Search for profiles</a></p>
            </div>
        <?php else : ?>
            <?php foreach ($profiles as $profile) : ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php se($profile, 'profile_picture', 'https://via.placeholder.com/150'); ?>" 
                             class="card-img-top profile-picture" 
                             alt="Profile Picture"
                             onerror="this.src='https://via.placeholder.com/150'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php se($profile, "first_name"); ?> 
                                <?php se($profile, "last_name"); ?>
                            </h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php se($profile, "headline"); ?>
                            </h6>
                            <p class="card-text">
                                <?php se($profile, "summary"); ?>
                            </p>
                            <div class="text-muted small">
                                LinkedIn: <a href="https://linkedin.com/in/<?php se($profile, "linkedin_username"); ?>" 
                                           target="_blank" 
                                           class="card-link">
                                    <?php se($profile, "linkedin_username"); ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            Saved: <?php echo date("M j, Y", strtotime($profile["created"])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: 0.3s;
}
.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
.card-text {
    max-height: 150px;
    overflow-y: auto;
}
.profile-picture {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 50%;
    margin: 20px auto 10px;
    border: 2px solid #e1e8ed;
    display: block;
}
</style>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>
