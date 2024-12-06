<?php
require(__DIR__ . "/../../partials/nav.php");
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="title-wrapper mb-2">
                <h1 class="main-title">
                    Welcome to <span class="brand-name">LinkedIn<span class="sight-text">Sight</span></span>
                </h1>
                <div class="title-underline"></div>
            </div>
            
            <?php if (is_logged_in()) : ?>
                <p class="welcome-message">
                    Hello, <span class="user-highlight"><?php se($_SESSION["user"], "username"); ?></span>!
                </p>
            <?php endif; ?>

            <div class="description-box mt-4">
                <p class="lead">
                    Your intelligent LinkedIn profile analyzer. Gain valuable insights and track professional networks with ease.
                </p>
                <hr class="my-4">
                <p>
                    LinkedInSight helps you:
                </p>
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-search"></i>
                        <span>Search Profiles</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-save"></i>
                        <span>Save Connections</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Track Growth</span>
                    </div>
                </div>
            </div>

            <?php if (!is_logged_in()) : ?>
                <div class="cta-buttons mt-5">
                    <a href="<?php echo get_url('login.php'); ?>" class="btn btn-primary btn-lg me-3">Login</a>
                    <a href="<?php echo get_url('register.php'); ?>" class="btn btn-outline-primary btn-lg">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.title-wrapper {
    padding: 0.5rem;
    position: relative;
    margin-bottom: 0.5rem;
}

.main-title {
    font-size: 3.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
}

.brand-name {
    background: linear-gradient(120deg, #0077b5, #0a66c2);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    position: relative;
    display: inline-block;
}

.sight-text {
    background: linear-gradient(120deg, #0a66c2, #00a0dc);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.title-underline {
    height: 4px;
    width: 100px;
    background: linear-gradient(90deg, #0077b5, #00a0dc);
    margin: 0 auto;
    border-radius: 2px;
    animation: slideIn 1s ease-out forwards;
}

@keyframes slideIn {
    from {
        width: 0;
        opacity: 0;
    }
    to {
        width: 100px;
        opacity: 1;
    }
}

.welcome-message {
    font-size: 1.5rem;
    color: #666;
    margin-top: 0.5rem;
    margin-bottom: 1.5rem;
}

.user-highlight {
    color: #0a66c2;
    font-weight: 500;
    transition: color 0.3s ease;
}

.description-box {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.description-box:hover {
    transform: translateY(-5px);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.feature-item {
    padding: 1rem;
    text-align: center;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.feature-item i {
    font-size: 2rem;
    color: #0a66c2;
    margin-bottom: 0.5rem;
    display: block;
}

.feature-item span {
    color: #666;
    font-weight: 500;
}

.cta-buttons .btn {
    transition: all 0.3s ease;
}

.cta-buttons .btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background-color: #0a66c2;
    border-color: #0a66c2;
}

.btn-outline-primary {
    color: #0a66c2;
    border-color: #0a66c2;
}

.btn-outline-primary:hover {
    background-color: #0a66c2;
    color: white;
}
</style>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<?php
require(__DIR__ . "/../../partials/flash.php");
?>