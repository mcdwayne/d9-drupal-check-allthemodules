INTRODUCTION
============
Cloud is a set of modules to realize Cloud management: Drupal-based Cloud
dashboard such as Amazon Management Console, RightScale, ElasticFox, and etc.
The module aims to support not only public Cloud like AWS EC2 but also private
Cloud like OpenStack since the system is highly modularized by Drupal
architecture.

REQUIREMENTS
============
- `PHP 7.2` or Higher (`128 MB` Memory or higher)


- `MySQL 5.5` or higher _OR_
- `MariaDB 10.1` or higher


- `Drupal 8.x` (Latest version of Drupal 8)
- `Cloud 8.x-1.x`
  - This branch is still under development. Any volunteer effort is greatly
    appreciated.
  - Currently, `aws_cloud` is the only `Cloud` implementation that is working.
  - Future support includes `GCP`, `Azure`, `OpenStack` and `Kubernetes`.

Limitations
===========
- The aws_cloud module does **not** support *Classic Ec2 instances*
  (`Non-VPC`).

  **Note:** Classic instances (`Non-VPC`) are available for AWS accounts
    created before *2013-12-03*.
  `aws_cloud` module is only tested for `EC2-VPC` instances.

  See also:
  - [Default VPC and Default Subnets](
      https://docs.aws.amazon.com/vpc/latest/userguide/default-vpc.html
    )
  - [Discussion Forums: Launch a NON-VPC Ec2 instance?](
      https://forums.aws.amazon.com/thread.jspa?threadID=182773
    )


INSTALLATION
=============
1. Download `aws-sdk` from:
     https://docs.aws.amazon.com/aws-sdk-php/v3/download/aws.zip
   and unzip it into the `vendor` directory.
2. Download `cloud` module.
3. Enable the `aws_cloud module`.  This will also enable the required modules.

   _OR_ (using `composer`)

- `composer require drupal/cloud`


CONFIGURATION
=============

Basic Setup
-----------
1. Create a new `Cloud Config` based on your needs.  Go to `Structure` > `Cloud
   config list` and `+ Add Cloud config`
2. Enter all required configuration parameters.  The system will automatically
   setup all regions from your AWS account.  There are three options for
   specifying AWS credentials:

   a. Instance credentials - If cloud module is running on an EC2 instance and
   the EC2 instance has an IAM Role attached, you have the option to check "Use
   Instance Credentials".  Doing so is secure and does not require `access id`
   and `secret access key` to be entered into Drupal.
   Please refer to this AWS tutorial about IAM role and EC2 Instance:

   https://aws.amazon.com/blogs/security/easily-replace-or-attach-an-iam-role-to-an-existing-ec2-instance-by-using-the-ec2-console/

   b. Simple access - Specify `access id` and `secret access key` to access a
      particular account's EC2 instances.

   c. Assume role - Specify `access id`, `secret access key` and the
      `Assume Role` section.  With this combination, the cloud module can
      assume the role of another AWS account and access their EC2 instances.
      To learn more about setting up assume role setup, please read this AWS
      tutorial:

      https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_use_permissions-to-switch.html

3. Run cron to update your specific Cloud region.
4. Use the links under `Cloud Service Providers` > `[CLOUD CONFIG]` to manage
   your AWS EC2 entities.
5. Import Images using the tab:
   `Cloud Service Providers` > `[CLOUD CONFIG]` | `Images`
   - Click on `+ Import AWS Cloud Image`
   - Search for images by AMI name.  For example, to import `Anaconda` images
   based on Ubuntu, type in `anaconda*ubuntu*`.
   Use the AWS Console on `aws.amazon.com` to search for images to import
6. `Import` or `Add a Keypair`.  The keypair is used to log into any system you
   launch.  Use the links under the tab:
   `Cloud Service Providers` > `[CLOUD CONFIG]` | `Key Pair`
   - Use the `+ Import AWS Cloud Key Pair` button to import an existing key
     pair.  You will be uploading your public key.
   - Use `+ Add AWS Cloud Key Pair` to have AWS generate a new private key.
     You will be prompted to download the key after it is created.
7. Setup `Security groups`, `Network Interfaces` as needed from AWS Management
   Console.

Launching Instance
------------------
1. Create a server template under
   `Design` > `Cloud Server Template` > `[CLOUD CONFIG]`
2. Once template is created, click the `Launch` tab to launch it.

Permissions
===========
Configure permissions per your requirements

Directory Structure
===================
```
cloud (Cloud is a core module for Cloud package)
└── modules
    ├── cloud_server_template (Merged into cloud module, removed in the future)
    └── cloud_service_providers
        └── aws_cloud
```

Maintainers
===========

- `yas` (https://drupal.org/u/yas)
- `baldwinlouie` (https://www.drupal.org/u/baldwinlouie)
- `xiaohua-guan` (https://www.drupal.org/u/xiaohua-guan)
- `Masami` (https://www.drupal.org/u/Masami)
- `shidat` (https://www.drupal.org/u/shidat)
