# Flysystem AliyunOSS

Provides an [Aliyun OSS](https://www.alibabacloud.com/product/oss) plugin for [Flysystem](https://www.drupal.org/project/flysystem)

## Dependencies

`composer require aliyuncs/oss-sdk-php:2.3.0 -vvv`

## CONFIGURATION

Example configuration:

```php

$schemes = [
  'oss' => [
    'driver' => 'aliyun_oss',
    'name' => 'Aliyun OSS',
    'description' => 'An Aliyun OSS plugin for Flysystem',
    'cache' => FALSE,
    'config' => [
      'access_key_id' => 'ACCESS_KEY_ID',
      'access_key_secret' => 'ACCESS_KEY_SECRET',
      'endpoint' => 'oss-cn-shanghai.aliyuncs.com',
      'bucket' => 'BUCKET_NAME',
      'cname' => 'cdn.example.com',
      'visibility' => 'private',
      'use_https' => TRUE,
      'expire' => 3600,
      'timeout' => 3600,
      'connect_timeout' => 60,
    ],
  ],
];

$settings['flysystem'] = $schemes;

```
