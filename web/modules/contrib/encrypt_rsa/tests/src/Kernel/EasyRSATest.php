<?php

namespace Drupal\Tests\encrypt_rsa\Kernel;

use Drupal\encrypt_rsa\Plugin\EncryptionMethod\PrivateOpenSslSealEncryptionMethod;
use Drupal\encrypt_rsa\Plugin\EncryptionMethod\PublicOpenSslSealEncryptionMethod;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests EasyRSA encryption method.
 *
 * @group encrypt_rsa
 */
class EasyRSATest extends KernelTestBase {

  /**
   * Plain text message string.
   */
  const PLAIN_TEXT_MESSAGE = 'Hello World!';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'encrypt',
    'encrypt_rsa',
    'encrypt_rsa_easyrsa_test',
  ];

  protected function setUp() {
    parent::setUp();

    $this->installConfig('encrypt_rsa_easyrsa_test');
  }

  /**
   * Test public profile/method/key.
   */
  public function testPublicEncryptDecrypt() {

    /** @var \Drupal\encrypt\EncryptionProfileInterface $profile */
    $profile = $this->container->get('entity_type.manager')
      ->getStorage('encryption_profile')
      ->load('easyrsa_public');

    $text = $profile->getEncryptionMethod()->encrypt(static::PLAIN_TEXT_MESSAGE, $profile->getEncryptionKey()->getKeyValue());

    // Check text is encrypted.
    $this->assertNotEquals(static::PLAIN_TEXT_MESSAGE, $text);

    // Check decrypting returns encrypted text.
    $this->assertEquals($text, $profile->getEncryptionMethod()->decrypt($text, $profile->getEncryptionKey()->getKeyValue()));

    // Manually encrypt and confirm all is good.
    $profile = $this->container->get('entity_type.manager')
      ->getStorage('encryption_profile')
      ->load('easyrsa_private');

    $this->assertEquals(static::PLAIN_TEXT_MESSAGE, $profile->getEncryptionMethod()->decrypt($text, $profile->getEncryptionKey()->getKeyValue()));

  }

  /**
   * Test private profile/method/key.
   */
  public function testPrivateEncryptDecrypt() {

    /** @var \Drupal\encrypt\EncryptionProfileInterface $profile */
    $profile = $this->container->get('entity_type.manager')
      ->getStorage('encryption_profile')
      ->load('easyrsa_private');

    $text = $profile->getEncryptionMethod()->encrypt(static::PLAIN_TEXT_MESSAGE, $profile->getEncryptionKey()->getKeyValue());

    // Check text is encrypted.
    $this->assertNotEquals(static::PLAIN_TEXT_MESSAGE, $text);

    // Check decrypting returns original message.
    $this->assertEquals(static::PLAIN_TEXT_MESSAGE, $profile->getEncryptionMethod()->decrypt($text, $profile->getEncryptionKey()->getKeyValue()));

  }

}
