<?php

namespace Drupal\display_mode_extras;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides permissions of the display_mode_extras module.
 */
class DisplayModeExtrasPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The settings of this module.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * DisplayModeExtrasPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_manager;
    $this->settings = $this->configFactory->get('display_mode_extras.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Get list of permissions.
   *
   * @return array
   *   Collection of permissions.
   */
  public function permissions() {

    $perms = [];

    $settings_form_modes = $this->settings->get('form_modes');
    $settings_view_modes = $this->settings->get('view_modes');

    foreach ($settings_form_modes as $entity_type_id => $form_modes) {
      foreach ($form_modes as $form_mode_name => $form_mode) {
        if ($form_mode['enabled']) {
          $perms += [
            "use form mode $entity_type_id $form_mode_name" => [
              'title' => $this->t('Use form mode %label', ['%label' => $form_mode['label']]),
            ],
          ];
        }
      }
    }

    foreach ($settings_view_modes as $entity_type_id => $view_modes) {
      foreach ($view_modes as $view_mode_name => $view_mode) {
        if ($view_mode['enabled']) {
          $perms += [
            "use view mode $entity_type_id $view_mode_name" => [
              'title' => $this->t('Use view mode %label', ['%label' => $view_mode['label']]),
            ],
          ];
        }
      }
    }

    return $perms;
  }

}
