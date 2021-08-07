
CREATE TABLE ~table_name~ (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
    transaction_id INT NOT NULL, 
    type ENUM('apex','eloquent','doctrine') NOT NULL DEFAULT 'apex', 
    package VARCHAR(255) NOT NULL,
    revision INT NOT NULL, 
    class_name VARCHAR(255) NOT NULL, 
    execute_ms INT NOT NULL DEFAULT 0,  
    installed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

