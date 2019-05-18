<?php

namespace Drupal\entity_gallery\Tests;
use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the persistence of basic options through multiple steps.
 *
 * @group entity_gallery
 */
class MultiStepEntityGalleryFormBasicOptionsTest extends EntityGalleryTestBase {

  /**
   * The field name to create.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Tests changing the default values of basic options to ensure they persist.
   */
  function testMultiStepEntityGalleryFormBasicOptions() {
    // Prepare a user to create the entity gallery.
    $web_user = $this->drupalCreateUser(array('administer entity galleries', 'create page entity galleries'));
    $this->drupalLogin($web_user);

    // Create an unlimited cardinality field.
    $this->fieldName = Unicode::strtolower($this->randomMachineName());
    FieldStorageConfig::create(array(
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_gallery',
      'type' => 'text',
      'cardinality' => -1,
    ))->save();

    // Attach an instance of the field to the page content type.
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_gallery',
      'bundle' => 'page',
      'label' => $this->randomMachineName() . '_label',
    ])->save();
    entity_get_form_display('entity_gallery', 'page', 'default')
      ->setComponent($this->fieldName, array(
        'type' => 'text_textfield',
      ))
      ->save();

    $edit = array(
      'title[0][value]' => 'a',
      "{$this->fieldName}[0][value]" => $this->randomString(32),
    );
    $this->drupalPostForm('gallery/add/page', $edit, t('Add another item'));
  }

}
