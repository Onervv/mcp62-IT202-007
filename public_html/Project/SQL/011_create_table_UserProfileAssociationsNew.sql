-- Create Entities table
CREATE TABLE IF NOT EXISTS Entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create EntityProfileMappings table
CREATE TABLE IF NOT EXISTS EntityProfileMappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_id INT NOT NULL,
    profile_id INT NOT NULL,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (entity_id, profile_id),
    FOREIGN KEY (entity_id) REFERENCES Entities(id),
    FOREIGN KEY (profile_id) REFERENCES LinkedInProfiles(id)
);