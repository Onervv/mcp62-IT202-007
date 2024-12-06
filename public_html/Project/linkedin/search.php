<?php
require(__DIR__ . "/../../../partials/nav.php");
?>

<div class="container mt-5">
    <div class="text-center mb-5">
        <h2 class="display-4 text-gradient mb-4">LinkedIn Profile Search</h2>
        <p class="lead text-muted">
            Enter a LinkedIn profile URL to fetch and store profile data. Ensure the URL is in the format: 
            <code>https://www.linkedin.com/in/username</code>.
        </p>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form id="searchForm" class="search-form">
                <div class="mb-4">
                    <label for="profileUrl" class="form-label h5 mb-3">LinkedIn Profile URL:</label>
                    <input type="text" id="profileUrl" class="form-control form-control-lg" 
                           placeholder="https://www.linkedin.com/in/username" required>
                </div>
                <button type="submit" class="btn btn-primary btn-search">
                    <i class="fas fa-search me-2"></i> Search Profile
                </button>
            </form>
        </div>
    </div>
    <div id="results" class="mt-4"></div>
</div>

<style>
.text-gradient {
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

.search-form {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.form-control {
    border-radius: 0.25rem;
    padding: 1rem 1.5rem;
    border: 1px solid #ced4da;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.form-control:focus {
    border-color: #0a66c2;
    box-shadow: 0 0 0 0.25rem rgba(10, 102, 194, 0.25);
}

.btn-search {
    display: block;
    width: 100%;
    padding: 1rem;
    font-size: 1.25rem;
    border-radius: 0.5rem;
    background: linear-gradient(120deg, #0077b5, #00a0dc);
    border: none;
    transition: all 0.3s ease;
    margin-top: 2rem;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.btn-search:hover {
    background: linear-gradient(120deg, #005f8d, #0077b5);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,119,181,0.3);
}

.form-label {
    font-weight: 500;
    color: #2c3e50;
}

code {
    background: rgba(0,119,181,0.1);
    color: #0077b5;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
}

.profile-picture {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 3px solid #0a66c2;
}

.card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 0.5rem;
}

.card-body {
    padding: 2rem;
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
}

.card-subtitle {
    color: #0a66c2;
}
</style>

<!-- Add Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        const url = $('#profileUrl').val();
        
        const settings = {
            async: true,
            crossDomain: true,
            url: `https://linkedin-data-api.p.rapidapi.com/get-profile-data-by-url?url=${encodeURIComponent(url)}`,
            method: 'GET',
            headers: {
                'x-rapidapi-key': 'e2ce1e9c38mshf2722f27a0be1cep1b59ebjsnd94db930e948',
                'x-rapidapi-host': 'linkedin-data-api.p.rapidapi.com'
            }
        };

        $.ajax(settings)
            .done(function(response) {
                console.log('API Response:', response);
                $.post('store_profile.php', {
                    profile_data: JSON.stringify(response)
                })
                .done(function() {
                    flash("Profile data stored successfully", "success");
                    displayProfile(response.data);
                })
                .fail(function(error) {
                    console.error('Storage Error:', error);
                    flash("Failed to store profile data", "danger");
                });
            })
            .fail(function(error) {
                console.error('API Error:', error);
                flash("Failed to fetch profile data", "danger");
            });
    });

    function displayProfile(data) {
        $('#results').html(`
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <img src="${data.profilePicture || 'https://via.placeholder.com/150'}" 
                                 class="rounded-circle img-thumbnail profile-picture"
                                 alt="Profile Picture"
                                 onerror="this.src='https://via.placeholder.com/150'">
                        </div>
                        <div class="col-md-9">
                            <h5 class="card-title">${data.firstName} ${data.lastName}</h5>
                            <h6 class="card-subtitle mb-2 text-muted">${data.headline || 'No headline available'}</h6>
                            <p class="card-text">${data.summary || 'No summary available'}</p>
                            <div class="text-muted small">
                                LinkedIn: <a href="https://linkedin.com/in/${data.username}" 
                                           target="_blank" 
                                           class="card-link">
                                    ${data.username}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
});
</script>

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>