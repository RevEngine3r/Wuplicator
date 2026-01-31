# STEP1: Admin Credentials Randomization

## Objective
Implement random admin username and password generation with configurable options.

## Scope
Modify `src/installer.php` to generate secure random admin credentials following specified patterns.

## Implementation Plan

### 1. Add Configuration Flags
```php
// After existing admin config variables
$RANDOMIZE_ADMIN_USER = false; // Set true to generate random username
$RANDOMIZE_ADMIN_PASS = false; // Set true to generate random password
```

### 2. Add Helper Methods

#### generateRandomUsername()
```php
/**
 * Generate random admin username
 * Pattern: admin_[5 random alphanumeric chars]
 * 
 * @return string Generated username (e.g., 'admin_aB3x9')
 */
private function generateRandomUsername() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $suffix = '';
    $length = 5;
    
    for ($i = 0; $i < $length; $i++) {
        $suffix .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return 'admin_' . $suffix;
}
```

#### generateRandomPassword()
```php
/**
 * Generate random admin password
 * Pattern: 12 random alphanumeric chars (upper, lower, numbers)
 * 
 * @return string Generated password (e.g., 'sL8kJ3hG9mP2')
 */
private function generateRandomPassword() {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    $length = 12;
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}
```

### 3. Modify updateAdminCredentials()

Update the method to use generated credentials when flags enabled:

```php
private function updateAdminCredentials($newUser, $newPass) {
    global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD, $BACKUP_METADATA;
    global $RANDOMIZE_ADMIN_USER, $RANDOMIZE_ADMIN_PASS;
    
    $this->log('Updating admin credentials...');
    
    // Generate random credentials if flags enabled
    if ($RANDOMIZE_ADMIN_USER) {
        $newUser = $this->generateRandomUsername();
        $this->log("Generated random username: {$newUser}");
    }
    
    if ($RANDOMIZE_ADMIN_PASS) {
        $newPass = $this->generateRandomPassword();
        $this->log("Generated random password: {$newPass}");
    }
    
    // Skip if no credentials to update
    if (empty($newUser) && empty($newPass)) {
        $this->log('No admin credentials to update');
        return;
    }
    
    try {
        $pdo = new PDO("mysql:host={$NEW_DB_HOST};dbname={$NEW_DB_NAME}", $NEW_DB_USER, $NEW_DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $prefix = $BACKUP_METADATA['table_prefix'];
        
        // Find admin user (ID = 1 usually)
        $stmt = $pdo->query("SELECT ID FROM {$prefix}users WHERE ID = 1 LIMIT 1");
        $adminId = $stmt->fetchColumn();
        
        if (!$adminId) {
            $this->error('Admin user not found');
            return;
        }
        
        // Update username
        if (!empty($newUser)) {
            $stmt = $pdo->prepare("UPDATE {$prefix}users SET user_login = ? WHERE ID = ?");
            $stmt->execute([$newUser, $adminId]);
            $this->log("✓ Admin username set to: {$newUser}");
        }
        
        // Update password
        if (!empty($newPass)) {
            require_once($this->workDir . '/wp-includes/class-phpass.php');
            $hasher = new PasswordHash(8, true);
            $hashedPass = $hasher->HashPassword($newPass);
            
            $stmt = $pdo->prepare("UPDATE {$prefix}users SET user_pass = ? WHERE ID = ?");
            $stmt->execute([$hashedPass, $adminId]);
            $this->log("✓ Admin password set to: {$newPass}");
        }
        
        // IMPORTANT: Store credentials for final display
        $_SESSION['final_admin_user'] = $newUser;
        $_SESSION['final_admin_pass'] = $newPass;
        
    } catch (PDOException $e) {
        $this->error('Admin update failed: ' . $e->getMessage());
    }
}
```

### 4. Update configureWordPress()

Ensure the method calls credential update appropriately:

```php
// Change admin credentials (existing or generated)
global $RANDOMIZE_ADMIN_USER, $RANDOMIZE_ADMIN_PASS;
if (!empty($NEW_ADMIN_USER) || !empty($NEW_ADMIN_PASS) || $RANDOMIZE_ADMIN_USER || $RANDOMIZE_ADMIN_PASS) {
    $this->updateAdminCredentials($NEW_ADMIN_USER, $NEW_ADMIN_PASS);
}
```

### 5. Update finalizeInstallation()

Display generated credentials prominently:

```php
private function finalizeInstallation() {
    $this->log('Finalizing installation...');
    
    // Display generated credentials if any
    if (isset($_SESSION['final_admin_user']) && isset($_SESSION['final_admin_pass'])) {
        $this->log('═══════════════════════════════════════');
        $this->log('⚠️  IMPORTANT: SAVE THESE CREDENTIALS');
        $this->log('═══════════════════════════════════════');
        $this->log("Admin Username: {$_SESSION['final_admin_user']}");
        $this->log("Admin Password: {$_SESSION['final_admin_pass']}");
        $this->log('═══════════════════════════════════════');
    }
    
    // Delete backup files
    $zipFile = $this->workDir . '/backup.zip';
    if (file_exists($zipFile)) {
        unlink($zipFile);
        $this->log('Backup archive deleted');
    }
    
    $sqlFile = $this->findSQLFile();
    if ($sqlFile && file_exists($sqlFile)) {
        unlink($sqlFile);
        $this->log('SQL file deleted');
    }
    
    $this->log('Installation complete!');
    $this->log('IMPORTANT: Delete installer.php manually for security');
}
```

## Testing Checklist

- [ ] Username format: `admin_` + exactly 5 alphanumeric characters
- [ ] Password format: exactly 12 alphanumeric characters (mixed case)
- [ ] Both uppercase and lowercase letters present in outputs
- [ ] Numbers included in outputs
- [ ] Credentials logged and displayed to user
- [ ] WordPress login successful with generated credentials
- [ ] Manual credentials still work when flags disabled
- [ ] Session storage of credentials works correctly

## Security Considerations

- Use `random_int()` for cryptographically secure randomness
- Display credentials clearly so user can save them
- Credentials shown in logs (which user must see before installer deleted)
- No special characters to avoid confusion/typing errors

## Files Modified
- `src/installer.php`

## Estimated Time
1-1.5 hours
