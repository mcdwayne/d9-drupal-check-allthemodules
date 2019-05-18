<?php

namespace Drupal\file_ownage_ui\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions (action tabs) for all entity bundles.
 *
 * Code derived from ds.
 *
 * @see \Drupal\ds\Plugin\Derivative\DsLocalTask
 */
class FileOwnageLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an DevelLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    return $this->derivatives;

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // The action is valid for any fieldable entity,
      // as anything fieldable may have textareas to inspect.
      // if ($entity_type->hasLinkTemplate('display')) {.
      $this->derivatives["$entity_type_id.file_ownage_scan_tab"] = [
        // I need to provide this named route as well. TODO.
        'route_name' => "entity.$entity_type_id.file_ownage_scan",
        'weight' => 10,
        'title' => $this->t('Scan Files'),
        'base_route' => "entity.$entity_type_id.canonical",
      ];
      // }.
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
