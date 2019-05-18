<?php

namespace Drupal\replicate_ui\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\replicate_ui\Form\ReplicateConfirmForm;

class EntityTypeAlter {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new EntityTypeAlter instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity type.
   */
  public function alter(array $entity_types) {
    $config = $this->configFactory->get('replicate_ui.settings');
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface && in_array($entity_type_id, (array) $config->get('entity_types')) && $entity_type->hasLinkTemplate('canonical')) {
        $entity_type->setFormClass('replicate', ReplicateConfirmForm::class);
        $entity_type->setLinkTemplate('replicate', $entity_type->getLinkTemplate('canonical') . '/replicate');
      }
    }
  }

}
