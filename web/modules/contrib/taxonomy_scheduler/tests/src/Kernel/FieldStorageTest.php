<?php

namespace Drupal\Tests\taxonomy_scheduler\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem;

/**
 * Class FieldStorageTest.
 */
class FieldStorageTest extends KernelTestBase {

  /**
   * Modules.
   *
   * @var array
   */
  public static $modules = [
    'hook_event_dispatcher',
    'automated_cron',
    'taxonomy_scheduler',
    'field',
    'taxonomy',
    'text',
    'datetime',
  ];

  /**
   * FieldManager.
   *
   * @var \Drupal\taxonomy_scheduler\Service\TaxonomySchedulerFieldManager
   */
  protected $fieldManager;

  /**
   * TaxonomyFieldStorageItem.
   *
   * @var \Drupal\taxonomy_scheduler\ValueObject\TaxonomyFieldStorageItem
   */
  protected $taxonomyFieldStorageItem;

  /**
   * SetUp.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->fieldManager = $this->container->get('taxonomy_scheduler.field_manager');

    $vocabulary = Vocabulary::create([
      'name' => 'Test',
      'vid' => 'test',
    ]);
    $vocabulary->save();
    $this->taxonomyFieldStorageItem = new TaxonomyFieldStorageItem([
      'vocabularies' => ['test'],
      'fieldName' => 'field_test',
      'fieldLabel' => 'Test',
      'fieldRequired' => 1,
    ]);
  }

  /**
   * TestAddField.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAddField(): void {
    $this->fieldManager->addField($this->taxonomyFieldStorageItem);

    $term = Term::create([
      'name' => 'testterm',
      'vid' => 'test',
    ]);

    $term->set('field_test', '2019-03-06T10:41:00');
    $term->save();
    $term = Term::load($term->id());
    self::assertEquals(TRUE, $term->hasField('field_test'));
    self::assertEquals('2019-03-06T10:41:00', $term->get('field_test')->getString());
  }

  /**
   * TestDisableField.
   */
  public function testDisableField(): void {
    $this->fieldManager->addField($this->taxonomyFieldStorageItem);
    $this->fieldManager->enableField($this->taxonomyFieldStorageItem);
    $this->fieldManager->disableField($this->taxonomyFieldStorageItem);
    $formDisplay = EntityFormDisplay::load('taxonomy_term.test.default');
    self::assertNull($formDisplay->getComponent(
      $this->taxonomyFieldStorageItem->getFieldName()
    ));
  }

  /**
   * TestEnableField.
   */
  public function testEnableField(): void {
    $this->fieldManager->addField($this->taxonomyFieldStorageItem);
    $this->fieldManager->enableField($this->taxonomyFieldStorageItem);
    $formDisplay = EntityFormDisplay::load('taxonomy_term.test.default');
    self::assertNotNull($formDisplay->getComponent(
      $this->taxonomyFieldStorageItem->getFieldName()
    ));
  }

}
