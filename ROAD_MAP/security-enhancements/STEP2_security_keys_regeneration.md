# STEP2: Security Keys Regeneration

## Objective
Regenerate all WordPress security keys and salts in wp-config.php during installation.

## Scope
Modify `src/installer.php` to replace all 8 WordPress security constants with fresh cryptographically-secure random values.

## WordPress Security Constants

WordPress uses 8 security constants for authentication and session management:

1. **AUTH_KEY**
2. **SECURE_AUTH_KEY**
3. **LOGGED_IN_KEY**
4. **NONCE_KEY**
5. **AUTH_SALT**
6. **SECURE_AUTH_SALT**
7. **LOGGED_IN_SALT**
8. **NONCE_SALT**

These should be unique, random, 64-character strings.

## Implementation Plan

### 1. Add Configuration Flag

```php
// After other configuration variables
$REGENERATE_SECURITY_KEYS = false; // Set true to regenerate WordPress security keys
```

### 2. Add Helper Method: generateSecurityKey()

```php
/**
 * Generate cryptographically secure random key
 * 
 * @param int $length Key length (default 64)
 * @return string Random key
 */
private function generateSecurityKey($length = 64) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
    $key = '';
    $charsLength = strlen($chars);
    
    // Use random_bytes for cryptographic security
    $randomBytes = random_bytes($length);
    
    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[ord($randomBytes[$i]) % $charsLength];
    }
    
    return $key;
}
```

### 3. Add Method: regenerateWPSecurityKeys()

```php
/**
 * Regenerate WordPress security keys in wp-config.php
 * Replaces all 8 security constants with fresh random values
 */
private function regenerateWPSecurityKeys() {
    $this->log('Regenerating WordPress security keys...');
    
    $wpConfig = $this->workDir . '/wp-config.php';
    if (!file_exists($wpConfig)) {
        $this->error('wp-config.php not found');
        return;
    }
    
    $content = file_get_contents($wpConfig);
    
    // Define all 8 keys that need regeneration
    $keys = [
        'AUTH_KEY',
        'SECURE_AUTH_KEY',
        'LOGGED_IN_KEY',
        'NONCE_KEY',
        'AUTH_SALT',
        'SECURE_AUTH_SALT',
        'LOGGED_IN_SALT',
        'NONCE_SALT'
    ];
    
    // Replace each key with a new random value
    foreach ($keys as $key) {
        $newValue = $this->generateSecurityKey(64);
        
        // Match define('KEY', 'value') with various spacing/quote styles
        $pattern = "/define\s*\(\s*['\"]" . preg_quote($key, '/') . "['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/";
        $replacement = "define('{$key}', '{$newValue}')";
        
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Write updated config
    if (file_put_contents($wpConfig, $content) !== false) {
        $this->log('✓ All 8 security keys regenerated successfully');
        $this->log('  - AUTH_KEY');
        $this->log('  - SECURE_AUTH_KEY');
        $this->log('  - LOGGED_IN_KEY');
        $this->log('  - NONCE_KEY');
        $this->log('  - AUTH_SALT');
        $this->log('  - SECURE_AUTH_SALT');
        $this->log('  - LOGGED_IN_SALT');
        $this->log('  - NONCE_SALT');
    } else {
        $this->error('Failed to write wp-config.php');
    }
}
```

### 4. Update configureWordPress()

Call security key regeneration after wp-config database updates:

```php
private function configureWordPress() {
    global $NEW_DB_HOST, $NEW_DB_NAME, $NEW_DB_USER, $NEW_DB_PASSWORD, $NEW_SITE_URL;
    global $NEW_ADMIN_USER, $NEW_ADMIN_PASS, $BACKUP_METADATA;
    global $RANDOMIZE_ADMIN_USER, $RANDOMIZE_ADMIN_PASS, $REGENERATE_SECURITY_KEYS;
    
    $this->log('Configuring WordPress...');
    
    // Update wp-config.php database settings
    $wpConfig = $this->workDir . '/wp-config.php';
    if (file_exists($wpConfig)) {
        $content = file_get_contents($wpConfig);
        
        $content = preg_replace("/define\s*\(\s*'DB_NAME'\s*,\s*'[^']*'\s*\)/", "define('DB_NAME', '{$NEW_DB_NAME}')", $content);
        $content = preg_replace("/define\s*\(\s*'DB_USER'\s*,\s*'[^']*'\s*\)/", "define('DB_USER', '{$NEW_DB_USER}')", $content);
        $content = preg_replace("/define\s*\(\s*'DB_PASSWORD'\s*,\s*'[^']*'\s*\)/", "define('DB_PASSWORD', '{$NEW_DB_PASSWORD}')", $content);
        $content = preg_replace("/define\s*\(\s*'DB_HOST'\s*,\s*'[^']*'\s*\)/", "define('DB_HOST', '{$NEW_DB_HOST}')", $content);
        
        file_put_contents($wpConfig, $content);
        $this->log('wp-config.php database settings updated');
    }
    
    // Regenerate security keys if enabled
    if ($REGENERATE_SECURITY_KEYS) {
        $this->regenerateWPSecurityKeys();
    }
    
    // Update URLs in database
    $oldUrl = $BACKUP_METADATA['site_url'];
    if ($oldUrl && $NEW_SITE_URL && $oldUrl !== $NEW_SITE_URL) {
        $this->replaceURLs($oldUrl, $NEW_SITE_URL);
    }
    
    // Change admin credentials (existing or generated)
    if (!empty($NEW_ADMIN_USER) || !empty($NEW_ADMIN_PASS) || $RANDOMIZE_ADMIN_USER || $RANDOMIZE_ADMIN_PASS) {
        $this->updateAdminCredentials($NEW_ADMIN_USER, $NEW_ADMIN_PASS);
    }
}
```

## Testing Checklist

- [ ] All 8 keys replaced in wp-config.php
- [ ] Each key is exactly 64 characters
- [ ] Keys contain alphanumeric + special characters
- [ ] All keys are unique (no duplicates)
- [ ] wp-config.php remains syntactically valid (no parse errors)
- [ ] WordPress loads successfully after key regeneration
- [ ] Old sessions invalidated (test by logging in before/after)
- [ ] define() statements maintain proper format
- [ ] Works with both single and double quotes in wp-config.php

## Security Benefits

### Why Regenerate Keys?

1. **Session Security**: Fresh keys invalidate all existing sessions/cookies
2. **Migration Best Practice**: Each deployment should have unique keys
3. **Compromise Prevention**: If source site was compromised, new keys prevent session hijacking
4. **Zero Trust**: Don't trust backup security keys are still secret

### Key Properties

- **Length**: 64 characters (WordPress standard)
- **Entropy**: Cryptographically secure (`random_bytes()`)
- **Character Set**: Alphanumeric + special chars (high entropy)
- **Uniqueness**: Each key independently generated

## Error Handling

- Check wp-config.php exists before modification
- Verify file write permissions
- Log each key regenerated for audit trail
- Graceful failure if regex doesn't match (log warning)

## Backward Compatibility

- Feature is **opt-in** via `$REGENERATE_SECURITY_KEYS` flag
- If disabled, original keys preserved (current behavior)
- No breaking changes to existing installations

## Files Modified

- `src/installer.php`

## Estimated Time

1-1.5 hours

## Security Notes

⚠️ **Important**: Key regeneration will log out all existing users. This is intentional and improves security during migration.

✓ **Best Practice**: Always regenerate keys when deploying to new environment.
