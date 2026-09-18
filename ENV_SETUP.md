# Environment Configuration Setup Guide

## Overview

This application now uses environment variables for all sensitive configuration, including database credentials and security settings. This prevents hardcoded credentials from being committed to version control and improves security.

## Quick Setup

1. **Copy the example file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit the `.env` file** with your actual database credentials and settings

3. **Ensure the `.env` file is never committed** to git (already added to `.gitignore`)

## Required Environment Variables

### Application Settings
- `APP_ENV`: Environment (development/production)
- `APP_DEBUG`: Enable debug mode (true/false) - **NEVER true in production**
- `APP_URL`: Your application's base URL

### Security Settings
- `SESSION_SECURE`: Use secure cookies (true/false) - **Set to true for HTTPS**
- `SESSION_HTTPONLY`: HttpOnly cookies (true/false) - **Keep true**
- `SESSION_SAMESITE`: SameSite cookie policy (Strict/Lax/None) - **Use Strict for better security**
- `SESSION_TIMEOUT`: Session timeout in seconds (default: 1800 = 30 minutes)

### Database Connections

The application uses multiple databases for different subsystems. Each requires its own set of credentials:

#### Core Database (Authentication, Sessions)
- `DB_CORE_HOST`: Database host
- `DB_CORE_USERNAME`: Database username
- `DB_CORE_PASSWORD`: Database password
- `DB_CORE_DATABASE`: Database name

#### Purchase Order Management Database
- `DB_POM_HOST`: Database host
- `DB_POM_USERNAME`: Database username
- `DB_POM_PASSWORD`: Database password
- `DB_POM_DATABASE`: Database name

#### Inventory Management System Database
- `DB_IMS_HOST`: Database host
- `DB_IMS_USERNAME`: Database username
- `DB_IMS_PASSWORD`: Database password
- `DB_IMS_DATABASE`: Database name

#### Supplier Management Database
- `DB_SVM_HOST`: Database host
- `DB_SVM_USERNAME`: Database username
- `DB_SVM_PASSWORD`: Database password
- `DB_SVM_DATABASE`: Database name

#### Procurement System Management Database
- `DB_PSM_HOST`: Database host
- `DB_PSM_USERNAME`: Database username
- `DB_PSM_PASSWORD`: Database password
- `DB_PSM_DATABASE`: Database name

#### Document Tracking & Records System Database
- `DB_DTRS_HOST`: Database host
- `DB_DTRS_USERNAME`: Database username
- `DB_DTRS_PASSWORD`: Database password
- `DB_DTRS_DATABASE`: Database name

#### Warehouse & Stock Management Database
- `DB_SWS_HOST`: Database host
- `DB_SWS_USERNAME`: Database username
- `DB_SWS_PASSWORD`: Database password
- `DB_SWS_DATABASE`: Database name

### CSRF Protection
- `CSRF_TOKEN_LENGTH`: Length of CSRF tokens (default: 32)
- `CSRF_TOKEN_EXPIRY`: Token expiry time in seconds (default: 3600 = 1 hour)

### Rate Limiting
- `RATE_LIMIT_ENABLED`: Enable rate limiting (true/false)
- `RATE_LIMIT_MAX_ATTEMPTS`: Max attempts per window (default: 5)
- `RATE_LIMIT_WINDOW`: Time window in seconds (default: 900 = 15 minutes)
- `RATE_LIMIT_LOCKOUT_DURATION`: Lockout duration in seconds (default: 1800 = 30 minutes)

## Security Best Practices

1. **Never commit `.env` files** to version control
2. **Use strong, unique passwords** for each database
3. **Rotate credentials regularly** in production
4. **Restrict file permissions** on `.env` files (chmod 600)
5. **Use different credentials** for development and production
6. **Monitor for unauthorized access** to environment variables

## Docker Deployment

When deploying with Docker, you can set environment variables:

1. **Via docker-compose.yml:**
   ```yaml
   environment:
     - DB_CORE_HOST=your-host
     - DB_CORE_USERNAME=your-user
     - DB_CORE_PASSWORD=your-password
     - DB_CORE_DATABASE=your-database
   ```

2. **Via .env file in Docker context:**
   The `.env` file will be automatically loaded if present in the build context.

3. **Via Docker run command:**
   ```bash
   docker run -e DB_CORE_HOST=your-host -e DB_CORE_USERNAME=your-user ...
   ```

## Troubleshooting

### "Database configuration incomplete" error
This means required environment variables are not set. Check your `.env` file and ensure all required variables for the databases you're using are set.

### Connection errors after migration
If you just migrated from hardcoded credentials, ensure:
1. The `.env` file exists in the project root
2. All required variables are set with correct values
3. The `.env` file has proper permissions

### Debug mode not working
Set `APP_DEBUG=true` in your `.env` file. **Never use this in production.**

## Migration from Hardcoded Credentials

If you were previously using hardcoded credentials:

1. Extract the credentials from your old connection files
2. Add them to the `.env` file
3. Test the connection locally
4. Commit the changes (excluding `.env`)
5. Deploy the updated code
6. Set environment variables in your production environment

## Additional Resources

- [PHP Environment Variables](https://www.php.net/manual/en/function.getenv.php)
- [OWASP Environment Variables Guide](https://cheatsheetseries.owasp.org/cheatsheets/Environment_Variables_Cheat_Sheet.html)
