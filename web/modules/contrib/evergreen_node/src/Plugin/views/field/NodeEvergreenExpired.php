<?php

namespace Drupal\evergreen_node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\evergreen\EvergreenServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to determine if a node has an evergreen_content entity.
 *
 * This returns whether or not the content is expired, but does not indicate if
 * it is evergreen.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_evergreen_expired")
 */
class NodeEvergreenExpired extends Boolean {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EvergreenServiceInterface $evergreen) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->evergreen = $evergreen;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('evergreen')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields['nid'] = 'nid';
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    if (!$field) {
      $id = $this->getValue($values, 'nid');
      $node = entity_load('node', $id);
      return $this->evergreen->entityHasExpired($node);
    }
    return parent::getValue($values, $field);
  }

}
