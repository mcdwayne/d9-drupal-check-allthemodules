<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for configurable BaseFieldConfig.
 *
 * @see \Drupal\content_entity_builder\Annotation\BaseFieldConfig
 * @see \Drupal\content_entity_builder\ConfigurableBaseFieldConfigBase
 * @see \Drupal\content_entity_builder\BaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\BaseFieldConfigBase
 * @see \Drupal\content_entity_builder\BaseFieldConfigManager
 * @see plugin_api
 */
interface ConfigurableBaseFieldConfigInterface extends BaseFieldConfigInterface, PluginFormInterface {

  public function buildDefaultValueForm(array $form, FormStateInterface $form_state);

}
