<?php

namespace Drupal\outlayer\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\blazy\Blazy;
use Drupal\outlayer\OutlayerDefault;
use Drupal\outlayer\OutlayerHook;

/**
 * Outlayer style plugin for Isotope filter.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "outlayer_filter",
 *   title = @Translation("Outlayer Filter"),
 *   help = @Translation("Display the results in an Outlayer filter."),
 *   theme = "item_list",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class OutlayerViewsFilter extends OutlayerViewsBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (OutlayerDefault::viewsFilterSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields                      = ['classes'];
    $definition                  = $this->getDefinedFieldOptions($fields);
    $definition['namespace']     = 'outlayer';
    $definition['outlayers']     = $this->getViewsAsOptions('outlayer_isotope');
    $definition['opening_class'] = 'form--views';

    $count = count($definition['classes']);
    $definition['captions_count'] = $count;

    $this->admin()->filterForm($form, $definition);
    $this->admin()->closingForm($form, $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $settings = $this->buildSettings();
    $instance = $settings['instance_id'];
    $plugin_id = $settings['plugin_id'];

    // All views must have the same instance in order to coordinate.
    if (!empty($settings['outlayer'])) {
      list($main_view_name, $main_view_display) = explode(":", $settings['outlayer'], 2);
      $instance = str_replace('_', '-', "{$main_view_name}-{$main_view_display}");
    }

    $settings['id'] = Blazy::getHtmlId("{$plugin_id}-{$instance}");
    $settings['instance_id'] = $instance;

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $items = $this->buildElements($settings, $rows);

      $elements[0] = $this->buildItemList($items, $settings, 'filter');

      // Searchable.
      if (!empty($settings['searchable'])) {
        if (!empty($settings['search_reset'])) {
          $variables = [
            'classes' => ['button--search', 'button--reset'],
            'filter'  => '*',
            'title'   => $settings['search_reset'],
          ];
          $items = ['all' => OutlayerHook::button($variables)];
        }

        $attributes = new Attribute();
        $attributes->addClass(['form-text', 'form-text--search']);
        $attributes->setAttribute('type', 'text');
        $attributes->setAttribute('value', '');
        $attributes->setAttribute('placeholder', strip_tags($settings['searchable']));
        $items['search'] = [
          '#markup' => '<span class="icon icon-search"></span><input' . $attributes . ' />',
          '#allowed_tags' => ['span', 'input'],
        ];

        $elements[1] = $this->buildItemList($items, $settings, 'search');
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements($settings, $rows) {
    $build = [];

    if (empty($settings['filters'])) {
      return [];
    }

    $selector = '*';
    $button_classes[] = 'button--filter';
    if (!empty($settings['filter_reset'])) {
      $variables = [
        'classes' => array_merge(['button--reset', 'is-active'], $button_classes),
        'filter'  => $selector,
        'title'   => $settings['filter_reset'],
      ];

      $build['all'] = OutlayerHook::button($variables);
    }

    $items = [];
    foreach ($rows as $index => $row) {
      $this->view->row_index = $index;

      $classes = $this->getFieldString($row, $settings['filters'], $index);
      if (empty($classes[$index])) {
        continue;
      }

      $items[] = $classes[$index];
    }

    unset($this->view->row_index);
    if (empty($items)) {
      return [];
    }

    foreach (array_unique($items) as $item) {
      $selectors = [];
      $filters = explode(' ', $item);
      foreach ($filters as $filter) {
        $selectors[] = '.fltr-' . $filter;
      }
      $selector = implode(',', $selectors);

      if (is_string($item)) {
        $item = str_replace('-', ' ', $item);
      }

      $variables = [
        'classes' => $button_classes,
        'filter'  => $selector,
        'title'   => $item,
      ];

      $box = OutlayerHook::button($variables);

      // Build outlayer items.
      $build[] = $box;
      unset($box);
    }

    return $build;
  }

}
