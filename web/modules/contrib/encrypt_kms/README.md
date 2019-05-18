# Encrypt KMS

| Branch | Build Status |
| ------ | ------------ |
| [8.x-1.x](https://www.drupal.org/project/encrypt_kms/releases/8.x-1.x-dev) | [![CircleCI](https://img.shields.io/circleci/project/github/nicksantamaria/drupal-encrypt_kms/8.x-1.x.svg?style=for-the-badge)](https://circleci.com/gh/nicksantamaria/drupal-encrypt_kms/tree/8.x-1.x) |

This Drupal module adds a new encryption method for the [Encrypt framework](https://www.drupal.org/project/encrypt) - it allows you to encrypt data using [AWS KMS](https://aws.amazon.com/kms/).

## Get Started
This guide assumes you have an AWS account and working knowledge of KMS, and the following resources provisioned in AWS.

* A KMS key
* An IAM user with privileges to encrypt and decrypt using aforementioned key

Ensure this module and its dependencies are available in your codebase.

- https://drupal.org/project/key
- https://drupal.org/project/encrypt
- https://github.com/aws/aws-sdk-php

Enable the **Encrypt KMS** module.

Ensure your user account has the **administer encrypt** permission.

Add a new Key - select the **KMS Key** type and enter the ARN of the KMS key. This is just an identifier, and is completely fine to store in the "Configuration" storage provider.

Add a new **Encryption Profile** - choose the **Amazon KMS** encryption method and the key you just created.

Go to the **Encrypt KMS** configuration form and add your AWS IAM user credentials.

> PROTIP: Use the Key module's configuration override capability to securely store the AWS credentials.

Great, you are now set up and can use KMS to encrypt [fields](https://www.drupal.org/project/field_encrypt), [webform submissions](https://www.drupal.org/project/webform_encrypt) and lots more.

### AWS Credentials

There are alternatives to configuring the AWS credentials in the admin form.

**settings.php**

```
$config['encrypt_kms.settings']['aws_key'] = 'foo';
$config['encrypt_kms.settings']['aws_secret'] = 'bar';
```

If you do not explicitly set AWS key and secret in config, it will fall back to:

* IAM Instance Profile
* Exported credentials in environment variables
* The default profile in a `~/.aws/credentials` file

See the AWS SDK Guide on [Credentials](http://docs.aws.amazon.com/aws-sdk-php/v3/guide/guide/credentials.html).

## Contribute

Development of this module takes place on [GitHub](https://github.com/nicksantamaria/drupal-encrypt_kms).

* If you encounter issues, please [search the backlog](https://github.com/nicksantamaria/drupal-encrypt_kms/issues).
* Please [create issues](https://github.com/nicksantamaria/drupal-encrypt_kms/issues/new?labels=bug) and [feature requests](https://github.com/nicksantamaria/drupal-encrypt_kms/issues/new?labels=enhancement) in GitHub.
* Even better, feel free to fork this repo and [make pull requests](https://github.com/nicksantamaria/drupal-encrypt_kms/compare).

