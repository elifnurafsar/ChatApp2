CREATE TABLE Users 
(username TEXT NOT NULL PRIMARY KEY, 
password TEXT NOT NULL);

INSERT INTO Users (username, password) 
VALUES ("user1@gmail.com", "user1.11");

INSERT INTO Users (username, password) 
VALUES ("user2@gmail.com", "user2.22");

INSERT INTO Users (username, password) 
VALUES ("user3@gmail.com", "user3.33");

INSERT INTO Users (username, password) 
VALUES ("user4@gmail.com", "user4.44");

INSERT INTO Users (username, password) 
VALUES ("user5@gmail.com", "user5.55");

CREATE TABLE Messages 
(id INTEGER PRIMARY KEY AUTOINCREMENT, sendername TEXT NOT NULL, receivername TEXT NOT NULL, 
message TEXT NOT NULL, sentdate TEXT NOT NULL,
FOREIGN KEY(sendername) REFERENCES Users(username),
FOREIGN KEY(receivername) REFERENCES Users(username));

INSERT INTO Messages (sendername, receivername, message, sentdate) 
VALUES ("user1@gmail.com", "user3@gmail.com", "Hello User3!", datetime('now'));

INSERT INTO Messages (sendername, receivername, message, sentdate) 
VALUES ("user3@gmail.com", "user1@gmail.com", "Hello User1!", datetime('now'));