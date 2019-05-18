<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Entity list display plugins.
 */
abstract class EntityListDisplayBase extends PluginBase implements EntityListDisplayInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The current entity list object.
   *
   * @var \Drupal\entity_list\Entity\EntityListInterface
   */
  protected $entity;

  /**
   * The current display settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $configuration['entity'];
    $this->settings = $configuration['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    return [];
  }

}
