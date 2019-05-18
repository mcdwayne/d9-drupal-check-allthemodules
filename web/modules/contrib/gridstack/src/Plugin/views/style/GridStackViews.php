<?php

namespace Drupal\gridstack\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Dejavu\BlazyStylePluginBase;
use Drupal\gridstack\GridStackDefault;
use Drupal\gridstack\Entity\GridStack;
use Drupal\gridstack\GridStackManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GridStack style plugin.
 */
class GridStackViews extends BlazyStylePluginBase {

  /**
   * The gridstack service manager.
   *
   * @var \Drupal\gridstack\GridStackManagerInterface
   */
  protected $manager;

  /**
   * Constructs a GridStackManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager, GridStackManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $blazy_manager);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'), $container->get('gridstack.manager'));
  }

  /**
   * Returns the gridstack admin.
   */
  public function admin() {
    return \Drupal::service('gridstack.admin');
  }

  /**
   * Returns the gridstack manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = ['stamp' => ['default' => '']];
    foreach (GridStackDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Returns the defined scopes for the current form.
   */
  protected function getDefinedFormScopes(array $extra_fields = []) {
    // Pass the common field options relevant to this style.
    $fields = [
      'captions',
      'layouts',
      'images',
      'links',
      'titles',
      'classes',
      'overlays',
    ];
    $fields = array_merge($fields, $extra_fields);

    // Fetches the returned field definitions to be used to define form scopes.
    $definition = $this->getDefinedFieldOptions($fields);
    $definition['opening_class'] = 'form--views';
    $definition['_views'] = TRUE;

    return $definition;
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definition = $this->getDefinedFormScopes();
    $this->buildSettingsForm($form, $definition);
  }

  /**
   * Build the Gridstack settings form.
   */
  protected function buildSettingsForm(&$form, &$definition) {
    $count = empty($definition['captions']) ? 0 : count($definition['captions']);
    $definition['captions_count'] = $count;
    $definition['stamps'] = $this->getViewsAsOptions('html_list');

    $this->admin()->buildSettingsForm($form, $definition);

    $title = '<p class="form__header form__title">';
    $title .= $this->t('Check Vanilla if using content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise Gridstack markups apply which require some fields added below.</small>');
    $title .= '</p>';

    $form['opening']['#markup'] .= $title;
    $form['image']['#description'] .= ' ' . $this->t('Be sure to UNCHECK "Use field template" (by default already UNCHECKED) to have it work for Blazy lazyloading. Use Blazy formatters for relevant features such as Colorbox/Photobox/Photoswipe, or multimedia supports.');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettings() {
    $settings = parent::buildSettings();

    // Prepare needed settings to work with.
    $settings['item_id']   = 'box';
    $settings['caption']   = array_filter($settings['caption']);
    $settings['namespace'] = 'gridstack';
    $settings['ratio']     = '';

    return $settings;
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings = $this->buildSettings();
    $optionset = GridStack::load($settings['optionset']);

    // Grids: x y width height image_style
    // Breakpoints: xs sm md lg, may contain width column image_style grids.
    // Converts gridstack breakpoint grids from stored JSON into array.
    $optionset->gridsJsonToArray($settings);

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $settings = array_filter($settings, function ($value) {
        return ($value !== NULL && $value !== '' && $value !== []);
      });
      $items = $this->buildElements($settings, $rows);

      // Supports Blazy multi-breakpoint images if using Blazy formatter.
      $settings['first_image'] = isset($rows[0]) ? $this->getFirstImage($rows[0]) : [];
      $build = [
        'items'     => $items,
        'optionset' => $optionset,
        'settings'  => $settings,
      ];

      $elements = $this->manager->build($build);
      unset($build);
    }

    return $elements;
  }

