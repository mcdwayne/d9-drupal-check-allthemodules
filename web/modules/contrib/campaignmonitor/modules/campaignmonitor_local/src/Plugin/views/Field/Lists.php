<?php

namespace Drupal\campaignmonitor_local\Plugin\views\field;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\field\PrerenderList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a list of CM lists.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("campaignmonitor_local_lists")
 */
class Lists extends PrerenderList {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   Database Service Object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['uid'] = ['table' => 'users_field_data', 'field' => 'uid'];
  }

  /**
   *
   */
  public function query() {
    $this->addAdditionalFields();
    $this->field_alias = $this->aliases['uid'];
  }

  /**
   *
   */
  public function preRender(&$values) {
    $uids = [];
    $this->items = [];
    $lists = campaignmonitor_get_list_options();
    foreach ($values as $record) {
      $uids[] = $record->uid;
    }

    if ($uids) {
      $available_lists = campaignmonitor_get_list_options();
      $result = $this->database->query('SELECT cls.uid, cls.list_id FROM {campaignmonitor_local_subscriptions} cls WHERE cls.uid IN ( :uids[] ) AND cls.list_id IN ( :lids[] )', [':uids[]' => $uids, ':lids[]' => array_keys($available_lists)]);
      foreach ($result as $sub) {
        $this->items[$sub->uid][$sub->list_id]['name'] = $lists[$sub->list_id];
        $this->items[$sub->uid][$sub->list_id]['list_id'] = $sub->list_id;
      }
    }
  }

  /**
   *
   */
  public function renderItem($count, $item) {
    return $item['name'];
  }

}
