[//]: # ( clear&&curl -s -F input_files[]=@README.md -F from=markdown -F to=html http://c.docverter.com/convert|tail -n+11|head -n-2 )

# AWS CloudFront Purger

You can use AWS CloudFront as a reverse proxy in front of your whole Drupal site.

This module provides a **very simple** AWS CloudFront [Purge][1] **Purger** plugin.

## Required Modules

Because AWS CloudFront does not support cache tags (yet), this module depends on [URLs queuer][2] module

## Installation

This module requires you to have the AWS SDK loaded in your classpath.

The recommended approach is to have a composer file in your project root, and run:

```
composer require aws/aws-sdk-php:~3.0
```

## Configuration

You need to specify the Distribution ID in your settings.php.

```
$config['cloudfront_purger.settings']['distribution_id'] = 'ABCD1234';
```
Alternatively, you can use drush to set it, then export as part of your site configuration.

```
drush config-set cloudfront_purger.settings distribution_id ABCD1234
```

### Overriding AWS CloudFront region.

You can override the CloudFront client region by adding the following to your sites services.yml file:

```
parameters:
  cloudfront.cloudfront_client.options:
    region: us-east-1
    version: latest
```

## AWS Authentication

You can set the AWS key and secret in `settings.php`:

```
$config['cloudfront_purger.settings']['aws_key'] = 'ABCD1234';
$config['cloudfront_purger.settings']['aws_secret'] = 'ABCD1234';
```

If you do not explicitly set AWS key and secret in config, it will fall
back to:

- IAM Roles
- Exporting credentials using environment variables
- Using a profile in a ~/.aws/credentials file

See the AWS SDK Guide on [Credentials][3]

You will need to allow the `cloudfront:CreateInvalidation` action in your IAM policy.

[1]: https://www.drupal.org/project/purge
[2]: https://www.drupal.org/project/purge_queuer_url
[3]: http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html
