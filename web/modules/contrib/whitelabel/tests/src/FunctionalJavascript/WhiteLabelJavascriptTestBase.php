<?php

namespace Drupal\Tests\whitelabel\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\whitelabel\Traits\WhiteLabelCreationTrait;

/**
 * The Javascript base class for white label functional tests.
 *
 * This is the same as WhiteLabelTestBase, but extending JavascriptTestBase
 * instead.
 *
 * @package Drupal\Tests\whitelabel\FunctionalJavascript
 */
abstract class WhiteLabelJavascriptTestBase extends JavascriptTestBase {

  use WhiteLabelCreationTrait {
    createWhiteLabel as drupalCreateWhiteLabel;
  }

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'whitelabel',
    'user',
    'image',
  ];

  /**
   * Holds the white label owner.
   *
   * @var \Drupal\user\Entity\User
   */
  public $whiteLabelOwner;

  /**
   * Holds the white label.
   *
   * @var \Drupal\whitelabel\WhiteLabelInterface
   */
  public $whiteLabel;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->whiteLabelOwner = $this->drupalCreateUser(['serve white label pages']);
    $this->drupalLogin($this->whiteLabelOwner);

    $image_files = $this->drupalGetTestFiles('image');

    $this->whiteLabel = $this->drupalCreateWhiteLabel([
      'token' => $this->randomMachineName(),
      'uid' => $this->whiteLabelOwner->id(),
      'name' => $this->randomString(),
      'name_display' => TRUE,
      'slogan' => $this->randomString(),
      'logo' => File::create((array) current($image_files)),
    ]);
  }

  /**
   * Attaches a white label field to a given entity and bundle.
   *
   * @param string $entity
   *   The entity type to add the field to.
   * @param string $bundle
   *   The bundle to add the field to.
   * @param string $field_name
   *   The system name of the field.
   * @param string $field_label
   *   The label of the field.
   */
  public function attachFieldToEntity($entity, $bundle, $field_name = 'field_whitelabel', $field_label = 'White label') {
    // Create a white label field and attach it to a user.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity,
      'type' => 'entity_reference_revisions',
      'cardinality' => 1,
      'settings' => [
        'target_type' => 'whitelabel',
      ],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => $field_label,
    ]);
    $field->save();

    entity_get_form_display($entity, $bundle, 'default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_whitelabel',
        'settings' => [
          'color_scanner' => TRUE,
          'form_display_mode' => 'default',
        ],
      ])
      ->save();
  }

  /**
   * Function for determining if text is in the system branding block.
   *
   * @param string $text
   *   The text to look for.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   */
  public function inBrandingBlock($text) {
    $this->assertSession()->elementTextContains('css', '.block-system-branding-block', $text);
  }

  /**
   * Function for determining if text is not in the system branding block.
   *
   * @param string $text
   *   The text to look for.
   *
   * @throws \Behat\Mink\Exception\ElementTextException
   */
  public function notInBrandingBlock($text) {
    $this->assertSession()->elementTextNotContains('css', '.block-system-branding-block', $text);
  }

  /**
   * Function for determining if the given value is in the src attribute.
   *
   * @param string $src
   *   The value that should be present in the src attribute.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  public function inImagePath($src) {
    $this->assertSession()->elementAttributeContains('css', '.block-system-branding-block img', 'src', $src);
  }

  /**
   * Function for determining if the given value is not in the src attribute.
   *
   * @param string $src
   *   The value that should not be present in the src attribute.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   */
  public function notInImagePath($src) {
    $this->assertSession()->elementAttributeNotContains('css', '.block-system-branding-block img', 'src', $src);
  }

}
