CREATE DATABASE IF NOT EXISTS blog;
USE blog;

CREATE TABLE users(
    id              INT(255) AUTO_INCREMENT NOT NULL,
    name            VARCHAR(50) NOT NULL,
    surname         VARCHAR(100),
    role            VARCHAR(20),
    email           VARCHAR(255) NOT NULL,
    password        VARCHAR(255) NOT NULL,
    description     TEXT,
    image           VARCHAR(255),
    created_at      DATETIME DEFAULT NULL,
    updated_at      DATETIME DEFAULT NULL,
    remember_token  VARCHAR(255),
    CONSTRAINT pk_users PRIMARY KEY(id)
)ENGINE=InnoDb;

CREATE TABLE categories(
    id              INT(255) AUTO_INCREMENT NOT NULL,
    name            VARCHAR(100) NOT NULL,
    created_at      DATETIME DEFAULT NULL,
    updated_at      DATETIME DEFAULT NULL,
    CONSTRAINT pk_categories PRIMARY KEY(id)
)ENGINE=InnoDb;

CREATE TABLE posts(
    id              INT(255) AUTO_INCREMENT NOT NULL,
    user_id         INT(255) NOT NULL,
    category_id     INT(255) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    content         TEXT,
    image           VARCHAR(255),
    created_at      DATETIME DEFAULT NULL,
    updated_at      DATETIME DEFAULT NULL,
    CONSTRAINT pk_posts PRIMARY KEY(id),
    CONSTRAINT fk_post_user FOREIGN KEY(user_id) REFERENCES users(id),
    CONSTRAINT fk_post_category FOREIGN KEY(category_id) REFERENCES categories(id)
)ENGINE=InnoDb;

INSERT INTO `users` VALUES (
    1,'admin','admin','ROLE_USER','admin@admin.com','8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918',NULL,NULL,NULL,NULL,NULL
);

INSERT INTO `categories` VALUES (
    1,'General',NULL,NULL
);