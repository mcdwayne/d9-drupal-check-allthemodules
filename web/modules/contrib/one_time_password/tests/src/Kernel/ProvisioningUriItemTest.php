<?php

namespace Drupal\Tests\one_time_password\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\one_time_password\Exception\MissingProvisioningUriException;
use OTPHP\TOTP;

/**
 * Test the provisioning URI item field.
 *
 * @group one_time_password
 */
class ProvisioningUriItemTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'one_time_password',
    'entity_test',
    'user',
    'system',
    'field',
  ];

  /**
   * A test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('entity_test');

    FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'one_time_password_provisioning_uri',
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();

    $this->entity = EntityTest::create([
      'name' => 'Entity Label',
    ]);
  }

  /**
   * Test regenerating a one time password provisioning URI.
   */
  public function testRegenerateOneTimePassword() {
    // The field can be empty when no URI is stored.
    $this->assertTrue($this->entity->test_field->isEmpty());
    // Regenerating a one time password URI should populate the field.
    $this->entity->test_field->regenerateOneTimePassword();
    $this->assertInstanceOf(TOTP::class, $this->entity->test_field->getOneTimePassword());
    $this->assertTrue(strpos($this->entity->test_field->uri, 'otpauth://totp/Entity%20Label?secret=') === 0);
  }

  /**
   * Test exception from empty field item list.
   */
  public function testEmptyFieldItemListPasswordException() {
    $this->setExpectedException(MissingProvisioningUriException::class, 'Cannot get password, provisioning field is empty.');
    $this->entity->test_field = [];
    $this->entity->test_field->getOneTimePassword();
  }

  /**
   * Test exception from empty uri on field item.
   */
  public function testEmptyFieldItemUriPasswordException() {
    $this->setExpectedException(MissingProvisioningUriException::class, 'Cannot get password, uri property on provisioning field is empty.');
    $this->entity->test_field = [
      'uri' => '',
    ];
    $this->entity->test_field->getOneTimePassword();
  }

}
