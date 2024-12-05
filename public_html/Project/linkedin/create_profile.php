<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!is_logged_in()) {
    flash("Please log in first", "warning");
    die(header("Location: " . get_url("login.php")));
}

/**
 * Validates and formats a profile picture URL
 * 
 * @param string $url The input URL to validate and format
 * @return string|null Returns formatted URL or null if invalid
 */
function validateProfilePictureUrl($url) {
    if (empty($url)) {
        return null;
    }
    
    // Remove any whitespace
    $url = trim($url);
    
    // Validate URL format
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    
    // Ensure URL uses HTTPS
    if (strpos($url, 'http://') === 0) {
        $url = 'https://' . substr($url, 7);
    }
    
    return $url;
}

if (isset($_POST["save"])) {
    $hasError = false;
    
    // Validate required fields
    $username = se($_POST, "linkedin_username", "", false);
    $firstName = se($_POST, "first_name", "", false);
    $lastName = se($_POST, "last_name", "", false);
    
    // Basic validation
    if (empty($username)) {
        flash("LinkedIn username is required", "warning");
        $hasError = true;
    }
    if (empty($firstName)) {
        flash("First name is required", "warning");
        $hasError = true;
    }
    if (empty($lastName)) {
        flash("Last name is required", "warning");
        $hasError = true;
    }
    
    // Username format validation
    if (!preg_match('/^[a-zA-Z0-9-]{3,100}$/', $username)) {
        flash("Invalid LinkedIn username format", "warning");
        $hasError = true;
    }

    if (!$hasError) {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO LinkedInProfiles 
            (user_id, linkedin_username, first_name, last_name, headline, summary, profile_picture, is_manual) 
            VALUES (:uid, :username, :fname, :lname, :headline, :summary, :picture, 1)"
        );
        
        try {
            $stmt->execute([
                ":uid" => get_user_id(),
                ":username" => $username,
                ":fname" => $firstName,
                ":lname" => $lastName,
                ":headline" => se($_POST, "headline", "", false),
                ":summary" => se($_POST, "summary", "", false),
                ":picture" => se($_POST, "profile_picture", "", false)
            ]);
            flash("Successfully created profile!", "success");
            die(header("Location: view_profiles.php"));
        } catch (PDOException $e) {
            if ($e->errorInfo[1] === 1062) {
                flash("This LinkedIn profile has already been added", "warning");
            } else {
                flash("Error creating profile", "danger");
                error_log(var_export($e->errorInfo, true));
            }
        }
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card profile-card">
                <div class="card-header bg-gradient">
                    <h2 class="text-center mb-0">Create LinkedIn Profile</h2>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validate(this);">
                        <div class="mb-3">
                            <label class="form-label">LinkedIn Username</label>
                            <input type="text" class="form-control" name="linkedin_username" 
                                   required maxlength="100" pattern="[a-zA-Z0-9-]{3,100}"
                                   value="<?php se($_POST, 'linkedin_username'); ?>" />
                            <div class="form-text">Example: john-doe</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" 
                                   required maxlength="100"
                                   value="<?php se($_POST, 'first_name'); ?>" />
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" 
                                   required maxlength="100"
                                   value="<?php se($_POST, 'last_name'); ?>" />
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Headline</label>
                            <input type="text" class="form-control" name="headline" 
                                   maxlength="255"
                                   value="<?php se($_POST, 'headline'); ?>" />
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Summary</label>
                            <textarea class="form-control" name="summary" rows="4"><?php se($_POST, 'summary'); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Profile Picture URL</label>
                            <input type="url" class="form-control" name="profile_picture" 
                                   value="<?php se($_POST, 'profile_picture'); ?>" />
                        </div>

                        <div class="text-center">
                            <button type="submit" name="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validate(form) {
    let isValid = true;
    
    // LinkedIn username validation
    const username = form.linkedin_username.value;
    if (!username.match(/^[a-zA-Z0-9-]{3,100}$/)) {
        flash("LinkedIn username must be 3-100 characters and contain only letters, numbers, and hyphens", "warning");
        isValid = false;
    }
    
    // Name validations
    if (form.first_name.value.trim().length === 0) {
        flash("First name is required", "warning");
        isValid = false;
    }
    if (form.last_name.value.trim().length === 0) {
        flash("Last name is required", "warning");
        isValid = false;
    }
    
    // URL validation if provided
    const pictureUrl = form.profile_picture.value;
    if (pictureUrl && !isValidUrl(pictureUrl)) {
        flash("Please enter a valid URL for the profile picture", "warning");
        isValid = false;
    }
    
    return isValid;
}

function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}
</script>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>

<style>
.profile-card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 1rem;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.profile-card:hover {
    transform: translateY(-5px);
}

.card-header.bg-gradient {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    color: white;
    padding: 1.5rem;
    border-bottom: none;
}

.card-header h2 {
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-body {
    padding: 2rem;
    background: #ffffff;
}

.form-label {
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control {
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0a66c2;
    box-shadow: 0 0 0 0.25rem rgba(10, 102, 194, 0.15);
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

textarea.form-control {
    min-height: 120px;
}

.btn-primary {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    border: none;
    padding: 0.75rem 2rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(120deg, #005f8d, #0077b5);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,119,181,0.3);
}

.btn-primary i {
    margin-right: 0.5rem;
}

/* Input group animations */
.mb-3 {
    position: relative;
    margin-bottom: 1.5rem !important;
}

.form-control:focus + .form-text {
    color: #0a66c2;
}

/* Required field indicator */
.form-label:after {
    content: "*";
    color: #dc3545;
    margin-left: 4px;
}

/* Optional fields */
.form-label[for="headline"]:after,
.form-label[for="summary"]:after,
.form-label[for="profile_picture"]:after {
    content: none;
}

/* Custom scrollbar for textarea */
textarea.form-control::-webkit-scrollbar {
    width: 8px;
}

textarea.form-control::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

textarea.form-control::-webkit-scrollbar-thumb {
    background: #0077b5;
    border-radius: 4px;
}

/* Error state styling */
.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: none;
}

.form-control.is-invalid:focus {
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
}

/* Success state styling */
.form-control.is-valid {
    border-color: #198754;
    box-shadow: none;
}

.form-control.is-valid:focus {
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}

/* Container background */
.container {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    padding-top: 3rem;
    padding-bottom: 3rem;
    border-radius: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1.5rem;
    }
    
    .btn-primary {
        width: 100%;
    }
}
</style>
