IN ORDER TO RUN THIS
PLEASE RUN THE FOLLOWING MYSQL SCRIPT!
CREATE DATABASE cramtayo_db( );
USE cramtayo_db;
CREATE TABLE users( 
user_id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(100) NOT NULL,
password VARCHHAR(100) NOT NULL
);

CREATE TABLE subjects (
id int AUTO_INCREMENT PRIMARY KEY,
user_id int,
subject_name VARCHAR(100) NOT NULL,
display_name  VARCHAR(100) NOT NULL,
description VARCHAR(150),
image_path VARCHAR(150),
created_at DATE,

FOREIGN KEY(user_id) REFERENCES  users(user_id)


);
