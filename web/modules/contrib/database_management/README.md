# Database Management

This module provides drush commands to help manage your database backups.

## Requirements

- zcat
- gzip

## Installation

1. Enable the `database_management` module.

2. Edit `settings.php` to add your AWS access key and secret key:

```
$settings['aws_s3_key'] = '';
$settings['aws_s3_secret'] = '';
```

3. Navigate to the database management configuration page (/admin/config/development/database_management) and update the bucket value to the S3 bucket where you want to store your backups.

## Usage

To export your database to S3:

    drush database-export

To download and import your database from S3:

    drush database-import

Further usage instructions (including optional flags) can be found by running `drush help database-export` or `drush help database-import`.
## Prefixing

If you want more flexibility in where your backups are stored, you can set the folder and file prefix in the configuration management page. For example, if you want to store your backups in `s3://YOUR_BUCKET/database-export-for-YYYY-MM-DD/version-YYYY-MM-DD.sql.gz`, you would set the S3 Folder Prefix to `database-export-for-` and the S3 File Prefix to `version-`.
