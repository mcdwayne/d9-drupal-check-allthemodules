<?php
/**
 * @file
 * Contains \Drupal\entity_reference_form\BrokenEntityReferenceRegistrar
 */

namespace Drupal\entity_reference_form;


use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BrokenEntityReferenceRegistry implements ContainerInjectionInterface {

  /**
   * @var string[][]
   */
  protected $brokenReferences = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $sub_entity
   * @param \Drupal\Core\Entity\EntityInterface $main_entity
   * @param string $reference_field_name
   * @param int $reference_field_delta
   */
  public function register(EntityInterface $sub_entity, EntityInterface $main_entity, $reference_field_name, $reference_field_delta) {
    $this->brokenReferences[$sub_entity->uuid()] = [
      'reference_field_name' => $reference_field_name,
      'reference_field_delta' => $reference_field_delta,
      'main_entity_type_id' => $main_entity->getEntityTypeId(),
    ];
  }

  public function inBrokenReference(EntityInterface $entity) {
    return array_key_exists($entity->uuid(), $this->brokenReferences);
  }

  public function getParentTypeId(EntityInterface $entity) {
    return $this->brokenReferences[$entity->uuid()]['main_entity_type_id'];
  }

  public function getReferenceFieldName(EntityInterface $entity) {
    return $this->brokenReferences[$entity->uuid()]['reference_field_name'];
  }

  public function getReferenceFieldDelta(EntityInterface $entity) {
    return $this->brokenReferences[$entity->uuid()]['reference_field_delta'];
  }

  public function getAll() {
    return $this->brokenReferences;
  }
}