CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

"Elasticsearch AWS Connector" facilitates integration between Elasticsearch Connector-module
and Amazon Web Services using Signed AWS Requests.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/elasticsearch_aws_connector

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/elasticsearch_aws_connector


REQUIREMENTS
------------

This module requires the following modules:

 * Elasticsearch Connector (https://www.drupal.org/project/elasticsearch_connector)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

When you select "Use authentication" on the form of the cluster created using "Elasticsearch Connector" module,
you will have to option to select "Amazon Web Services - signed requests" as "Authentication type".
When "Amazon Web Services - signed requests" is selected, a field to store the "Amazon Web Services - region" will
be available in order to define the correct region of the AWS-stack where the Elasticsearch is located.
A few possible options to enter in the region field:
 * eu-west-1
 * us-west-2
Also a field to enter your preferred "AWS authentication type" will become available, you can select either:
 * AWS Credentials
 * AWS IAM Role
In case you choose "AWS Credentials" you will also be asked to enter a key and a secret.


MAINTAINERS
-----------

Current maintainers:
 * Jochen Verdeyen (jover) - https://drupal.org/user/310720
