<?php
require(__DIR__ . "/../../../partials/nav.php");
if (!is_logged_in()) {
    flash("Please log in first", "warning");
    die(header("Location: " . get_url("login.php")));
}


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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'favorite') {
    // Prevent any session messages from being included in response
    ob_clean(); // Clear any previous output
    
    // Set proper JSON content type header
    header('Content-Type: application/json');
    
    $profile_id = se($_POST, "profile_id", "", false);
    if (!empty($profile_id)) {
        $db = getDB();
        try {
            // Toggle the favorite status
            $stmt = $db->prepare("UPDATE LinkedInProfiles 
                                SET is_favorited = NOT is_favorited 
                                WHERE id = :pid AND user_id = :uid");
            $stmt->execute([":pid" => $profile_id, ":uid" => get_user_id()]);
            
            // Get the new status
            $stmt = $db->prepare("SELECT is_favorited FROM LinkedInProfiles 
                                WHERE id = :pid AND user_id = :uid");
            $stmt->execute([":pid" => $profile_id, ":uid" => get_user_id()]);
            $is_favorited = (bool)$stmt->fetchColumn();
            
            // Return proper JSON response and exit immediately
            echo json_encode([
                "success" => true,
                "is_favorited" => $is_favorited,
                "message" => $is_favorited ? "Profile added to favorites" : "Profile removed from favorites"
            ]);
            exit();
        } catch (PDOException $e) {
            // Return error response and exit
            echo json_encode([
                "success" => false,
                "message" => "Error updating favorite status"
            ]);
            exit();
        }
    } else {
        // Return error for invalid profile ID and exit
        echo json_encode([
            "success" => false,
            "message" => "Invalid profile ID"
        ]);
        exit();
    }
}

// Pagination settings
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Modify the existing profiles query to include pagination
$db = getDB();
$stmt = $db->prepare(
    "SELECT * FROM LinkedInProfiles 
    WHERE user_id = :user_id 
    ORDER BY created DESC 
    LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(":user_id", get_user_id(), PDO::PARAM_INT);
$stmt->bindValue(":limit", $per_page, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$stmt = $db->prepare("SELECT COUNT(*) FROM LinkedInProfiles WHERE user_id = :user_id");
$stmt->execute([":user_id" => get_user_id()]);
$total_profiles = $stmt->fetchColumn();
$total_pages = ceil($total_profiles / $per_page);

function is_favorited($profile_id) {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT 1 FROM UserFavorites 
         WHERE user_id = :uid AND profile_id = :pid"
    );
    try {
        $stmt->execute([
            ":uid" => get_user_id(),
            ":pid" => $profile_id
        ]);
        return $stmt->fetchColumn() ? true : false;
    } catch (PDOException $e) {
        error_log(var_export($e->errorInfo, true));
        return false;
    }
}
?>

<div class="container-fluid px-4">
    <div class="header-section mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="display-4 fw-bold text-gradient mb-0">Network Hub</h1>
            <div class="subtitle-divider my-3"></div>
            <p class="lead text-muted">
                Your curated collection of professional connections and industry leaders.
            </p>
        </div>
        <div class="filter-controls mt-3 mt-md-0">
            <a href="<?php echo get_url('linkedin/favorited_profiles.php'); ?>" class="favorite-link">
                <i class="bi bi-star-fill"></i>
                Favorites
                <?php
                // Get count of favorited profiles
                $db = getDB();
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM LinkedInProfiles 
                    WHERE user_id = :uid AND is_favorited = 1"
                );
                $stmt->execute([":uid" => get_user_id()]);
                $favCount = $stmt->fetchColumn();
                if ($favCount > 0) {
                    echo "<span class=\"favorite-count\">$favCount</span>";
                }
                ?>
            </a>
            <div class="per-page-control ms-3">
                <button class="per-page-link" onclick="togglePerPageInput(this)">
                    <i class="bi bi-grid-3x3"></i>
                    Per Page
                    <input type="number" 
                           class="per-page-count" 
                           value="<?php echo $per_page; ?>"
                           min="1" 
                           max="50"
                           onchange="updatePerPage(this.value)"
                    >
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4 justify-content-center" id="profiles-container">
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
                        <div class="profile-banner">
                            <button class="btn favorite-btn" 
                                    onclick="toggleFavorite(this, <?php se($profile, 'id'); ?>)" 
                                    data-favorited="<?php se($profile, 'is_favorited', '0'); ?>">
                                <i class="bi <?php echo (se($profile, 'is_favorited', '0', false) === '1') ? 'bi-star-fill' : 'bi-star'; ?>"></i>
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

        <?php if ($total_pages > 1): ?>
            <div class="col-12">
                <nav aria-label="Profile navigation">
                    <ul class="pagination pagination-lg justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link rounded-pill" href="?page=<?php echo $page-1; ?>&per_page=<?php echo $per_page; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link rounded-pill" href="?page=<?php echo $i; ?>&per_page=<?php echo $per_page; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link rounded-pill" href="?page=<?php echo $page+1; ?>&per_page=<?php echo $per_page; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize favorite buttons based on their stored state
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.favorite-btn').forEach(button => {
        const isFavorited = button.getAttribute('data-favorited') === '1';
        const icon = button.querySelector('i');
        
        if (isFavorited) {
            button.classList.add('active');
            icon.classList.remove('bi-star');
            icon.classList.add('bi-star-fill');
        } else {
            button.classList.remove('active');
            icon.classList.remove('bi-star-fill');
            icon.classList.add('bi-star');
        }
    });
});

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

