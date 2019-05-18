<?php

namespace Drupal\block_panelizer_usage\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Link;

/**
 * A handler to provide a custom field to be used in listing of custom blocks.
 * This field creates a report of the panelizer view modes on which a block
 * appears.
 *
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("block_panelizer_usage__default_view_modes")
 */
class block_panelizer_usage__default_view_modes extends FieldPluginBase {

  public $panelizered_displays_by_block = [];
  private $bundle_info;
  public $entityManager;
  public $panelizer;
  public $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = \Drupal::service('entity.manager');
    $this->panelizer = \Drupal::service('panelizer');
    $this->configFactory = \Drupal::service('config.factory');
    $this->bundle_info = $this->entityManager->getAllBundleInfo();
    $this->renderer = \Drupal::service('renderer');
    // Set the panelizered displays.
    $this->buildPanelizeredDisplaysByBlock();
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

    if (!empty($this->panelizered_displays_by_block[$plugin_uuid])) {
      $report = $this->panelizered_displays_by_block[$plugin_uuid];
    }

    // Cache by custom tag.
    $cache = ['#cache' => ['tags' => [BLOCK_PANELIZER_USAGE_CACHE_TAG_VIEW_MODES]]];

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
   * Load all node bundles, see which are panelizered, and build an array of
   * links by block uuid for the report.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function buildPanelizeredDisplaysByBlock() {

    // Loop through node types and get the enabled display modes that are panelizered.
    foreach ($this->entityManager->getStorage('node_type')->loadMultiple() as $node_type) {

      $values = [
        'targetEntityType' => 'node',
        'bundle' => $node_type->toArray()['type'],
        'status' => TRUE,
      ];
      $entity_view_display = EntityViewDisplay::create($values);

      // Loop through the enabled view modes, build index by block.
      foreach ($this->getEntityDisplays($entity_view_display) as $id => $view_display_mode) {
        list($entity_type, $bundle, $view_mode) = explode('.', $view_display_mode->id());

        // Only load displays that have been panelizered.
        if ($panel_display_array = $this->panelizer->getDefaultPanelsDisplays($entity_type, $bundle, $view_mode)) {

          $panelizered_display = array_values($panel_display_array)[0];
          if (!isset(array_values($panel_display_array)[0])) {
            continue;
          }

          $config = $panelizered_display->getConfiguration();
          if (empty($config['blocks'])) {
            continue;
          }

          foreach ($config['blocks'] as $block) {
            $this->addBlockLinkToPanelizeredDisplaysByBlock($config, $block, $view_display_mode->getMode());
          }
        }
      }
    }
  }

  /**
   * Add a link for a block to the report array, indexed by block uuid.
   *
   * @param  array $config A configuration array from
   *    \Drupal\Core\Entity\Display\EntityDisplayInterface->getConfiguration()
   * @param array $block A block array from
   *    \Drupal\Core\Entity\Display\EntityDisplayInterface->getConfiguration()['blocks'][]
   * @param string $view_mode A view mode string from
   *    \Drupal\Core\Entity\Display\EntityDisplayInterface->getMode()
   */
  protected function addBlockLinkToPanelizeredDisplaysByBlock($config, $block, $view_mode) {

    list($entity_type, $bundle, ,) = explode(':', $config['storage_id']);
    $bundle_info = $this->bundle_info;

    $route_machine_name = str_replace(':', '__', $config['storage_id']);

    // Create the render array for the link.
    $link_render = Link::createFromRoute(
    "{$bundle_info[$entity_type][$bundle]['label']}: {$view_mode}",
      'panelizer.wizard.edit',
      ['machine_name' => $route_machine_name,
        'step' => 'content']
    )->toRenderable();
    $link_html = $this->renderer->renderPlain($link_render);

    // Get the block uuid to serve as index.
    $block_uuid_array = explode(':', $block['id']);
    $block_uuid = end($block_uuid_array);

    // Add link by block uuid to the class variable.
    $this->panelizered_displays_by_block[$block_uuid][] = $link_html;
  }

  /**
   * Load all the entity displays for a entity bundle.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface[]
   *   An array holding entity displays or entity form displays.
   */
  protected function getEntityDisplays(EntityViewDisplayInterface $entity) {
    $load_ids = [];
    $display_entity_type = $entity->getEntityTypeId();
    $entity_type = $this->entityManager->getDefinition($display_entity_type);
    $config_prefix = $entity_type->getConfigPrefix();
    $ids = $this->configFactory->listAll($config_prefix . '.' . $entity->getTargetEntityTypeId() . '.' . $entity->getTargetBundle() . '.');

    foreach ($ids as $id) {
      $config_id = str_replace($config_prefix . '.', '', $id);
      list(,, $display_mode) = explode('.', $config_id);
      if ($display_mode != 'default') {
        $load_ids[] = $config_id;
      }
    }

    return $this->entityManager->getStorage($display_entity_type)->loadMultiple($load_ids);
  }

}

