# OSS File System

## INTRODUCTION

  * OSS File System (ossfs) provides an additional file system to your drupal
    site, alongside the public and private file systems, which stores files in
    Aliyun Object Storage Service (OSS).
    You can set your site to use OSS File System as the default, or use it only
    for individual fields. This functionality is designed for sites which are
    load-balanced across multiple servers, as the mechanism used by Drupal's
    default file systems is not viable under such a configuration.

## REQUIREMENTS

  * Aliyun OSS PHP SDK
  * "https://patch-diff.githubusercontent.com/raw/aliyun/aliyun-oss-php-sdk/pull/65.patch" patch
  * "https://www.drupal.org/files/issues/imagetoolkit_plugins-2826482-2.patch" patch
  * "allow_url_fopen = On" in your php.ini file

## INSTALLATION

  * Execute `composer require drupal/ossfs:1.x-dev -v` and apply above patch to OSS SDK.
  * Enable the ossfs module.
  * Configure OSS File System settings at "/admin/config/media/ossfs".
    In the "Style names mapping" section, fill the corresponding OSS style for the Drupal style.
    See [OSS IMG](https://help.aliyun.com/document_detail/44686.html) for creating styles.
  * Keep in mind that any time the contents of your OSS bucket change without Drupal
    knowing about it (like if you copy some files into it manually using another tool),
    you'll need to sync the metadata into local storage. Ossfs assumes that its local
    storage is a canonical listing of every file in the bucket. Thus, Drupal will not
    be able to access any files you uploaded into your bucket manually until the local
    storage learns of them.

## CONFIGURATION

  * Visit the "admin/config/media/file-system" page and set the "Default download
    method" to "Aliyun Object Storage Service."
  * Add or edit a field of type File, Image, etc, and set the "Upload destination" to 
    "Aliyun Object Storage Service." in the "Field Settings" tab.
  * Visit the "/admin/config/content/formats" page, edit 'Basic HTML' and 'Full HTML'
    formats, and set the "File storage" to 'Aliyun Object Storage Service.' for inline images.
    If you enabled the 'Apply OSS style to images' filter, ensure it under the 
    "Track images uploaded via a Text Editor" filter.
    
## SYNC OSS METADATA TO LOCAL STORAGE

  * Use the drush command `drush ossfs:sync-metadata` to do this, it will copy the
    metadata for every existing file in your OSS bucket into the local storage (database) 

## UPLOAD LOCAL PUBLIC FILES TO OSS

  * Use the drush command `drush ossfs:upload-public` to do this, it will upload
    all the files into the correct sub directory in your bucket according to your
    ossfs configuration, and will write them to the local storage (database).

  * You shouldn't upload images during the process and when the process finished,
    replace with a simple query all oss:// by public:// if the files are public:
    ```sql
    UPDATE file_managed SET uri=REPLACE(uri, 'public://', 'oss://') WHERE uri LIKE 'public://%';
    ```

## ACKNOWLEDGEMENT

  * OSS File System started as a fork of the drupal/s3fs module.
