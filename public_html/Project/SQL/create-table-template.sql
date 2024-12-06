-- Active: 1726156123627@@db.ethereallab.app@3306@mcp62
CREATE TABLE Samples
(  
    id int NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT 'Primary Key',
    create_time DATETIME COMMENT 'Create Time',
    name VARCHAR(255)
) COMMENT 'Testing Dynamic CRUD Operations';