CREATE DATABASE IF NOT EXISTS minimal_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS minimal_concurrency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON minimal_test.* TO 'minimal'@'%';
GRANT ALL PRIVILEGES ON minimal_concurrency.* TO 'minimal'@'%';
