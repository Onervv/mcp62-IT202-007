<?php
require(__DIR__ . "/../../partials/nav.php");
reset_session();
?>
<form onsubmit="return validate(this)" method="POST">
    <div>
        <label for="email">Email</label>
        <input type="email" name="email" required />
    </div>
    <div>
        <label for="username">Username</label>
        <input type="text" name="username" required maxlength="30" />
    </div>
    <div>
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required minlength="8" />
    </div>
    <div>
        <label for="confirm">Confirm</label>
        <input type="password" name="confirm" required minlength="8" />
    </div>
    <input type="submit" value="Register" />
</form>
<script>
    // mcp62 11/12/2024
    function validate(form) {
        var emailPattern = /^([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})*$/;
        var isValid = true;
        var password = form.password.value;
        var confirm = form.confirm.value;
        var email = form.email.value;
        var username = form.username.value;

        // Check if email is empty
        if (email == "") {
            flash("[CLINET] Email must not be empty");
            isValid = false;
        }

         // Check if email for invalid format
        if (!email.match(emailPattern)) {
            flash("[CLINET] Please enter a valid email address");
            isValid = false;
        }

        // Check if password is at least 8 characters long
        if (password.length < 8) {
            flash("[CLINET] Password must be at least 8 characters long");
            isValid = false;
        }

        // Check if password matches confirm password
        if (password != confirm) {
            flash("[CLINET] Passwords must match");
            isValid = false;
        }

        // Check if username follows the pattern
        var usernamePattern = /^[a-z0-9_-]{3,16}$/;
        if (!username.match(usernamePattern)) {
            flash("[CLINET] Username must only contain 3-30 characters a-z, 0-9, _, or -");
            isValid = false;
        }

        return isValid;  // Check if form is valid
    }
</script>
<?php
if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm"])) {
    $email = se($_POST, "email", "", false);
    $password = se($_POST, "password", "", false);
    $confirm = se($_POST, "confirm", "", false);
    $username = se($_POST, "username", "", false);

    $hasError = false;
    if (empty($email)) {
        flash("Email must not be empty", "danger");
        $hasError = true;
    }

    $email = sanitize_email($email);

    if (!is_valid_email($email)) {
        flash("Invalid email address", "danger");
        $hasError = true;
    }

    if (!preg_match('/^[a-z0-9_-]{3,16}$/', $username)) {
        flash("Username must only contain 3-30 characters a-z, 0-9, _, or -", "danger");
        $hasError = true;
    }

    if (empty($password)) {
        flash("Password must not be empty", "danger");
        $hasError = true;
    }

    if (empty($confirm)) {
        flash("Confirm password must not be empty", "danger");
        $hasError = true;
    }

    if (strlen($password) < 8) {
        flash("Password too short", "danger");
        $hasError = true;
    }

    if (strlen($password) > 0 && $password !== $confirm) {
        flash("Passwords must match", "danger");
        $hasError = true;
    }

    if (!$hasError) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Users (email, password, username) VALUES(:email, :password, :username)");
        try {
            $stmt->execute([":email" => $email, ":password" => $hash, ":username" => $username]);
            flash("Successfully registered!", "success");
        } catch (PDOException $e) {
            users_check_duplicate($e->errorInfo);
        }
    }
}
?>
<?php
require(__DIR__ . "/../../partials/flash.php");
?>