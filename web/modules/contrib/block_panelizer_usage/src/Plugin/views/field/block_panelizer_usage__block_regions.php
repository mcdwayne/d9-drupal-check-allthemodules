<?php

namespace Drupal\block_panelizer_usage\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Link;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * A handler to provide a custom field to be used in listing of custom blocks.
 * This view field prints a list of blocks as they're used in site regions.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("block_panelizer_usage__block_regions")
 */
class block_panelizer_usage__block_regions extends FieldPluginBase {

  public $entityManager;
  public $theme_manager;
  public $theme_handler;
  public $renderer;
  public $regions;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = \Drupal::service('entity.manager');
    $this->theme_manager = \Drupal::service('theme.manager');
    $this->theme_handler = \Drupal::service('theme_handler');
    $this->renderer = \Drupal::service('renderer');
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->regions = system_region_list($this->options['theme_report']);
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
    $options['theme_report'] = ['default' => ''];
    $options['display_as_link'] = TRUE;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $theme_checkboxes = [];
    foreach ($this->theme_handler->listInfo() as $theme_name => $theme) {
      $theme_checkboxes[$theme_name] = $theme->getName();
    }

    $form['theme_report'] = [
      '#type' => 'select',
      '#title' => t('Choose the theme from which to load blocks for this report.'),
      '#weight' => -30,
      '#optional' => FALSE,
      '#default_value' => $this->options['theme_report'],
      '#options' => ['' => ''] + $theme_checkboxes
    ];

    $form['display_as_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Link to the Block layout page.'),
      '#weight' => -31,
      '#default_value' => $this->options['display_as_link'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $plugin_uuid = $values->_entity->get('uuid')->getString();

    // Prepare the report for blocks that match the current block.
    foreach ($this->getEnabledBlocks() as $block) {
      $this_plugin_uuid_array = explode(':', $block->getPlugin()->pluginId);
      $this_plugin_uuid = end($this_plugin_uuid_array);
      if ($plugin_uuid == $this_plugin_uuid) {
        $region_name = $this->regions[$block->getRegion()]->__toString();
        if ($this->options['display_as_link']) {
          $link_render = Link::createFromRoute($region_name, 'block.admin_display_theme', ['theme' => $this->options['theme_report']])->toRenderable();
          $report[] = $this->renderer->renderPlain($link_render);
        }
        else {
          $report[] = $region_name;
        }
      }
    }

    // Cache by block ID and config ID.
    $cache = [
      '#cache' => [
        'tags' => array_merge(
          $values->_entity->getCacheTags(),
          $block->getCacheTags(),
          [BLOCK_PANELIZER_USAGE_CACHE_TAG_BLOCK_REGIONS],
          [BLOCK_PANELIZER_USAGE_CACHE_TAG_BLOCK_REGIONS . ':' . $block->getPluginId()]
        )
      ]
    ];

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
   * Returns the array of enabled blocks from the theme set in the view options.
   *
   * @return array
   */
  protected function getEnabledBlocks() {

    // Change active theme to the selected one to get a block list from container.
    $actual_current_theme = $this->theme_manager->getActiveTheme();
    $theme_initializer = \Drupal::service('theme.initialization');
    $this->theme_manager->setActiveTheme($theme_initializer->getActiveThemeByName($this->options['theme_report']));
    // Get all enabled blocks in this theme.
    $all_blocks = $this->entityManager->getListBuilder('block')->load();

    $enabled_blocks = array_filter($all_blocks, function($block) {
      return $block->status();
    });

    // Set the active theme back.
    $this->theme_manager->setActiveTheme($actual_current_theme);

    return $enabled_blocks;
  }
}
