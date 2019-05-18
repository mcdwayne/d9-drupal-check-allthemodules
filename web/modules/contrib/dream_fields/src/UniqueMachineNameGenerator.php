<?php

namespace Drupal\dream_fields;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class UniqueFieldMachineNameGenerator
 */
class UniqueMachineNameGenerator implements MachineNameGeneratorInterface {

  /**
   * The storage config.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The property to lookup for seeing if something is unique.
   *
   * @var string
   */
  protected $lookupProperty;

  /**
   * The maximum length of a field machine name.
   *
   * @var int
   */
  protected $maxMachineNameLength = 31;

  /**
   * {@inheritdoc}
   */
  public function getMachineName($input) {
    $trimmed_machine_name = trim(substr($this->cleanValue($input), 0, $this->maxMachineNameLength), '_');
    $counter = 0;
    $machine_name = $trimmed_machine_name;
    while ($this->isExistingMachineName($machine_name)) {
      // Create a counter based suffix to increment the field name.
      $suffix = '_' . $counter++;
      // Ensure the machine name fits within the length bounds, including the
      // suffix.
      $machine_name = substr($trimmed_machine_name, 0, $this->maxMachineNameLength - strlen($suffix)) . $suffix;
    }
    return $machine_name;
  }

  /**
   * Clean a value to be machine-friendly.
   *
   * @param string $value
   *   The input string.
   * @return string
   *   A clean value.
   */
  protected function cleanValue($value) {
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9_]+/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return $value;
  }

  /**
   * Check if a machine name already exists.
   *
   * @param string $name
   *   The input name.
   * @return bool
   *   If the field exists or not.
   */
  protected function isExistingMachineName($name) {
    $field = $this->storage->loadByProperties([
      $this->lookupProperty => $name,
    ]);
    return !empty($field);
  }

  /**
   * Create an instance of the unique field name generator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, $entity_type, $lookup_property) {
    $this->lookupProperty = $lookup_property;
    $this->storage = $entity_type_manager->getStorage($entity_type);
  }

}
