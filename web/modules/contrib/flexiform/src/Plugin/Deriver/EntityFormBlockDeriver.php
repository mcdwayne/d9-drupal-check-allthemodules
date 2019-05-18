<?php

namespace Drupal\flexiform\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\flexiform\FlexiformEntityFormDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deriver class.
 */
class EntityFormBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity bundle info manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Constructs new EntityViewDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display respository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity bundle info service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager,
    TranslationInterface $string_translation,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityTypeBundleInfoInterface $entity_bundle_info
  ) {
    $this->entityBundleInfo = $entity_bundle_info;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Contstrain contexts by bundle.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
        continue;
      }

      foreach ($this->entityBundleInfo->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
        $values = [];
        if ($entity_type->hasKey('bundle')) {
          $values[$entity_type->getKey('bundle')] = $bundle;
        }

        foreach ($this->entityDisplayRepository->getFormModeOptions($entity_type_id) as $mode_name => $mode_label) {
          /* @var \Drupal\flexiform\FlexiformEntityFormDisplayInterface $entity_form_display */
          $entity_form_display = FlexiformEntityFormDisplay::collectRenderDisplayLight($entity_type_id, $bundle, $mode_name);
          $plugin_id = "{$entity_type_id}.{$bundle}.{$mode_name}";
          $this->derivatives[$plugin_id] = [
            'admin_label' => $this->t(
              '@entity_type (@bundle) @mode form',
              [
                '@entity_type' => $entity_type->getLabel(),
                '@bundle' => $bundle_info['label'],
                '@mode' => $mode_label,
              ]
            ),
            'entity_type' => $entity_type_id,
            'bundle' => $bundle,
            'form_mode' => $mode_name,
            'context' => [
              'entity' => new ContextDefinition(
                'entity:' . $entity_type_id,
                $this->t('Base @entity_type', ['@entity_type' => $entity_type->getLabel()])
              ),
            ],
          ] + $base_plugin_definition;

          foreach ($entity_form_display->getFormEntityConfig() as $namespace => $form_entity_info) {
            if ($form_entity_info['plugin'] != 'provided') {
              continue;
            }

            $this->derivatives[$plugin_id]['context'][$namespace] = new ContextDefinition(
              'entity:' . $form_entity_info['entity_type'],
              $form_entity_info['label']
            );
          }
        }
      }
    }

    return $this->derivatives;
  }

}
