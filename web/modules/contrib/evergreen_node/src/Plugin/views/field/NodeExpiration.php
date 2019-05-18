<?php

namespace Drupal\evergreen_node\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\evergreen\EvergreenServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Field handler shows the expiration date for the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_evergreen_expiration")
 */
class NodeExpiration extends Date {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $date_formatter, EntityStorageInterface $date_format_storage, EvergreenServiceInterface $evergreen) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $date_formatter, $date_format_storage);

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
      $container->get('date.formatter'),
      $container->get('entity.manager')->getStorage('date_format'),
      $container->get('evergreen')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->additional_fields['nid'] = 'nid';
    // $this->additional_fields['evergreen_content'] = 'evergreen_expires';
    // $this->field_alias = 'evergreen_content.evergreen_expires';
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
      return $this->evergreen->entityExpirationDate($node);
    }
    return parent::getValue($values, $field);
  }

}
