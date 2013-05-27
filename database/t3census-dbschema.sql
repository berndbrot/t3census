CREATE TABLE port (
port_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
port_number SMALLINT UNSIGNED NOT NULL,
PRIMARY KEY (port_id)
) ENGINE=InnoDB;

CREATE TABLE server_port (
fk_server_id BIGINT UNSIGNED NOT NULL,
fk_port_id SMALLINT UNSIGNED NOT NULL,
FOREIGN KEY fk_server_id (fk_server_id)
REFERENCES server (server_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
FOREIGN KEY fk_port_id (fk_port_id)
REFERENCES port (port_id)
ON DELETE CASCADE
ON UPDATE CASCADE
);

CREATE TABLE server (
server_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
server_ip INT UNSIGNED NOT NULL,
created DATETIME NOT NULL,
updated DATETIME,
PRIMARY KEY (server_id)
) ENGINE=InnoDB;


CREATE TABLE host (
host_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
host_name VARCHAR(255) DEFAULT NULL,
host_domain VARCHAR(255) NOT NULL,
typo3_installed BOOL NULL,
typo3_versionstring VARCHAR(64) NULL,
created DATETIME NOT NULL,
updated DATETIME,
fk_server_id BIGINT UNSIGNED NOT NULL,
FOREIGN KEY fk_server_id (fk_server_id)
REFERENCES server (server_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY (host_id)
) ENGINE=InnoDB;

CREATE TABLE twitter_user (
user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
user_name VARCHAR(255) DEFAULT NULL,
twitter_id BIGINT UNSIGNED NOT NULL,
PRIMARY KEY (user_id),
UNIQUE KEY unique_user_id (user_name),
UNIQUE KEY unique_user_name (twitter_id)
) ENGINE=InnoDB;

CREATE TABLE twitter_tweet (
tweet_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
tweet_text VARCHAR(255) DEFAULT NULL,
twitter_id BIGINT UNSIGNED NOT NULL,
tweet_processed BOOL NOT NULL,
created DATETIME NOT NULL,
fk_user_id INT UNSIGNED NOT NULL,
FOREIGN KEY fk_user_id (fk_user_id)
REFERENCES twitter_user (user_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY (tweet_id),
UNIQUE KEY unique_tweet_id (fk_user_id,twitter_id)
) ENGINE=InnoDB;

CREATE TABLE twitter_url(
url_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
url_text VARCHAR(255) NOT NULL,
fk_tweet_id BIGINT UNSIGNED NOT NULL,
FOREIGN KEY fk_tweet_id (fk_tweet_id)
REFERENCES twitter_tweet (tweet_id)
ON DELETE CASCADE
ON UPDATE CASCADE,
PRIMARY KEY (url_id)
) ENGINE=InnoDB;
