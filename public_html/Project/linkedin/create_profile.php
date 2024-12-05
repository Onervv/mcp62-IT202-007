<?php
require(__DIR__ . "/../../../partials/nav.php");

if (!is_logged_in()) {
    flash("Please log in first", "warning");
    die(header("Location: " . get_url("login.php")));
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
                            <label class="form-label">LinkedIn Username*</label>
                            <input type="text" class="form-control" name="linkedin_username" 
                                   required maxlength="100" pattern="[a-zA-Z0-9-]{3,100}"
                                   value="<?php se($_POST, 'linkedin_username'); ?>" />
                            <div class="form-text">Example: john-doe</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">First Name*</label>
                            <input type="text" class="form-control" name="first_name" 
                                   required maxlength="100"
                                   value="<?php se($_POST, 'first_name'); ?>" />
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name*</label>
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
