<?php
require_once(__DIR__ . "/../../partials/nav.php");
is_logged_in(true);
?>
<?php
if (isset($_POST["save"])) {
    $email = se($_POST, "email", null, false);
    $username = se($_POST, "username", null, false);
    $hasError = false;
    //sanitize
    $email = sanitize_email($email);
    //validate
    if (!is_valid_email($email)) {
        flash("Invalid email address", "danger");
        $hasError = true;
    }
    if (!is_valid_username($username)) {
        flash("Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
        $hasError = true;
    }
    if (!$hasError) {
        $params = [":email" => $email, ":username" => $username, ":id" => get_user_id()];
        $db = getDB();
        $stmt = $db->prepare("UPDATE Users set email = :email, username = :username where id = :id");
        try {
            $stmt->execute($params);
            flash("Profile saved", "success");
        } catch (PDOException $e) {
            users_check_duplicate($e->errorInfo);
        }
        //select fresh data from table
        $stmt = $db->prepare("SELECT id, email, username from Users where id = :id LIMIT 1");
        try {
            $stmt->execute([":id" => get_user_id()]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                //$_SESSION["user"] = $user;
                $_SESSION["user"]["email"] = $user["email"];
                $_SESSION["user"]["username"] = $user["username"];
            } else {
                flash("User doesn't exist", "danger");
            }
        } catch (PDOException $e) {
            flash("An unexpected error occurred, please try again", "danger");
            //echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
        }
    }


    //check/update password
    $current_password = se($_POST, "currentPassword", null, false);
    $new_password = se($_POST, "newPassword", null, false);
    $confirm_password = se($_POST, "confirmPassword", null, false);
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        $hasError = false;
        if (!is_valid_password($new_password)) {
            flash("Password too short", "danger");
            $hasError = true;
        }
        if (!$hasError) {
            if ($new_password === $confirm_password) {
                //TODO validate current
                $stmt = $db->prepare("SELECT password from Users where id = :id");
                try {
                    $stmt->execute([":id" => get_user_id()]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (isset($result["password"])) {
                        if (password_verify($current_password, $result["password"])) {
                            $query = "UPDATE Users set password = :password where id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->execute([
                                ":id" => get_user_id(),
                                ":password" => password_hash($new_password, PASSWORD_BCRYPT)
                            ]);

                            flash("Password reset", "success");
                        } else {
                            flash("Current password is invalid", "warning");
                        }
                    }
                } catch (PDOException $e) {
                    echo "<pre>" . var_export($e->errorInfo, true) . "</pre>";
                }
            } else {
                flash("New passwords don't match", "warning");
            }
        }
    }
}
?>

<?php
$email = get_user_email();
$username = get_username();
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card profile-card">
                <div class="card-header bg-gradient">
                    <h2 class="text-center mb-0">Profile Settings</h2>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validate(this);">
                        <!-- Basic Info Section -->
                        <div class="section-title">
                            <i class="fas fa-user-circle"></i> Basic Information
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" value="<?php se($email); ?>" />
                        </div>
                        <div class="mb-4">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" value="<?php se($username); ?>" />
                        </div>

                        <!-- Password Reset Section -->
                        <div class="section-title mt-4">
                            <i class="fas fa-key"></i> Password Reset
                        </div>
                        <div class="mb-4">
                            <label for="cp" class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="currentPassword" id="cp" />
                        </div>
                        <div class="mb-4">
                            <label for="np" class="form-label">New Password</label>
                            <input type="password" class="form-control" name="newPassword" id="np" />
                        </div>
                        <div class="mb-4">
                            <label for="conp" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirmPassword" id="conp" />
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="save" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // mcp62 11/12/2024
    function validate(form) {
    let pw = form.newPassword.value;
    let con = form.confirmPassword.value;
    let email = form.email.value;
    let username = form.username.value;
    let currentPassword = form.currentPassword.value;
    let isValid = true;

    // Email Validation
    if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
        flash("[CLINET] Please enter a valid email address", "warning");
        isValid = false;
    }

    // Username Validation
    if (!username || !/^[a-z0-9_-]{3,16}$/i.test(username)) {
        flash("[CLINET] Username must contain 3-16 characters (letters, numbers, underscores, or hyphens)", "warning");
        isValid = false;
    }

    // Password Validation (only if attempting password reset)
    if (currentPassword || pw || con) {
        if (pw.length < 8) {
            flash("[CLINET] New password must be at least 8 characters long", "warning");
            isValid = false;
        }
        if (pw !== con) {
            flash("[CLINET] New password and confirmation must match", "warning");
            isValid = false;
        }
    }

    return isValid;
}
</script>
<style>
.profile-card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 15px;
    overflow: hidden;
    margin-top: 2rem;
}

.card-header {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    color: white;
    padding: 1.2rem;
    border-bottom: none;
}

.card-header h2 {
    font-size: 2rem;
    font-weight: 600;
}

.card-body {
    padding: 2rem;
}

.section-title {
    color: #0a66c2;
    font-size: 1.2rem;
    font-weight: 600;
    margin: 1rem 0 1.5rem 0;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e1e8ed;
}

.section-title:first-child {
    margin-top: 0;
}

.form-control {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    border: 1px solid #e1e8ed;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.form-control:focus {
    border-color: #0a66c2;
    box-shadow: 0 0 0 0.2rem rgba(10, 102, 194, 0.15);
}

.form-label {
    color: #2c3e50;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.btn-primary {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    border: none;
    padding: 0.75rem 2rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-primary i {
    margin-right: 0.5rem;
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    .card-header {
        padding: 1rem;
    }
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<?php
require_once(__DIR__ . "/../../partials/flash.php");
?>