CREATE DATABASE IF NOT EXISTS `witlt_local` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
GRANT ALL ON `witlt_local`.* TO 'witlt_user'@'%';
CREATE DATABASE IF NOT EXISTS `witlt_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
GRANT ALL ON `witlt_testing`.* TO 'witlt_user'@'%';
FLUSH PRIVILEGES;