<?php

namespace Drupal\Tests\radioactivity\Functional;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\radioactivity\DefaultIncidentStorage;

trait RadioActivityFunctionTestTrait {


  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity type bundle.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * Adds a Count type energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $defaultEnergy
   * @param int $cardinality
   */
  public function addCountEnergyField($fieldName, $defaultEnergy = 0, $cardinality = 1) {

    $granularity = $halfLifeTime = $cutoff = 0;
    $this->createEnergyField($fieldName, 'count', TRUE, $defaultEnergy, $granularity, $halfLifeTime, $cutoff, $cardinality);
  }

  /**
   * Adds a Linear type energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $defaultEnergy
   * @param int $granularity
   * @param int|float $cutoff
   * @param int $cardinality
   */
  public function addLinearEnergyField($fieldName, $defaultEnergy = 0, $granularity = 900, $cutoff = 10, $cardinality = 1) {

    $halfLifeTime = 0;
    $this->createEnergyField($fieldName, 'linear', TRUE, $defaultEnergy, $granularity, $halfLifeTime, $cutoff, $cardinality);
  }

  /**
   * Adds a Decay type energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $defaultEnergy
   * @param int $granularity
   * @param int $halfLifeTime
   * @param int|float $cutoff
   * @param int $cardinality
   */
  public function addDecayEnergyField($fieldName, $defaultEnergy = 0, $granularity = 0, $halfLifeTime = 43200, $cutoff = 10, $cardinality = 1) {

    $this->createEnergyField($fieldName, 'decay', TRUE, $defaultEnergy, $granularity, $halfLifeTime, $cutoff, $cardinality);
  }

  /**
   * Adds an radioactivity energy field to the content type.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param string $profile
   *   Profile type.
   * @param bool $required
   *   Required input.
   * @param int|float $defaultEnergy
   *   Field energy when the entity is created.
   * @param int $granularity
   *   Energy decay granularity.
   * @param int $halfLifeTime
   *   Half life time.
   * @param int|float $cutoff
   *   Energy cut off value.
   * @param int $cardinality
   *   Field cardinality.
   */
  protected function createEnergyField($fieldName, $profile, $required = FALSE, $defaultEnergy = 0, $granularity = 900, $halfLifeTime = 43200, $cutoff = 10, $cardinality = 1) {

    FieldStorageConfig::create([
      'entity_type' => $this->entityType,
      'type' => 'radioactivity',
      'field_name' => $fieldName,
      'cardinality' => $cardinality,
      'settings' => [
        'profile' => $profile,
        'granularity' => $granularity,
        'halflife' => $halfLifeTime,
        'cutoff' => $cutoff,
      ],
    ])->save();

    FieldConfig::create([
      'entity_type' => $this->entityType,
      'bundle' => $this->entityBundle,
      'field_name' => $fieldName,
      'required' => $required,
      'default_value' => [
        [
          'energy' => $defaultEnergy,
          'timestamp' => 0,
        ],
      ],
    ])->save();
  }

  /**
   * Creates an energy field formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   */
  protected function createEnergyFormDisplay($fieldName) {
    $entityFormDisplay = EntityFormDisplay::load('entity_test.entity_test.default');
    $entityFormDisplay->setComponent($fieldName, [
      'type' => 'radioactivity_energy',
    ]);
    $entityFormDisplay->save();
  }

  /**
   * Creates an emitter field formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int|float $energy
   *   The energy to emit.
   * @param string $display
   *   The field display type.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The entity view display object.
   */
  protected function createEmitterViewDisplay($fieldName, $energy = 10, $display = 'raw') {
    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->entityBundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $entity_view_display->setComponent($fieldName, [
      'type' => 'radioactivity_emitter',
      'settings' => [
        'energy' => $energy,
        'display' => $display,
      ],
    ]);
    $entity_view_display->save();
    return $entity_view_display;
  }

  /**
   * Creates an value field formatter.
   *
   * @param string $fieldName
   *   Field machine name.
   * @param int $decimals
   *   Number of decimals to display.
   *
   * @return \Drupal\Core\Entity\Entity\EntityViewDisplay
   *   The entity view display object.
   */
  protected function createValueViewDisplay($fieldName, $decimals = 0) {
    $entity_view_display = EntityViewDisplay::create([
      'targetEntityType' => $this->entityType,
      'bundle' => $this->entityBundle,
      'mode' => 'default',
    ]);
    $entity_view_display->setComponent($fieldName, [
      'type' => 'radioactivity_value',
      'settings' => ['decimals' => $decimals],
    ]);
    $entity_view_display->save();
    return $entity_view_display;
  }

