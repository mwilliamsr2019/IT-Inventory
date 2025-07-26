# IT Inventory Management System - Security Documentation

## Overview
This document outlines the security measures implemented in the IT Inventory Management System.

## Security Features Implemented

### 1. Authentication & Authorization
- **SSO/AD Integration**: Support for SSSD/LDAP authentication
- **Database Authentication**: Fallback local authentication
- **Role-based Access Control**: Admin and user roles
- **Rate Limiting**: Login attempt restrictions
- **Session Management**: Secure session handling with regeneration

### 2. Input Validation & Sanitization
- **SQL Injection Prevention**: Prepared statements (PDO) throughout
- **XSS Prevention**: HTML entity encoding and output sanitization
- **CSRF Protection**: Token-based protection for all forms
- **File Upload Security**: MIME type and extension validation
- **Input Validation**: Server-side validation for all user inputs

### 3. Data Security
- **Password Hashing**: bcrypt with salt
- **Data Encryption**: HTTPS enforcement via HSTS
- **Sensitive Data Protection**: No plain text storage of passwords
- **Audit Logging**: Security event logging

### 4. File Security
- **Upload Restrictions**: Only CSV files allowed (max 10MB)
- **Path Traversal Prevention**: File name sanitization
- **MIME Type Validation**: Server-side file type verification

### 5. HTTP Security Headers
- **X-Frame-Options**: Prevents clickjacking
- **X-Content-Type-Options**: Prevents MIME sniffing
- **X-XSS-Protection**: Enables XSS filtering
- **Strict-Transport-Security**: Enforces HTTPS
- **Content-Security-Policy**: Restricts resource loading

### 6. Database Security
- **Parameterized Queries**: Prevents SQL injection
- **Least Privilege**: Database user with minimal required permissions
- **Connection Security**: Secure database configuration
- **Input Validation**: Data type and format validation

## Security Checklist

### ✅ Completed Security Measures

1. **Authentication Security**
   - [x] Strong password hashing (bcrypt)
   - [x] Rate limiting on login attempts
   - [x] Session management with regeneration
   - [x] SSO/AD integration for enterprise authentication
   - [x] Role-based access control

2. **Input Validation**
   - [x] All user inputs sanitized
   - [x] SQL injection prevention via prepared statements
   - [x] XSS prevention via output encoding
   - [x] CSRF tokens on all forms
   - [x] File upload restrictions and validation

3. **Data Protection**
   - [x] Sensitive data encryption at rest
   - [x] Secure password storage
   - [x] Audit logging for security events
   - [x] HTTPS enforcement

4. **Access Control**
   - [x] User authentication required
   - [x] Authorization checks
   - [x] Session timeout management
   - [x] Secure logout functionality

5. **File Security**
   - [x] Restricted file types
   - [x] File size limits
   - [x] MIME type validation
   - [x] Secure file handling

## Security Configuration

### Environment Variables
Create a `.env` file based on `.env.example` with your actual values:

```bash
# Database Configuration
DB_HOST=localhost
DB_NAME=it_inventory
DB_USER=your_db_user
DB_PASS=your_secure_password

# Security Settings
SESSION_TIMEOUT=3600
CSRF_TOKEN_LIFETIME=3600

# LDAP Configuration (for SSO)
LDAP_HOST=your-ldap-server.com
LDAP_PORT=389
LDAP_BASE_DN=dc=yourcompany,dc=com
```

### File Permissions
```bash
# Set proper permissions
chmod 600 config/.env
chmod 755 logs/
chmod 644 *.php
chmod 755 uploads/
```

### Database Security
```sql
-- Create restricted database user
CREATE USER 'itinv_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON it_inventory.* TO 'itinv_user'@'localhost';
FLUSH PRIVILEGES;
```

## Security Monitoring

### Log Files
- **Security Events**: `logs/security.log`
- **Query Logs**: `logs/queries.log` (development only)
- **Access Logs**: Standard Apache/Nginx logs

### Monitoring Commands
```bash
# Check for failed login attempts
grep "FAILED_LOGIN" logs/security.log

# Check for file upload attempts
grep "INVALID_FILE_UPLOAD" logs/security.log

# Monitor successful logins
grep "SUCCESSFUL_LOGIN" logs/security.log
```

## Security Best Practices

### Regular Maintenance
1. **Update Dependencies**: Keep PHP and libraries updated
2. **Review Logs**: Regular security log analysis
3. **Backup Security**: Encrypted backups
4. **Access Review**: Periodic user access review

### Incident Response
1. **Immediate Actions**: Isolate affected systems
2. **Investigation**: Analyze security logs
3. **Recovery**: Restore from secure backups
4. **Prevention**: Implement additional controls

## Security Testing

### Manual Testing Checklist
- [ ] Test SQL injection attempts
- [ ] Test XSS payloads
- [ ] Test file upload restrictions
- [ ] Test authentication bypass
- [ ] Test CSRF protection
- [ ] Test rate limiting

### Automated Testing
```bash
# Run security scans
nikto -h http://your-domain.com
sqlmap -u "http://your-domain.com/search.php?search=test"
```

## Contact & Support

For security-related issues:
- Report vulnerabilities to: security@yourcompany.com
- Emergency contact: +1-XXX-XXX-XXXX
- Documentation updates: IT Security Team