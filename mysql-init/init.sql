CREATE DATABASE IF NOT EXISTS `authentication`;
CREATE DATABASE IF NOT EXISTS `novels`;
GRANT ALL ON `authentication`.* TO 'admin'@'%';
GRANT ALL ON `novels`.* TO 'admin'@'%';

