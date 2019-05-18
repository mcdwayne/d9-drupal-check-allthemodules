<?php

namespace Drupal\Tests\radioactivity\FunctionalJavascript;

/**
 * Testing the Count type radioactivity field.
 *
 * @see https://www.drupal.org/docs/8/phpunit/phpunit-javascript-testing-tutorial
 *
 * @group radioactivity
 */
class CountTest extends RadioactivityFunctionalJavascriptTestBase {

  /**
   * The name of the energy field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->fieldName = 'field_count_energy';
    $this->entityType = 'entity_test';
    $this->entityBundle = 'entity_test';
    $this->addCountEnergyField($this->fieldName);
    $this->createEnergyFormDisplay($this->fieldName);
    $this->createEmitterViewDisplay($this->fieldName, 1, 'raw');
  }

  /**
   * Tests Basic Radioactivity count functionality.
   */
  public function testCount() {
    $entity = $this->createContent();
    $this->assetIncidentCount(0);

    $this->drupalGet($entity->toUrl());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assetIncidentCount(1);
    $this->assertPageEnergyValue($this->fieldName, 0);
    $this->assertFieldEnergyValue($entity, $this->fieldName, 0);

    \Drupal::service('cron')->run();
    // The entity has updated values, reload it.
    /** @var \Drupal\node\Entity\Node $node */
    $entity = \Drupal::entityTypeManager()
      ->getStorage($this->entityType)
      ->load($entity->id());

    $this->assetIncidentCount(0);
    $this->assertFieldEnergyValue($entity, $this->fieldName, 1);

    $this->drupalGet($entity->toUrl());
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertPageEnergyValue($this->fieldName, 1);
  }

}
