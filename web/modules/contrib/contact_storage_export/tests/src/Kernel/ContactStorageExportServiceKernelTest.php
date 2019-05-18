<?php

namespace Drupal\Tests\contact_storage_export\Kernel;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests contact storage export service methods.
 *
 * @group contact_storage
 */
class ContactStorageExportServiceKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'link',
    'entity_reference',
    'datetime',
    'contact_storage',
    'contact_storage_export',
    'csv_serialization',
    'contact',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('contact_message');
    $this->installEntitySchema('user');
    $this->installConfig(['field', 'system']);
  }

  /**
   * Tests contact storage export.
   */
  public function testContactStorageExport() {
    // Create a sample form.
    $contact_form_id = 'contact_storage_export_form';
    $contact_form = ContactForm::create(['id' => $contact_form_id]);
    $contact_form->save();

    // Add sample link, entity reference and datetime fields.
    $this->addField('field_link', 'link', $contact_form_id, ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED]);
    $entity_reference_storage = [
      'entity_types' => [],
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'user',
      ],
    ];
    $entity_reference_instance = [
      'settings' => [
        'handler' => 'default:user',
        'handler_settings' => [
          'include_anonymous' => TRUE,
        ],
      ],
    ];
    $this->addField('field_entity_reference', 'entity_reference', $contact_form_id, $entity_reference_storage, $entity_reference_instance);
    $this->addField('field_datetime', 'datetime', $contact_form_id);

    // Create a sample message.
    $message = Message::create([
      'id' => 1,
      'contact_form' => $contact_form->id(),
      'name' => 'example',
      'mail' => 'admin@example.com',
      'field_link' => [
        [
          'uri' => 'http://example.com',
        ],
        [
          'uri' => 'http://drupal.org',
        ],
      ],
      'created' => '1487321550',
      'field_entity_reference' => [
        'target_id' => User::getAnonymousUser()->id(),
      ],
      'field_datetime' => '2018-02-03',
      'ip_address' => '127.0.0.1',
    ]);
    $message->save();

    // Assert full CSV output with date format provided.
    $csv_string = \Drupal::service('contact_storage_export.exporter')->encode([$message], ['date_format' => 'html_date']);
    $headers = '"Message ID",Language,"Form ID","The sender\'s name","The sender\'s email",Subject,Message,Copy,"Recipient ID",Created,"User ID","IP address",field_link,field_entity_reference,field_datetime';
    $values = '1,English,contact_storage_export_form,example,admin@example.com,,,,,2017-02-17,0,127.0.0.1,http://example.com|http://drupal.org,0,';
    $expected = $headers . PHP_EOL . $values;
    $this->assertEquals($expected, $csv_string);

    // Assert full CSV output without date format provided.
    $csv_string = \Drupal::service('contact_storage_export.exporter')->encode([$message]);
    $values = '1,English,contact_storage_export_form,example,admin@example.com,,,,,"02/17/2017 - 19:52",0,127.0.0.1,http://example.com|http://drupal.org,0,';
    $expected = $headers . PHP_EOL . $values;
    $this->assertEquals($expected, $csv_string);

    // Assert CSV output with selected columns.
    $columns = [
      'name' => 'name',
      'mail' => 'mail',
      'field_link' => 'field_link',
    ];
    $csv_string = \Drupal::service('contact_storage_export.exporter')->encode([$message], ['columns' => $columns]);
    $headers = '"The sender\'s name","The sender\'s email",field_link';
    $values = 'example,admin@example.com,http://example.com|http://drupal.org';
    $expected = $headers . PHP_EOL . $values;
    $this->assertEquals($expected, $csv_string);
  }

  /**
   * A helper function that adds a field to the contact message entity.
   *
   * @param string $name
   *   A field name.
   * @param string $type
   *   A field type.
   * @param string $bundle
   *   A bundle.
   * @param array $storage_configuration
   *   (optional) Field storage configuration.
   * @param array $instance_configuration
   *   (optional) Field instance configuration.
   */
  protected function addField($name, $type, $bundle, array $storage_configuration = [], array $instance_configuration = []) {
    $field_storage = FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'contact_message',
      'type' => $type,
    ] + $storage_configuration);
    $field_storage->save();
    $field_instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $name,
    ] + $instance_configuration);
    $field_instance->save();
  }

}
