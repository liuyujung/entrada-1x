UPDATE `communities_template_permissions` SET `permission_value` = 'faculty,staff,medtech' WHERE `permission_value` = 'faculty,staff';

UPDATE `settings` SET `value` = '1304' WHERE `shortname` = 'version_db';