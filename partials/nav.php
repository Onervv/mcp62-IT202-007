<?php
require_once(dirname(__DIR__) . "/lib/functions.php");
//Note: this is to resolve cookie issues with port numbers
$domain = $_SERVER["HTTP_HOST"];
if (strpos($domain, ":")) {
    $domain = explode(":", $domain)[0];
}
$localWorks = true; //some people have issues with localhost for the cookie params
//if you're one of those people make this false

//this is an extra condition added to "resolve" the localhost issue for the session cookie
if (($localWorks && $domain == "localhost") || $domain != "localhost") {
    session_set_cookie_params([
        "lifetime" => 60 * 60,
        "path" => "$BASE_PATH",
        //"domain" => $_SERVER["HTTP_HOST"] || "localhost",
        "domain" => $domain,
        "secure" => true,
        "httponly" => true,
        "samesite" => "lax"
    ]);
}
session_start();

$nav_arr = [
    "Home" => "home.php",
    "Profile" => "profile.php",
    "LinkedIn" => [
        "Search Profiles" => "linkedin/search.php"
    ]
];

?>
<!-- include css and js files -->
<link rel="stylesheet" href="<?php echo get_url('styles.css'); ?>">
<!-- include custom LinkedIn css -->
<link rel="stylesheet" href="/Project/styles/linkedin.css">
<script src="<?php echo get_url('helpers.js'); ?>"></script>
<nav>
    <ul>
        <?php if (is_logged_in()) : ?>
            <li><a href="<?php echo get_url('home.php'); ?>">Home</a></li>
            <li><a href="<?php echo get_url('profile.php'); ?>">Profile</a></li>
            
            <?php if (has_role("Admin")) : ?>
                <li class="nav-dropdown">
                    <a href="#">Admin</a>
                    <div class="nav-dropdown-content">
                        <a href="<?php echo get_url('admin/create_role.php'); ?>">Create Role</a>
                        <a href="<?php echo get_url('admin/list_roles.php'); ?>">List Roles</a>
                        <a href="<?php echo get_url('admin/assign_roles.php'); ?>">Assign Roles</a>
                        <a href="<?php echo get_url('admin/watchlist.php'); ?>">Admin Watchlist</a>
                    </div>
                </li>
            <?php endif; ?>
            
            <li class="nav-dropdown">
                <a href="#">LinkedIn</a>
                <div class="nav-dropdown-content">
                    <a href="<?php echo get_url('linkedin/search.php'); ?>">Search Profiles</a>
                    <a href="<?php echo get_url('linkedin/view_profiles.php'); ?>">View Profiles</a>
                    <a href="<?php echo get_url('linkedin/create_profile.php'); ?>">Create Profile</a>
                </div>
            </li>
            
            <li style="margin-left: auto;"><a href="<?php echo get_url('logout.php'); ?>">Logout</a></li>
        <?php endif; ?>
        
        <?php if (!is_logged_in()) : ?>
            <li><a href="<?php echo get_url('login.php'); ?>">Login</a></li>
            <li><a href="<?php echo get_url('register.php'); ?>">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<style>
/* Add styles for dropdown */
.nav-dropdown {
    position: relative;
    display: inline-block;
}

.nav-dropdown-content {
    display: none;
    position: absolute;
    background-color: #ffffff;
    min-width: 160px;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    z-index: 1;
}

.nav-dropdown:hover .nav-dropdown-content {
    display: block;
}

.nav-dropdown-content a {
    color: rgba(0, 0, 0, 0.9);
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.nav-dropdown-content a:hover {
    background-color: rgba(10, 102, 194, 0.1);
    color: #0a66c2;
}
</style>