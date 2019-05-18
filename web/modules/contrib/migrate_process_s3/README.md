# Migrate Process S3

When integrating with a 3rd party API, you may have file uploads stored on Amazon S3. While you could link to files in the bucket, this can pose several challenges:

* The bucket may not be public and cannot be made public
* The bucket content must be mirrored locally for legal or privacy reasons
* You want to leverage Drupal-side file processing (like image styles)
* You do not want to use s3fs or a whole S3 file system replacement
* Many other reasons.

This module provides a process plugin to download objects from an S3 bucket in a Drupal migration:

```yaml
  field_file_or_image:
    plugin: s3_download
    source: s3_path
    access_key: MY_ACCESS_KEY
    secret_key: MY_SECRET_KEY
    bucket: my_bucket_name
    dest_dir: path/in/public/files
```

## s3_download in file migrations

The plugin works much like `file_copy`, and can be used in a typical file migration:

```yaml
langcode: en
status: true
dependencies: {  }
id: my_s3_download_migration
label: 'My S3 Download Migration'
source:
  plugin: some_source_plugin
process:
  _s3_path:
    -
      plugin: skip_on_empty
      source: my_s3_object_path
      method: row
  uri:
    -
      plugin: s3_download
      source: '@_s3_path'
      access_key: MY_ACCESS_KEY
      secret_key: MY_SECRET_KEY
      bucket: my_bucket_name
      dest_dir: path/in/public/files
  filename:
    -
      plugin: callback
      source: '@_s3_path'
      callable: basename
  status:
    -
      plugin: default_value
      default_value: 1
  uid:
    -
      plugin: default_value
      default_value: 0
destination:
  plugin: 'entity:file'
migration_dependencies: {}
```

## Credentials

Bucket credentials can be specified as part of the process plugin configuration:

```yaml
  field_file_or_image:
    plugin: s3_download
    source: s3_path
    access_key: MY_ACCESS_KEY
    secret_key: MY_SECRET_KEY
    bucket: my_bucket_name
    dest_dir: path/in/public/files
```

If omitted, they will be loaded from environment variables. See how this works in the [AWS SDK for PHP docs](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html).

If you have multiple profiles in your `~/.aws/credentials` file, you can specify the profile name in the process plugin config:

```yaml
  field_file_or_image:
    plugin: s3_download
    source: s3_path
    profile: my_profile_name
    bucket: my_bucket_name
    dest_dir: path/in/public/files
```

## Bucket regions

You can specify your bucket's region in the process plugin config:

```yaml
  field_file_or_image:
    plugin: s3_download
    source: s3_path
    access_key: MY_ACCESS_KEY
    secret_key: MY_SECRET_KEY
    bucket: my_bucket_name
    region: us-east-2
    dest_dir: path/in/public/files
```
