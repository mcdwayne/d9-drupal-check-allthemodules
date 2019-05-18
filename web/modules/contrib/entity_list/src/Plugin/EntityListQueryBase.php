<?php

namespace Drupal\entity_list\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Entity list query plugins.
 */
abstract class EntityListQueryBase extends PluginBase implements EntityListQueryInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The current entity list object.
   *
   * @var \Drupal\entity_list\Entity\EntityListInterface
   */
  protected $entity;

  /**
   * The current settings array.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $configuration['entity'];
    $this->settings = $configuration['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function allRevisions() {
    // Do nothing by default.
  }

  /**
   * {@inheritdoc}
   */
  public function currentRevision() {
    // Do nothing by default.
  }

  /**
   * {@inheritdoc}
   */
  public function tableSort(&$headers) {
    // Do nothing by default.
  }

  /**
   * {@inheritdoc}
   */
  public function latestRevision() {
    // Do nothing by default.
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->settings['entity_type'] ?? '';
  }

}
