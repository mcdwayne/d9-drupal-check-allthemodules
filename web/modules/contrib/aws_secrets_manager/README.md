# AWS Secrets Manager

| Branch | Build Status |
| ------ | ------------ |
| [8.x-1.x](https://www.drupal.org/project/aws_secrets_manager/releases/8.x-1.x-dev) | [![CircleCI](https://img.shields.io/circleci/project/github/nicksantamaria/drupal-aws_secrets_manager/8.x-1.x.svg?style=for-the-badge)](https://circleci.com/gh/nicksantamaria/drupal-aws_secrets_manager/tree/8.x-1.x) |

This Drupal module adds a new key provider for the [Key module](https://www.drupal.org/project/key) - it allows you to encrypt data using [AWS Secrets Manager](https://aws.amazon.com/secrets-manager/).

## Get Started
This guide assumes you have an AWS account and working knowledge of AWS Secrets Manager and IAM, and the following resources provisioned in AWS.

* One or more secrets
* An IAM user with privileges to access the relevant secrets

Ensure this module and its dependencies are available in your codebase.

- https://drupal.org/project/key
- https://github.com/aws/aws-sdk-php

Enable the **AWS Secrets Manager** module.

@todo document the rest

### AWS Credentials

There are alternatives to configuring the AWS credentials in the admin form.

**settings.php**

```
$config['aws_secrets_manager.settings']['aws_key'] = 'foo';
$config['aws_secrets_manager.settings']['aws_secret'] = 'bar';
```

If you do not explicitly set AWS key and secret in config, it will fall back to:

* IAM Instance Profile
* Exported credentials in environment variables
* The default profile in a `~/.aws/credentials` file

See the AWS SDK Guide on [Credentials](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html).

## Contribute

Development of this module takes place on [GitHub](https://github.com/nicksantamaria/drupal-aws_secrets_manager).

* If you encounter issues, please [search the backlog](https://github.com/nicksantamaria/drupal-aws_secrets_manager/issues).
* Please [create issues](https://github.com/nicksantamaria/drupal-aws_secrets_manager/issues/new?labels=bug) and [feature requests](https://github.com/nicksantamaria/drupal-aws_secrets_manager/issues/new?labels=enhancement) in GitHub.
* Even better, feel free to fork this repo and [make pull requests](https://github.com/nicksantamaria/drupal-aws_secrets_manager/compare).
