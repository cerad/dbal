DROP DATABASE IF EXISTS tests;

CREATE DATABASE tests;

USE tests;

CREATE TABLE users 
(
  id INT AUTO_INCREMENT        NOT NULL, 
  user_name       VARCHAR(255) NOT NULL,
  disp_name       VARCHAR(255),
  email           VARCHAR(255) NOT NULL,
  email_verified  BOOLEAN      DEFAULT false,
  password        VARCHAR(255),
  salt            VARCHAR(255),
  roles           VARCHAR(999),
  person_key      VARCHAR( 80),
  person_verified BOOLEAN      DEFAULT false,
  status          VARCHAR( 20) DEFAULT 'Active',
  PRIMARY KEY(id),
  UNIQUE  INDEX users__user_name (user_name), 
  UNIQUE  INDEX users__email     (email), 
          INDEX users__person_key(person_key)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

CREATE TABLE user_auths 
(
  id      INT AUTO_INCREMENT NOT NULL, 
  user_id INT                NOT NULL,

  provider VARCHAR(255) NOT NULL,
  sub      VARCHAR(255) NOT NULL,
  iss      VARCHAR(255) NOT NULL,
  name     VARCHAR(255),
  email    VARCHAR(255),
  PRIMARY KEY(id),
  FOREIGN KEY(user_id) REFERENCES users(id),
  UNIQUE   INDEX user_auths__provider_sub_iss(provider,sub,iss)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

