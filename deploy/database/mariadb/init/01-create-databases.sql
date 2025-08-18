-- Create databases for multi-tenant SSO system
CREATE DATABASE IF NOT EXISTS sso_main CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS tenant1_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS tenant2_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create test databases
CREATE DATABASE IF NOT EXISTS sso_main_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS tenant1_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS tenant2_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant privileges to sso_user for all databases
GRANT ALL PRIVILEGES ON sso_main.* TO 'sso_user'@'%';
GRANT ALL PRIVILEGES ON tenant1_db.* TO 'sso_user'@'%';
GRANT ALL PRIVILEGES ON tenant2_db.* TO 'sso_user'@'%';
GRANT ALL PRIVILEGES ON sso_main_test.* TO 'sso_user'@'%';
GRANT ALL PRIVILEGES ON tenant1_db_test.* TO 'sso_user'@'%';
GRANT ALL PRIVILEGES ON tenant2_db_test.* TO 'sso_user'@'%';

FLUSH PRIVILEGES;