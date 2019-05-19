-- SUMMARY --

This module integrates the payment method SOFORT Banking from SOFORT GmbH into
Drupal Commerce. The integration follows the API available from
https://www.sofort.com/integrationCenter-eng-DE/integration/API-SDK/#sue and has been
guided by the documentation https://www.sofort.com/integrationCenter-eng-DE/content/view/full/2513

-- REQUIREMENTS --

* Drupal 8
* Commerce 2
  - commerce_payment
* SofortLib-PHP Library (https://github.com/sofort/sofortlib-php)
* SOFORT Merchant account (https://sofort.com/register)

-- INSTALLATION --

* This module needs to be installed via Composer, which will download the required libraries.
composer require "drupal/commerce_sofortbanking"
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies

-- CONFIGURATION --

* Create a new SOFORT payment gateway.
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  SOFORT-specific settings available:
  - Configuration key (of your gateway project from your account at www.sofort.com)

-- SUPPORT --

Support for this module is provided in the issue queue at https://drupal.org/project/issues/commerce_sofortbanking

-- CONTACT --

Maintainer:
* Jürgen Haas (jurgenhaas) - https://www.drupal.org/u/jurgenhaas
* Andreas Mayr (agoradesign) - https://www.drupal.org/u/agoradesign

This project has been sponsored by:
* PARAGON Executive Services GmbH
  Providing IT services as individual as the requirements.
  Find out more from https://www.paragon-es.de
* agoradesign KG
  Find out more from https://www.agoradesign.at
