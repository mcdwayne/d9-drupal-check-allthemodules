<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\field\Markup.
 */

namespace Drupal\views_xml_backend\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide an XML markup field.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_xml_backend_markup")
 */
class Markup extends Standard {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new Markup object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['format']['default'] = '';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = [];
    foreach (filter_formats($this->currentUser) as $id => $format) {
      $options[$id] = $format->get('name');
    }

    $form['format'] = [
      '#title' => $this->t('Format'),
      '#description' => $this->t('The filter format'),
      '#type' => 'select',
      '#default_value' => $this->options['format'],
      '#required' => TRUE,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) {
    $text = str_replace('<!--break-->', '', $item['value']);

    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => $this->options['format'],
      '#filter_types_to_skip' => [],
      '#langcode' => '',
    ];

    return $this->renderer->renderPlain($build);
  }

}
