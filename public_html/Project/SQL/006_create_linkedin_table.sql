CREATE TABLE IF NOT EXISTS LinkedInProfiles (
    id INT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    linkedin_username VARCHAR(100) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    headline VARCHAR(255),
    summary TEXT,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES Users(id),
    UNIQUE KEY unique_user_profile (user_id, linkedin_username)
) 