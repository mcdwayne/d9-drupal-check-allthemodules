SUMMARY - Experian validation
=============================

Experian is a Third party service providing a way to validate the users address.
this module is providing the validation for Phone number & Email address through
experian service.


Installation:
-------------

Install this module as usual. Please see
http://drupal.org/documentation/install/modules-themes/modules-8

Configuration:
--------------

Go to admin/config/experian/settings and Endpoint & Token for email & phone.
This module supporting the below countries for validating phone number

United States
Australia
Canada
France
Ireland
Singapore
United Kingdom

Usage:
------

Go to Managed form display section & select the experian validation
for Email & Phone number field.

For Custom integration, Inject 'experian_validation.service' service
in your custom module:

use Drupal\experian_validation\Services\ExperianValidationService;

  public function __construct(ExperianValidationService $experianValidation) {
    $this->experianValidation = $experianValidation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('experian_validation.service')
    );
  }

For Email Address:
$this->experianValidation->validateEmail($emailAddress);

For Phone Number:
$this->experianValidation->validatePhone($phoneNumber, $countryCode);

Support:
--------
https://www.drupal.org/u/vedprakash
