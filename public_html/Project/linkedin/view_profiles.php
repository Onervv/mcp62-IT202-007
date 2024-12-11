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

// Add this after your initial pagination settings but before the query execution
$filter = se($_GET, "filter", "all", false);

// Modify the existing profiles query to include filters
$db = getDB();
$query = "SELECT * FROM LinkedInProfiles WHERE user_id = :user_id";
$params = [":user_id" => get_user_id()];

// Apply filters
switch($filter) {
    case "recent":
        $query .= " AND created >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case "manual":
        $query .= " AND is_manual = 1";
        break;
    case "api":
        $query .= " AND is_manual = 0";
        break;
    case "all":
    default:
        // No additional conditions needed
        break;
}

// Add the ORDER BY and LIMIT clauses
$query .= " ORDER BY modified DESC, created DESC LIMIT :limit OFFSET :offset";

// Execute the query with pagination
$stmt = $db->prepare($query);
$stmt->bindValue(":user_id", get_user_id(), PDO::PARAM_INT);
$stmt->bindValue(":limit", $per_page, PDO::PARAM_INT);
$stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$stmt->execute();
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination with filters
$count_query = str_replace("SELECT *", "SELECT COUNT(*)", substr($query, 0, strpos($query, " LIMIT")));
$stmt = $db->prepare($count_query);
$stmt->bindValue(":user_id", get_user_id(), PDO::PARAM_INT);
$stmt->execute();
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
            <div class="filter-control ms-3">
                <button class="filter-link" onclick="toggleFilterInput(this)">
                    <i class="bi bi-funnel"></i>
                    Filter
                    <select class="filter-options" onchange="handleFilterChange(this.value)">
                        <optgroup label="Items per page">
                            <option value="per_page_custom">Items per page (1-100)</option>
                            <option value="per_page_12" <?php echo $per_page === 12 ? 'selected' : ''; ?>>12 per page</option>
                            <option value="per_page_24" <?php echo $per_page === 24 ? 'selected' : ''; ?>>24 per page</option>
                            <option value="per_page_48" <?php echo $per_page === 48 ? 'selected' : ''; ?>>48 per page</option>
                        </optgroup>
                        <optgroup label="Filter by">
                            <option value="all">All Profiles</option>
                            <option value="recent">Recently Added</option>
                            <option value="manual">Manually Added</option>
                            <option value="api">API Added</option>
                        </optgroup>
                    </select>
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
    if (perPage >= 1 && perPage <= 100) {
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

function handleFilterChange(value) {
    if (value === 'per_page_custom') {
        const perPage = prompt('Enter number of items per page (1-100):', '12');
        if (perPage !== null) {
            const numPerPage = parseInt(perPage);
            if (numPerPage >= 1 && numPerPage <= 100) {
                updatePerPage(numPerPage);
                return;
            } else {
                alert('Please enter a number between 1 and 100');
            }
        }
    } else if (value.startsWith('per_page_')) {
        const perPage = value.split('_')[2];
        updatePerPage(perPage);
    } else {
        // Handle filters
        let url = new URL(window.location.href);
        url.searchParams.set('filter', value);
        url.searchParams.set('page', '1'); // Reset to first page on filter change
        window.location.href = url.toString();
    }
}

function toggleFilterInput(button) {
    const select = button.querySelector('.filter-options');
    if (select) {
        select.style.width = select.style.width === '200px' ? '24px' : '200px';
        select.focus();
    }
}
</script>


<?php require(__DIR__ . "/../../../partials/flash.php"); ?>
