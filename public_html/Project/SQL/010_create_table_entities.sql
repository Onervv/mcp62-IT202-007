CREATE TABLE IF NOT EXISTS Entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier_name VARCHAR(255) NOT NULL,
    description TEXT,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_identifier (identifier_name)
);

CREATE TABLE IF NOT EXISTS UserEntityAssociations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (entity_id) REFERENCES Entities(id),
    UNIQUE KEY unique_association (user_id, entity_id)
); 

CREATE TABLE IF NOT EXISTS UserProfileAssociations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (profile_id) REFERENCES LinkedInProfiles(id),
    UNIQUE KEY unique_association (user_id, profile_id)
);