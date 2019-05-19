DROP TABLE IF EXISTS post;
CREATE TABLE IF NOT EXISTS post
(
    id      INTEGER PRIMARY KEY AUTO_INCREMENT,
    correct BOOLEAN,
    source  TEXT,
    ip      TEXT,
    time    INTEGER
);

DROP TABLE IF EXISTS `key`;
CREATE TABLE IF NOT EXISTS `key`
(
    id      INTEGER PRIMARY KEY AUTO_INCREMENT,
    post_id INTEGER,
    `key`   TEXT
);

DROP TABLE IF EXISTS `cmocl`;
CREATE TABLE IF NOT EXISTS `cmocl`
(
    id      INTEGER PRIMARY KEY AUTO_INCREMENT,
    source  TEXT,
    period  TEXT,
    date    TEXT,
    data    TEXT
);

DROP TABLE IF EXISTS `setting`;
CREATE TABLE IF NOT EXISTS `setting`
(
    `key`   TEXT,
    `value` TEXT
);
