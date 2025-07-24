# IT Systems Inventory Management System

A comprehensive PHP-based web application for managing IT equipment inventory with MariaDB database, featuring SSSD/LDAP authentication, Excel import/export, and advanced search capabilities.

## Features

- **Complete Inventory Management**: Track make, model, serial number, property number, warranty end date, excess date, use case, and location
- **SSSD/LDAP Authentication**: Support for both local database authentication and SSSD/LDAP integration
- **Excel Import/Export**: Full CSV/Excel compatibility for data migration
- **Advanced Search**: Search across all fields with filtering capabilities
- **Responsive Design**: Bootstrap 5-based responsive interface
- **User Management**: Role-based access control (admin/user roles)
- **Audit Trail**: Track who created/modified inventory items

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MariaDB/MySQL
- **Frontend**: Bootstrap 5, jQuery, Font Awesome
- **Authentication**: Local database + SSSD/LDAP
- **Export/Import**: CSV format (Excel compatible)

## Installation Guide

### 1. Database Setup

1. Install MariaDB/MySQL on your server
2. Create the database and tables:

```bash
mysql -u root -p < database/schema.sql
```

### 2. Web Server Configuration

#### Apache (LAMP Stack)
```bash
# Install Apache and PHP
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-mysql php-ldap php-pdo

# Enable required PHP modules
sudo phpenmod pdo_mysql
sudo systemctl restart apache2
```

#### PHP Extensions Required
- `php-mysql` or `php-mysqli`
- `php-pdo`
- `php-ldap` (for SSSD authentication)

### 3. Application Setup

1. **Clone or extract the application** to your web directory:
```bash
sudo mkdir -p /var/www/html/inventory/
sudo cp -r * /var/www/html/inventory/
sudo chown -R www-data:www-data /var/www/html/inventory/
```

2. **Configure database connection**:
   - Copy `config/.env.example` to `config/.env`
   - Update the database credentials:
   ```bash
   cp config/.env.example config/.env
   nano config/.env
   ```

3. **Set permissions**:
```bash
sudo chmod 755 -R /var/www/html/inventory/
sudo chmod 644 /var/www/html/inventory/config/.env
```

### 4. SSSD Configuration (Optional)

For SSSD/LDAP authentication, update the LDAP settings in `config/.env`:

```env
LDAP_HOST=your-ldap-server.company.com
LDAP_PORT=389
LDAP_BASE_DN=dc=company,dc=com
LDAP_BIND_DN=cn=admin,dc=company,dc=com
LDAP_BIND_PASSWORD=your-ldap-password
LDAP_USER_FILTER=(uid=%s)
```

### 5. Initial Login

After setup, access the application at `http://your-server/inventory/` and login with:
- **Username**: admin
- **Password**: admin123

## Database Schema

### Main Tables
- `inventory`: Core inventory items
- `locations`: Equipment locations
- `use_cases`: Equipment use case categories
- `users`: User authentication

### Indexes
Created for optimal search performance across all searchable fields.

## Usage Guide

### Adding New Items
1. Navigate to Inventory → Add New Item
2. Fill in all required fields
3. Select location and use case from dropdowns
4. Save the item

### Excel Import/Export
1. **Export**: Go to Export/Import → Export to CSV
2. **Import**: Use the same page to upload CSV files
3. **Format**: Follow the export format for imports

### Advanced Search
1. Use the Search page for detailed filtering
2. Combine multiple criteria for precise results
3. Export filtered results to CSV

## Security Features

- **CSRF Protection**: Token-based form protection
- **SQL Injection Prevention**: PDO prepared statements
- **Input Validation**: Server-side validation for all inputs
- **XSS Prevention**: HTML entity encoding for all outputs
- **Password Hashing**: bcrypt password storage

## Backup Strategy

### Database Backup
```bash
# Daily backup
mysqldump -u root -p it_inventory > backup_$(date +%Y%m%d).sql

# Automated backup script
sudo crontab -e
# Add: 0 2 * * * mysqldump -u root -p your_password it_inventory > /backups/inventory_$(date +\%Y\%m\%d).sql
```

### File Backup
```bash
# Backup application files
tar -czf inventory_backup_$(date +%Y%m%d).tar.gz /var/www/html/inventory/
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in config/.env
   - Ensure MySQL service is running
   - Verify database exists

2. **Import/Export Issues**
   - Ensure PHP has write permissions to uploads directory
   - Check PHP upload_max_filesize and post_max_size
   - Verify CSV format matches export format

3. **SSSD Authentication Not Working**
   - Check LDAP extension is installed: `php -m | grep ldap`
   - Verify LDAP server connectivity
   - Check SSSD service status

### Log Files
- Apache: `/var/log/apache2/error.log`
- PHP: `/var/log/php_errors.log`
- Application: Check browser developer tools console

## Development

### Adding New Fields
1. Update database schema
2. Modify validation in `includes/functions.php`
3. Update forms in `pages/inventory/`
4. Update export/import functionality

### Custom Styling
- Edit `assets/css/custom.css` for general styles
- Edit `assets/css/login.css` for login page styles
- Use Bootstrap 5 classes for consistent design

## Support

For issues or feature requests, please check:
1. This README
2. Application logs
3. Browser developer tools
4. Server logs

## License

This project is provided as-is for educational and internal use purposes.