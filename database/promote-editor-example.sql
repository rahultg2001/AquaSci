-- Run in phpMyAdmin only after registering your own account.
-- Replace the email with the account you control; never let authors select their own role.
UPDATE users
SET role = 'editor'
WHERE email = 'YOUR_EDITOR_EMAIL@example.com'
LIMIT 1;
