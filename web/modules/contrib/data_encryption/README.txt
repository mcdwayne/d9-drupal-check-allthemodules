SUMMARY - Data Encryption
=========================

This is developer frindly module which will used for Encrption/Decryption
data based in the Key & Encryption profile.


Installation
-------------

Install this module as usual. Please see
http://drupal.org/documentation/install/modules-themes/modules-8


Usage
-----

Inject the below service in your module for custom usage:

  use Drupal\data_encryption\Services\DataEncryptionService;

  protected $encrypt;
  /**
   * {@inheritdoc}
   */
  public function __construct(DataEncryptionService $encrypt) {
    $this->encrypt = $encrypt;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('data_encryption.encryption')
    );
  }

// Encrypted Values
$this->encrypt->getEncryptedValue('TEXT_TO_BE_ENCRYPTED', 'ENCRYPTION_PROFILE');

// Decrypted Values
$this->encrypt->getDecryptedValue('TEXT_TO_BE_DECRYPTED', 'ENCRYPTION_PROFILE');

Dependencies
------------
Key
Encrypt
Real AES

SUPPORT
--------
Use the issue queue to report bugs or request support:
http://drupal.org/project/issues/data_encryption
https://www.drupal.org/u/vedprakash
