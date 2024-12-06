CREATE TABLE IF NOT EXISTS UserFavorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (user_id, profile_id),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (profile_id) REFERENCES LinkedInProfiles(id)
);