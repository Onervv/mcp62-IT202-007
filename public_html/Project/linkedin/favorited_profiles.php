<?php
require(__DIR__ . "/../../../partials/nav.php");
if (!is_logged_in()) {
    flash("Please log in first", "warning");
    die(header("Location: " . get_url("login.php")));
}

// Fetch only favorited profiles for current user
$db = getDB();
$stmt = $db->prepare(
    "SELECT * FROM LinkedInProfiles 
    WHERE user_id = :user_id 
    AND is_favorited = 1 
    ORDER BY created DESC"
);
$stmt->execute([":user_id" => get_user_id()]);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="header-section mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-4 fw-bold text-gradient mb-0">Favorite Profiles</h1>
            <div class="subtitle-divider my-3"></div>
            <p class="lead text-muted">
                Your collection of saved professional connections.
            </p>
        </div>
        <div class="filter-controls mt-3 mt-md-0">
            <a href="<?php echo get_url('linkedin/view_profiles.php'); ?>" 
               class="btn btn-primary back-link">
                <i class="fas fa-arrow-left me-2"></i>Back to All Profiles
            </a>
        </div>
    </div>

    <div class="row g-4 justify-content-center" id="profiles-container">
        <?php if (empty($profiles)) : ?>
            <div class="col-12">
                <div class="empty-state text-center py-5">
                    <i class="fas fa-star empty-icon mb-4"></i>
                    <h3 class="fw-bold mb-3">No Favorites Yet</h3>
                    <p class="text-muted mb-4">Start adding profiles to your favorites collection</p>
                    <a href="<?php echo get_url('linkedin/view_profiles.php'); ?>" class="btn btn-primary btn-lg rounded-pill">
                        <i class="fas fa-users me-2"></i>View All Profiles
                    </a>
                </div>
            </div>
        <?php else : ?>
            <?php foreach ($profiles as $profile) : ?>
                <div class="col-md-6 col-lg-4">
                    <div class="profile-card">
                        <div class="profile-banner">
                            <button class="btn favorite-btn active" 
                                    onclick="toggleFavorite(this, <?php se($profile, 'id'); ?>)" 
                                    data-favorited="1">
                                <i class="bi bi-star-fill"></i>
                            </button>
                        </div>
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

<script>
function toggleFavorite(button, profileId) {
    const formData = new FormData();
    formData.append('profile_id', profileId);

    fetch('<?php echo get_url("api/favorite_profile.php"); ?>', {
        method: 'POST',
        body: formData,
        credentials: 'include' // This ensures cookies are sent
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && !data.is_favorited) {
            // Remove the card with animation
            const card = button.closest('.col-md-6');
            card.style.opacity = '0';
            card.style.transform = 'scale(0.8)';
            setTimeout(() => {
                card.remove();
                // Check if there are any profiles left
                if (document.querySelectorAll('.profile-card').length === 0) {
                    location.reload(); // Reload to show empty state
                }
            }, 300);
            flash("Profile removed from favorites", "success");
        } else if (!data.success) {
            flash(data.message || "Failed to update favorite status", "danger");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        flash("Failed to update favorite status", "danger");
    });
}
</script>

<style>
/* Header Styles */
.header-section {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.text-gradient {
    background: linear-gradient(145deg, #2b4162, #12100e);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.subtitle-divider {
    height: 4px;
    width: 60px;
    background: linear-gradient(145deg, #2b4162, #12100e);
    border-radius: 2px;
}

/* Back Button */
.back-link {
    background: linear-gradient(145deg, #2b4162, #12100e);
    border: none;
    padding: 0.75rem 1.5rem;
    color: white;
    font-weight: 500;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: inline-flex;
    align-items: center;
}

.back-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    color: white;
}

/* Profile Card */
.profile-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    overflow: hidden;
    position: relative;
    transition: all 0.3s ease;
}

.profile-banner {
    background: linear-gradient(145deg, #2b4162, #12100e);
    height: 80px;
    position: relative;
}

.profile-content {
    padding: 60px 1.5rem 1.5rem;
    position: relative;
}

/* Profile Image */
.profile-image-wrapper {
    position: absolute;
    top: -50px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 2;
}

.profile-picture {
    width: 100px;
    height: 100px;
    border: 4px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    object-fit: cover;
}

/* Profile Content */
.profile-name {
    color: #2b4162;
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0.5rem 0;
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

.linkedin-link {
    color: #0077b5;
    font-weight: 500;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.linkedin-link:hover {
    background: rgba(0,119,181,0.1);
    color: #0077b5;
}

/* Favorite Button */
.favorite-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    transition: all 0.3s ease;
    z-index: 3;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 0;
}

.favorite-btn:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.favorite-btn i {
    color: #ffd700;
    text-shadow: none;
    transition: all 0.3s ease;
}

/* Empty State */
.empty-state {
    padding: 3rem 1.5rem;
    text-align: center;
}

.empty-icon {
    font-size: 3rem;
    color: #ffd700;
    margin-bottom: 1.5rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .header-section {
        flex-direction: column;
        text-align: center;
    }
    
    .filter-controls {
        margin-top: 1.5rem;
    }
    
    .back-link {
        width: 100%;
        justify-content: center;
    }
}

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

/* Animation Classes */
.profile-card {
    transition: all 0.3s ease;
}

.profile-card.removing {
    opacity: 0;
    transform: scale(0.8);
}
</style>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>