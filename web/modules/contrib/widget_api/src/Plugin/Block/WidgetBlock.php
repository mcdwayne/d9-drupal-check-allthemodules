<?php

namespace Drupal\widget_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\widget_api\Plugin\Widget\WidgetPluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'WidgetBlock' block plugin.
 *
 * @Block(
 *   id = "widget_block",
 *   admin_label = @Translation("Widget block"),
 *   deriver = "Drupal\widget_api\Plugin\Derivative\WidgetBlock"
 * )
 */
class WidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The widget interface.
   *
   * @var \Drupal\widget_api\Plugin\Widget\WidgetPluginManagerInterface
   */
  private $widgetInterface;

  /**
   * Creates a NodeBlock instance.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param WidgetPluginManagerInterface $widget_interface
   *   The widget interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WidgetPluginManagerInterface $widget_interface) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->widgetInterface = $widget_interface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [];
    foreach ($this->getFields() as $field_id => $field) {
      $config[$field_id] = isset($field['default_value']) ? $field['default_value'] : '';
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    foreach ($this->getFields() as $field_id => $field) {
      // Initialize field.
      $form[$field_id] = [];

      // Set the field options.
      foreach ($field as $field_index => $field_item) {
        if ($field_id !== 'default_value') {
          $form[$field_id]['#' . $field_index] = $field_item;
        }
      }

      // Set the default value.
      $form[$field_id]['#default_value'] = $config[$field_id];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach ($this->getFields() as $field_id => $field) {
      $this->configuration[$field_id] = $form_state->getValue($field_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [
      '#theme' => $this->pluginDefinition['definition']['theme'],
      'content' => [],
    ];

    // Get the config.
    $configuration = $this->getConfiguration();

    // Set it to the content.
    foreach ($this->getFields() as $field_id => $field) {
      $render_array['content'][$field_id] = $configuration[$field_id];
    }

    return $render_array;
  }

  /**
   * Gets the configuration fields.
   */
  private function getFields() {
    return $this->pluginDefinition['definition']['fields'];
  }

}
