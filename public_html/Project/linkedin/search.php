<?php
require(__DIR__ . "/../../../partials/nav.php");
?>

<div class="container">
    <h2>LinkedIn Profile Search</h2>
    <form id="searchForm">
        <div class="mb-3">
            <label for="profileUrl">LinkedIn Profile URL:</label>
            <input type="text" id="profileUrl" class="form-control" 
                   placeholder="https://www.linkedin.com/in/username" required>
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
    <div id="results" class="mt-3"></div>
</div>

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

<style>
.profile-picture {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 3px solid #0a66c2;
}

.card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 15px;
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

<?php require(__DIR__ . "/../../../partials/flash.php"); ?>