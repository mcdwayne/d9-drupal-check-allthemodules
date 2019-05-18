<?php

namespace Drupal\flexiform\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deriver class.
 */
class FormEntityProfileTypeDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_bundle_info
   *   The entity bundle info service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_manager,
    TranslationInterface $string_translation,
    EntityTypeBundleInfoInterface $entity_bundle_info
  ) {
    $this->entityBundleInfo = $entity_bundle_info;
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
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!\Drupal::moduleHandler()->moduleExists('profile')) {
      return [];
    }

    $entity_type = $this->entityTypeManager->getDefinition('profile');
    foreach ($this->entityBundleInfo->getBundleInfo('profile') as $bundle => $bundle_info) {
      $plugin_id = $bundle;
      $this->derivatives[$plugin_id] = [
          'label' => $this->t(
            '@bundle @profile from User',
            [
              '@bundle' => $bundle_info['label'],
              '@profile' => $entity_type->getLabel(),
            ]
          ),
          'profile_type' => $bundle,
          'context' => [
            'user' => new ContextDefinition('entity:user', $this->t('Base User')),
          ],
        ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