  /**
   * Sets the emitter energy of a field.
   *
   * @param string $fieldName
   * @param int $energy
   */
  public function setFieldEmitterEnergy($fieldName, $energy = 10) {
    $this->updateFieldEmitterSettings($fieldName, ['energy' => $energy]);
  }

  /**
   * Sets the emitter display mode of a field.
   *
   * @param string $fieldName
   * @param bool $displayEnergy
   */
  public function setFieldEmitterDisplay($fieldName, $displayEnergy = FALSE) {
    $display = $displayEnergy ? 'raw' : 'none';
    $this->updateFieldEmitterSettings($fieldName, ['display' => $display]);
  }

  /**
   * Updates the emitter field display settings.
   *
   * @param string $fieldName
   * @param array $settings
   *   Allowed keys:
   *   'energy': The energy value this field will emit when displayed.
   *   'raw':    True if the energy value is visible.
   */
  protected function updateFieldEmitterSettings($fieldName, array $settings) {

    $display = EntityViewDisplay::load('entity_test.entity_test.default');
    $component = $display->getComponent($fieldName);

    foreach ($settings as $key => $value) {
      $component['settings'][$key] = $value;
    }
    $display->setComponent($fieldName, $component)
      ->save();
  }

  /**
   * Creates an entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  public function createContent() {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = \Drupal::entityTypeManager()->getStorage($this->entityType)->create([
      'type' => $this->entityType,
      'title' => $this->randomString(),
    ]);
    $entity->save();

    return $entity;
  }

  /**
   * Assert the energy values from a field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $fieldName
   * @param array|string|int $expectedValues
   * @param string $operator
   * @param string $message
   */
  public function assertFieldEnergyValue(EntityInterface $entity, $fieldName, $expectedValues, $operator = '==', $message = '') {
    $expectedValues = is_array($expectedValues) ? $expectedValues : [$expectedValues];
    $actualValues = array_map(
      function ($item) {
        return $item['energy'];
      },
      $entity->get($fieldName)->getValue()
    );

    $this->assertEnergyValues($fieldName, $actualValues, $expectedValues, $operator, $message);
  }

  /**
   * Assert the energy values from the page.
   *
   * @param $fieldName
   * @param array|string|int $expectedValues
   * @param string $operator
   * @param string $message
   */
  public function assertPageEnergyValue($fieldName, $expectedValues, $operator = '==', $message = '') {
    $expectedValues = is_array($expectedValues) ? $expectedValues : [$expectedValues];
    $actualValues = $this->getPageEnergyValues($fieldName);

    $this->assertEnergyValues($fieldName, $actualValues, $expectedValues, $operator, $message);
  }

  /**
   * Assert field energy values.
   *
   * @param string $fieldName
   * @param array $actualValues
   * @param array $expectedValues
   * @param string $operator
   * @param string $message
   */
  private function assertEnergyValues($fieldName, array $actualValues, array $expectedValues, $operator = '==', $message = '') {
    if (array_diff(array_keys($actualValues), array_keys($expectedValues))) {
      throw new \RuntimeException(sprintf('Invalid number of expected values for %s.', $fieldName));
    }

    foreach ($actualValues as $key => $actual) {
      $expected = $expectedValues[$key];

      switch ($operator) {
        case '>':
          $result = $actual > $expected;
          break;

        case '>=':
          $result = $actual >= $expected;
          break;

        case '<':
          $result = $actual < $expected;
          break;

        case '<=':
          $result = $actual <= $expected;
          break;

        case '==':
        default:
          $result = $actual == $expected;
      }
      $message = $message ?: $message = sprintf('The energy value of %s is %s, but %s expected.', $fieldName, $actual, $expected);
      $this->assertTrue($result, $message);
    }
  }

  /**
   * Gets the field's energy values from the session's page.
   *
   * @param $fieldName
   *
   * @return array
   */
  public function getPageEnergyValues($fieldName) {
    $values = [];
    $fieldBaseName = substr($fieldName, 6);
    $selector = '.field--name-field-' . $fieldBaseName . ' .field__item';

    $rows = $this->getSession()->getPage()->findAll('css', $selector);
    if ($rows) {
      foreach ($rows as $row) {
        $values[] = $row->getHtml();
      }
    }

    return $values;
  }

  /**
   * Asserts the actual incident count.
   *
   * @param $expected
   * @param string $message
   */
  public function assetIncidentCount($expected, $message = '') {
    $actual = $this->getIncidentCount();
    $message = $message ?: $message = sprintf('The incident count is %s, but %s expected.', $actual, $expected);
    $this->assertTrue($actual == $expected, $message);
  }

  /**
   * Gets the number of incidents from the incident storage.
   *
   * @return integer
   */
  public function getIncidentCount() {
    $storage = new DefaultIncidentStorage(\Drupal::state());
    return count($storage->getIncidents());
  }

}
