# Amazon Web Services (AWS) Connector

This Drupal 8.x Module utilizes the AWS PHP SDK [CredentialProvider](http://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Credentials.CredentialProvider.html) to provide authentication methodology for other Drupal modules to connect to and utilize AWS services. 

This module provides a simple form for managing your credentials within Drupal itself. These credentials are then stored as part of Drupal configuration and are made accessible to the CredentialProvider class.

To use this module, include it in your own PHP code (e.g. use Drupal\aws_connector\Credentials\AWSCredentialProvider;). 

# Requirements

This module requires the AWS PHP SDK to be loaded into your environment. This can be handled via composer and the included composer.json file.

# Examples
```php
<?php
namespace <your namespace>

use Drupal\aws_connector\Credentials\AWSCredentialProvider;
use Aws\IotDataPlane\IotDataPlaneClient;
use Aws\Iot\IotClient;
/**
 * AWS IoT class.
 */
class AWSIoT implements SenderInterface {
   /**
   * IoT client object.
   *
   * @var \Aws\IotDataPlane\IotDataPlaneClient
   */
  private $iotClient;

  /**
   * Construct.
   */
  public function __construct($topic = '') {
    $credentials = new AWSCredentialProvider();
    $a = AWSCredentialProvider::ini(NULL, NULL);
    $composed = AWSCredentialProvider::chain($a);

    $promise = $composed();
    $this->awsCredentials = $promise->wait();

    $this->iotClient = new IotClient([
      'credentials' => $this->awsCredentials,
      'region' => $credentials->getRegion(),
      'version' => "2015-05-28",
    ]);
  }
}
```