  /**
   * Returns gridstack contents.
   */
  public function buildElements(array $settings, $rows) {
    $build = [];
    $item_id = $settings['item_id'];
    $stamp_index = isset($settings['stamp_index']) ? $settings['stamp_index'] : -1;

    foreach ($rows as $delta => $row) {
      $this->view->row_index = $delta;

      $box = $box['attributes'] = [];
      $settings['delta'] = $delta;

      // Overrides fallback breakpoint image_style with grid image_style.
      if (!empty($settings['breakpoints'])) {
        $this->manager()->buildImageStyleMultiple($settings, $delta);
      }

      // Adds box type where image can be anything, not only image.
      if (!empty($settings['image']) && $this->getField($delta, $settings['image'])) {
        $settings['type'] = $row->_entity->getFieldDefinition($settings['image'])->getFieldStorageDefinition()->getType();
      }

      $box['settings'] = $settings;

      // Use Vanilla gridstack if so configured, ignoring GridStack markups.
      if (!empty($settings['vanilla'])) {
        $box[$item_id] = $this->view->rowPlugin->render($row);
      }
      else {
        // Build individual row/element contents.
        $this->buildElement($box, $row, $delta);
      }

      // Allows extending contents without overriding the entire loop method.
      $this->buildElementExtra($box, $row, $delta);

      // Extracts stamp from existing box.
      $stamp = [];
      if ($delta == $stamp_index && !empty($box['stamp'])) {
        $stamp = $box['stamp'];
        $stamp[$stamp_index]['settings']['type'] = 'stamp';
        unset($box['stamp']);
      }

      // Build gridstack items.
      $build[] = $box;

      // Inserts stamp to the array.
      if ($delta == $stamp_index && !empty($stamp)) {
        array_splice($build, $stamp_index, 0, $stamp);
      }

      unset($box);
    }

    unset($this->view->row_index);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildElement(array &$box, $row, $delta) {
    $settings = &$box['settings'];
    $item_id = $settings['item_id'];
    $use_category = TRUE;

    // Add row/ item classes.
    if (!empty($settings['class'])) {
      $classes = $this->getFieldString($row, $settings['class'], $delta);
      if (!empty($classes[$delta])) {
        $box['attributes']['class'][] = $classes[$delta];
      }
    }

    // Adds the rich box named overlay replacing the main stage to alternate
    // boring images with a mix of rich boxes: Slick carousel, any potential
    // block_field: video, currency, time, weather, ads, donations blocks, etc.
    if (!empty($settings['overlay']) && $this->getField($delta, $settings['overlay'])) {
      $box[$item_id] = $this->getFieldRendered($delta, $settings['overlay']);
      $settings['type'] = 'rich';
      $use_category = FALSE;

      // As this takes over the entire box contents, nullify extra unused works.
      foreach (['caption', 'image', 'link', 'overlay', 'title'] as $key) {
        $settings[$key] = '';
      }
    }

    // Adds stamp, such as HTML list of latest news, members, testimonials, etc.
    $stamp_index = isset($settings['stamp_index']) ? $settings['stamp_index'] : -1;
    if ($delta == $stamp_index && !empty($settings['stamp'])) {
      list($view_id, $display_id) = explode(":", $settings['stamp'], 2);

      $box['stamp'][$stamp_index] = [
        $item_id => views_embed_view($view_id, $display_id),
        'settings' => $settings,
        'attributes' => isset($box['attributes']) ? $box['attributes'] : [],
      ];
    }

    // Overrides parent::buildElement().
    parent::buildElement($box, $row, $delta);

    // Adds category, must run after parent::buildElement() where captions may
    // be nullified.
    if ($use_category && !empty($settings['category']) && $this->getField($delta, $settings['category'])) {
      $box['caption']['category'] = $this->getFieldRendered($delta, $settings['category']);
    }
  }

  /**
   * Returns extra row/ element content such as Isotope filters, sorters, etc..
   */
  public function buildElementExtra(array &$box, $row, $delta) {
    // Do nothing, let extender do their jobs.
  }

}
