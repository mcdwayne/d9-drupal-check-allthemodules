<?php

namespace Drupal\text_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The Text Block plugin.
 *
 * @Block(
 *   id = "text_block",
 *   admin_label = @Translation("Text Block"),
 *   category = @Translation("Text Block")
 * )
 */
class TextBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id , $plugin_definition, $container->get('module_handler'));
  }

  /**
   * TextBlock constructor.
   *
   * @param array $configuration
   *   The settings configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = ['text' => ['value' => NULL, 'format' => NULL]];
    if ($this->moduleHandler->moduleExists('filter')) {
      $default['text']['format'] = filter_default_format();
    }
    return $default + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $settings = $this->getConfiguration();
    $form['text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#default_value' => $settings['text']['value'],
      '#required' => TRUE,
    ];
    if ($this->moduleHandler->moduleExists('filter')) {
      $form['text']['#type'] = 'text_format';
      $form['text']['#format'] = $settings['text']['format'];
    }
    return $form + parent::blockForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $text = $form_state->getValue('text');
    if (is_string($text)) {
      $text = ['value' => $text, 'format' => NULL];
    }
    $this->configuration['text'] = $text;
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = $this->getConfiguration();
    $value = isset($settings['text']['value']) ? $settings['text']['value'] : '';
    $format = isset($settings['text']['format']) ? $settings['text']['format'] : NULL;
    if (!isset($format) || !$this->moduleHandler->moduleExists('filter')) {
      return ['#markup' => $value];
    }
    return [
      '#type' => 'processed_text',
      '#text' => $value,
      '#format' => $format,
    ];
  }

}
