<?php

namespace Drupal\block_panelizer_usage\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;

/**
 * A handler to provide a custom field to be used in listing of custom blocks.
 * This field creates a report of the specific node content on which a block
 * appears.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("block_panelizer_usage__panelizered_nodes")
 */
class block_panelizer_usage__panelizered_nodes extends FieldPluginBase {

  public $panelizered_nids;
  public $panelizer;
  public $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->panelizer = \Drupal::service('panelizer');
    $this->panelizered_nids = $this->getPanelizeredNidsByBlockUuid();
    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_alter_empty'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $plugin_uuid = $values->_entity->get('uuid')->getString();

    if (!empty($this->panelizered_nids[$plugin_uuid])) {
      foreach ($this->panelizered_nids[$plugin_uuid] as $nid => $title) {
        $link_render = Link::createFromRoute($title, 'entity.node.canonical', ['node' => $nid])->toRenderable();
        $report[] = $this->renderer->renderPlain($link_render);
      }
    }

    // Cache by custom tag.
    $cache = ['#cache' => ['tags' => [BLOCK_PANELIZER_USAGE_CACHE_TAG_PANELIZERED_NODES]]];

    if (!empty($report)) {
      $render_array = [
        '#theme' => 'item_list',
        '#items' => $report,
      ];
    }

    else {
      // This is so field is properly hidden if empty. [] in item_list did not.
      // Empty markup must be returned so that it can be cached and cleared.
      $render_array = [
        '#markup' => '',
      ];
    }

    return array_merge($render_array, $cache);
  }

  /**
   * Gets an array of all node ids keyed by block uuid.
   *    @TODO Scale this. Probably works for sites with less than 10K panelized nodes.
   *
   * @return array node ids keyed by block uuid.
   */
  public function getPanelizeredNidsByBlockUuid() {
    $query = \Drupal::database()->query(
      'SELECT entity_id, panelizer_panels_display, title
        FROM node__panelizer
        INNER JOIN node_field_data 
        ON node_field_data.nid = node__panelizer.entity_id
        WHERE node__panelizer.deleted = :deleted',
      [':deleted' => 0]
    );

    $panelizer_node_configs = $query->fetchAll();

    // Loop through panelizered node configurations and get their blocks.
    $panelizered_nodes_by_block_uuid = [];
    foreach ($panelizer_node_configs as $panelizer_node_config) {
      $config = unserialize($panelizer_node_config->panelizer_panels_display);
      $nid = $panelizer_node_config->entity_id;
      $title = $panelizer_node_config->title;

      if (!isset($config['blocks'])) {
        continue;
      }

      // Loop through blocks to build block-uuid-keyed array of nids.
      foreach ($config['blocks'] as $panelizer_uuid => $block) {
        list($module, $block_uuid) = explode(':', $block['id']);
        if (!isset($panelizered_nodes_by_block_uuid[$block_uuid][$nid])) {
          $panelizered_nodes_by_block_uuid[$block_uuid][$nid] = $title;
        }
      }
    }

    return $panelizered_nodes_by_block_uuid;
  }
}

