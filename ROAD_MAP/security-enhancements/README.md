# Security Enhancements Feature

## Overview
Enhance Wuplicator installer with advanced security features including randomized admin credentials and WordPress security keys regeneration.

## Goals
1. Generate secure random admin usernames following pattern: `admin_[5 random chars: uppercase, lowercase, numbers]`
2. Generate secure random passwords: 12 characters (uppercase, lowercase, numbers)
3. Regenerate all 8 WordPress security keys/salts in wp-config.php
4. Provide option to enable/disable these features via configuration flags

## Security Benefits
- **Unique Admin Credentials**: Prevents default admin username attacks
- **Strong Passwords**: 12-character random passwords provide high entropy
- **Fresh Security Keys**: Invalidates any potentially compromised session tokens
- **Migration Security**: Each deployment gets unique security credentials

## Implementation Steps

### STEP1: Admin Credentials Randomization
**File**: `src/installer.php`
**Scope**: Add random username/password generation with configuration options

**Changes**:
- Add configuration flags: `$RANDOMIZE_ADMIN_USER`, `$RANDOMIZE_ADMIN_PASS`
- Implement `generateRandomUsername()` method (admin_[5 chars: A-Za-z0-9])
- Implement `generateRandomPassword()` method (12 chars: A-Za-z0-9)
- Modify `updateAdminCredentials()` to use generated values when flags enabled
- Display generated credentials in UI for user reference
- Update logs to show generated credentials (important for user to save)

**Testing**:
- Verify username format: `admin_` + exactly 5 alphanumeric chars
- Verify password: exactly 12 alphanumeric chars (mixed case + numbers)
- Test WordPress login with generated credentials
- Ensure credentials displayed to user before installer deletion

### STEP2: Security Keys Regeneration
**File**: `src/installer.php`
**Scope**: Regenerate WordPress security keys/salts in wp-config.php

**Changes**:
- Add configuration flag: `$REGENERATE_SECURITY_KEYS`
- Implement `generateSecurityKey()` method (64-char cryptographically secure random string)
- Implement `regenerateWPSecurityKeys()` method to update wp-config.php
- Replace all 8 WordPress constants:
  - AUTH_KEY
  - SECURE_AUTH_KEY
  - LOGGED_IN_KEY
  - NONCE_KEY
  - AUTH_SALT
  - SECURE_AUTH_SALT
  - LOGGED_IN_SALT
  - NONCE_SALT
- Use regex-based replacement to preserve wp-config.php structure
- Log successful regeneration

**Testing**:
- Verify all 8 keys are replaced with unique 64-char strings
- Test WordPress login after key regeneration (should invalidate old sessions)
- Verify wp-config.php syntax remains valid
- Ensure no duplicate keys generated

## Configuration Options

```php
// Enable admin username randomization
$RANDOMIZE_ADMIN_USER = true;

// Enable admin password randomization  
$RANDOMIZE_ADMIN_PASS = true;

// Enable security keys regeneration
$REGENERATE_SECURITY_KEYS = true;
```

## Character Sets

### Admin Username Suffix (5 chars)
- Uppercase: A-Z
- Lowercase: a-z  
- Numbers: 0-9
- Pattern: `admin_[A-Za-z0-9]{5}`
- Example: `admin_aB3x9`

### Admin Password (12 chars)
- Uppercase: A-Z
- Lowercase: a-z
- Numbers: 0-9
- Length: 12
- Example: `sL8kJ3hG9mP2`

### Security Keys (64 chars each)
- Character set: A-Za-z0-9 and special characters (!@#$%^&*()_+-=[]{}|)
- Length: 64
- Cryptographically secure (random_bytes)

## Dependencies
- PHP 7.0+ (for `random_bytes()`)
- WordPress PasswordHash class (already required)

## Backward Compatibility
- All features are **opt-in** via configuration flags
- If flags disabled, behaves exactly like current version
- Manual credential specification still supported

## Status
- **Status**: In Progress
- **Priority**: High (Security Enhancement)
- **Steps**: 2
- **Estimated Time**: 2-3 hours
