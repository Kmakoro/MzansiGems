USE hidden_gems;

UPDATE settings
SET setting_value = 'Mzansi Gem'
WHERE setting_key = 'site_name';

UPDATE IGNORE users
SET email = 'admin@mzansigem.local'
WHERE email = 'admin@hiddengems.local';

UPDATE IGNORE users
SET email = 'user@mzansigem.local'
WHERE email = 'user@hiddengems.local';