function filterProfiles(filter, buttonElement) {
    // Update active button state
    document.querySelectorAll('.filter-controls .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    buttonElement.classList.add('active');

    // Get all profile cards
    const profileCards = document.querySelectorAll('.profile-card');
    
    profileCards.forEach(card => {
        const favoriteBtn = card.querySelector('.favorite-btn');
        
        if (filter === 'all') {
            card.parentElement.classList.remove('hidden');
        } else if (filter === 'favorites') {
            if (favoriteBtn.classList.contains('active')) {
                card.parentElement.classList.remove('hidden');
            } else {
                card.parentElement.classList.add('hidden');
            }
        }
    });

    // Check if no profiles are visible
    const visibleProfiles = document.querySelectorAll('.profile-card:not(.hidden)');
    const noResultsMessage = document.getElementById('no-results-message');
    
    if (visibleProfiles.length === 0) {
        if (!noResultsMessage) {
            const message = document.createElement('div');
            message.id = 'no-results-message';
            message.className = 'col-12 text-center py-5';
            message.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-search empty-icon mb-4"></i>
                    <h3 class="fw-bold mb-3">No Profiles Found</h3>
                    <p class="text-muted mb-0">No profiles match your current filter.</p>
                </div>
            `;
            document.getElementById('profiles-container').appendChild(message);
        }
    } else if (noResultsMessage) {
        noResultsMessage.remove();
    }
}

// Update the toggleFavorite function to handle toggling
function toggleFavorite(button, profileId) {
    const formData = new FormData();
    formData.append('profile_id', profileId);
    formData.append('action', 'favorite');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const icon = button.querySelector('i');
            if (data.is_favorited) {
                button.classList.add('active');
                icon.classList.remove('bi-star');
                icon.classList.add('bi-star-fill');
                flash("Profile added to favorites", "success");
            } else {
                button.classList.remove('active');
                icon.classList.remove('bi-star-fill');
                icon.classList.add('bi-star');
                flash("Profile removed from favorites", "success");
            }
        } else {
            flash(data.message || "Failed to update favorite status", "danger");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        flash("Failed to update favorite status", "danger");
    });
}

// Pagination control functions
function togglePerPageInput(button) {
    const input = button.querySelector('.per-page-count');
    input.style.width = '40px';
    input.focus();
}

function updatePerPage(value) {
    const perPage = parseInt(value) || 12;
    if (perPage > 0 && perPage <= 50) {
        window.location.href = `${window.location.pathname}?per_page=${perPage}&page=1`;
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.per-page-control')) {
        const input = document.querySelector('.per-page-count');
        if (input) {
            input.style.width = '24px';
        }
    }
});
</script>

<!-- Styles -->

<style>
/* Common Gradients */
.text-gradient {
    background: linear-gradient(120deg, #2b4162, #12100e);
    -webkit-background-clip: text;
    background-clip: text;
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
    height: 120px;
    background: linear-gradient(120deg, #1a2b43, #4a90e2);
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
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    object-fit: cover;
}

/* Profile Text Content */
.profile-name {
    color: #2b4162;
    font-size: 1.4rem;
    font-weight: 700;
    margin-top: 0;
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

/* Update the Favorite Button styling */
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
    color: #6c757d;
    text-shadow: none;
    transition: all 0.3s ease;
}

.favorite-btn.active {
    background: #fff;
    border-color: #ffd700;
}

.favorite-btn.active i {
    color: #ffd700;  /* Golden color for filled star */
}

/* Add these new styles */
.header-section {
    background: #f8f9fa;
    padding: 1rem 2rem;
    border-radius: 15px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
}

.filter-controls .btn-group {
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-radius: 10px;
    overflow: hidden;
}

.filter-controls .btn {
    padding: 0.5rem 1rem;
    font-weight: 500;
    border: none;
    position: relative;
    transition: all 0.3s ease;
}

.filter-controls .btn:hover {
    transform: translateY(-1px);
}

.filter-controls .btn.active {
    background: #2b4162;
    color: white;
}

/* Update profile card for filtering */
.profile-card {
    transition: all 0.3s ease;
}

.profile-card.hidden {
    display: none;
}

/* Add to your existing styles */
.favorite-link {
    position: relative;
    height: 42px;
    padding: 0.5rem 1.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 21px;
    transition: all 0.3s ease;
    background: #fff;
    border: 2px solid #ffd700;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    color: #2b4162;
    font-weight: 500;
    text-decoration: none;
}

.favorite-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
    border-color: #f4c414;
    color: #2b4162;
    text-decoration: none;
}

.favorite-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ffd700;
    color: #2b4162;
    border-radius: 12px;
    min-width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 0 4px;
}

.favorite-link:hover .favorite-count {
    background: #f4c414;
    transform: scale(1.1);
}

/* Per Page Control */
.per-page-link {
    position: relative;
    height: 42px;
    padding: 0.5rem 1.25rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border-radius: 21px;
    transition: all 0.3s ease;
    background: #fff;
    border: 2px solid #ffd700;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    color: #2b4162;
    font-weight: 500;
    text-decoration: none;
}

.per-page-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
    border-color: #f4c414;
}

.per-page-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #ffd700;
    color: #2b4162;
    border-radius: 12px;
    width: 24px;
    height: 24px;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    padding: 0;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.per-page-count:focus {
    outline: none;
    background: #fff;
    border-color: #ffd700;
    width: 40px;
}

/* Pagination Styles */
.pagination {
    gap: 0.5rem;
}

.pagination .page-link {
    border-radius: 8px;
    border: none;
    color: #2b4162;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.pagination .page-item.active .page-link {
    background: #ffd700;
    color: #2b4162;
}

.pagination .page-link:hover {
    background: #f4c414;
    color: #2b4162;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
}

.pagination .page-item.disabled .page-link {
    background: #f8f9fa;
    color: #6c757d;
}

/* Update just these CSS rules */
.filter-controls {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.favorite-link, .per-page-link {
    display: inline-flex;
    align-items: center;
}
</style>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>
