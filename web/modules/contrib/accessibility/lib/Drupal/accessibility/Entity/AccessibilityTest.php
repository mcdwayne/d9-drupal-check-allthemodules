<?php

/**
 * @file
 * Definition of Drupal\accessibility\Entity\AccessibilityTest.
 */

namespace Drupal\accessibility\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;
use Drupal\accessibility\AccessibilityTestInterface;
use Drupal\Core\Field\FieldDefinition;

/**
 * Defines the accessibility test entity.
 *
 * @EntityType(
 *   id = "accessibility_test",
 *   label = @Translation("Accessibility test"),
 *   bundle_label = @Translation("Accessibility test"),
 *   module = "accessibility",
 *   controllers = {
 *     "storage" = "Drupal\accessibility\AccessibilityTestStorageController",
 *     "render" = "Drupal\accessibility\AccessibilityTestRenderController",
 *     "access" = "Drupal\accessibility\AccessibilityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\accessibility\Form\AccessibilityTestFormController",
 *       "delete" = "Drupal\accessibility\Form\AccessibilityTestDeleteForm"
 *     },
 *     "translation" = "Drupal\accessibility\TermTranslationController"
 *   },
 *   base_table = "accessibility_test",
 *   uri_callback = "accessibility_test_uri",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "test_id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *   },
 *   links = {
 *     "canonical" = "/accessibility-test/{accessibility_test}",
 *     "edit-form" = "/accessibility-test/{accessibility_test}/edit"
 *   },
 *   menu_base_path = "accessibility-test/%accessibility_test",
 *   route_base_path = "admin/config/accessibility/tests",
 *   permission_granularity = "entity"
 * )
 */
class AccessibilityTest extends ContentEntityBase implements AccessibilityTestInterface {

  /**
   * The test ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $test_id;

  /**
   * Name of the test.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $name;

  /**
   * The test quail name.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $quail_name;

  /**
   * The test severity.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $severity;

  /**
   * The test status.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $status;

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('test_id')->value;
  }

  /**
   * Overides \Drupal\Core\Entity\EntityNG::init().
   */
  protected function init() {
    parent::init();
    unset($this->test_id);
    unset($this->name);
    unset($this->quail_name);
    unset($this->severity);
    unset($this->status);
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageControllerInterface $storage_controller, $update = TRUE) {
    /*if (property_exists($test, 'is_new') && $test->is_new) {
      drupal_write_record('accessibility_test', $test);
    }
    else {
      db_update('accessibility_test')
               ->fields(array('name' => $test->name,
                              'severity' => $test->severity,
                              'created' => $test->created,
                              'changed' => $test->changed,
                              'quail_name' => $test->quail_name,
                              'data' => serialize($test->data)))
               ->condition('test_id', $test->test_id)
               ->execute();
    }
    cache_clear_all('accessibility_tests_json', 'cache');*/
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields['test_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Term ID'))
      ->setDescription(t('The test ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = FieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The test UUID.'))
      ->setReadOnly(TRUE);

    $fields['name'] = FieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The test name name.'));

    $fields['langcode'] = FieldDefinition::create('string')
      ->setLabel(t('Language code'))
      ->setDescription(t('The node language code.'));

    $fields['quail_name'] = FieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('Name of QUAIL test.'));

    $fields['severity'] = FieldDefinition::create('string')
      ->setLabel(t('Severity'))
      ->setDescription(t('Severity level of the test test.'));

    $fields['status'] = FieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Whether the test is active or not.'))
      ->setFieldSettings(array('default_value' => 0));

    return $fields;
  }

}

