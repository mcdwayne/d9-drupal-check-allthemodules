<?php

namespace Drupal\outlayer\Plugin\views\style;

use Drupal\views\Views;

/**
 * Outlayer style plugin for Isotope grid.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "outlayer_isotope",
 *   title = @Translation("Outlayer Isotope"),
 *   help = @Translation("Display the results in an Outlayer Isotope."),
 *   theme = "gridstack",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class OutlayerViewsIsotope extends OutlayerViewsGridStack {

  /**
   * Sorter fields.
   *
   * @var array
   */
  protected $resultSorters = [];

  /**
   * Filter fields.
   *
   * @var array
   */
  protected $resultFilters = [];

  /**
   * The aspect ratio.
   *
   * @var array
   */
  protected $aspectRatio = [];

  /**
   * {@inheritdoc}
   */
  protected function buildSettingsForm(&$form, &$definition) {
    parent::buildSettingsForm($form, $definition);

    foreach (['filter', 'sorter'] as $key) {
      $options = $this->getViewsAsOptions('outlayer_' . $key);
      $form[$key] = [
        '#title'         => $this->t('@title', ['@title' => ucfirst($key)]),
        '#type'          => 'select',
        '#default_value' => $this->options[$key],
        '#options'       => $options,
        '#empty_option'  => $this->t('- None -'),
        '#description'   => $this->t('Associate <b>@title</b>. If no <b>Outlayer @title</b> exists, create one first. Leave empty to not use <b>@title</b>.', ['@title' => $key]),
        '#weight'        => -69,
      ];
    }

    $form['grid_custom']['#description'] .= ' ' . $this->t("This will disable GridStack layout and unload its JS and CSS assets to use your custom defined grids instead. Be aware! Unlike GridStack rock-solid layout, this one is unpredictable. Leave it empty to use GridStack optionset.");
    if (isset($form['skin'])) {
      $form['skin']['#weight'] = -40;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $attach = [];
    $dimensions = $this->manager->extractGridCustom($this->options);

    $this->aspectRatio = $dimensions;
    $this->htmlSettings['dimensions'] = $attach['dimensions'] = $dimensions;
    $attach['dimensions_count'] = count($dimensions);
    $this->setHtmlSettings($attach);
    $settings = $this->buildSettings();

    if (!empty($settings['filter'])) {
      $this->buildFilter($settings);
    }

    if (!empty($settings['sorter'])) {
      $this->buildSorter($settings);
    }

    // Ensures sorters and filters data are passed to parent.
    return parent::render();
  }

  /**
   * Build the filter.
   */
  public function buildFilter(array $settings) {
    list($view_id, $display_id) = explode(":", $settings['filter'], 2);
    $view_x = Views::getView($view_id);
    $display = $view_x->storage->getDisplay($display_id);
    $field_name = $display['display_options']['style']['options']['filters'];

    $view_x->setDisplay($display_id);
    $view_x->preExecute();
    $view_x->execute();

    $style = $view_x->getStyle();

    $results = [];
    if (!empty($field_name) && $view_results = views_get_view_result($view_id, $display_id)) {
      foreach ($view_results as $delta => $row) {
        $style->view->row_index = $delta;

        $classes = $style->getFieldString($row, $field_name, $delta);
        if (empty($classes[$delta])) {
          continue;
        }

        $classes = explode(' ', $classes[$delta]);
        $items = [];
        foreach ($classes as $class) {
          $items[] = 'fltr-' . $class;
        }

        $results[] = implode(' ', $items);
      }
      unset($style->view->row_index);
    }

    $this->resultFilters = $results;
  }

  /**
   * Build the sorter.
   */
  public function buildSorter(array $settings) {
    list($view_id, $display_id) = explode(":", $settings['sorter'], 2);
    $view_x = Views::getView($view_id);
    $display = $view_x->storage->getDisplay($display_id);
    $fields = $display['display_options']['style']['options']['sorters'];
    $fields = array_filter($fields);

    $view_x->setDisplay($display_id);
    $view_x->preExecute();
    $view_x->execute();

    $style = $view_x->getStyle();

    $results = $keys = [];
    if (!empty($fields) && $view_results = views_get_view_result($view_id, $display_id)) {
      foreach ($view_results as $delta => $row) {
        $style->view->row_index = $delta;

        $sorters = [];
        foreach ($fields as $key => $field_name) {
          $classes = $style->getFieldString($row, $field_name, $delta, FALSE);
          if (empty($classes[$delta])) {
            continue;
          }

          $title = str_replace('field_', '', $key);
          $sorter_key = str_replace('_', '', $title);
          $sorters[$sorter_key] = $classes[$delta];
        }

        $results[] = $sorters;
      }
      unset($style->view->row_index);

      foreach ($fields as $key => $field_name) {
        $title = str_replace('field_', '', $key);
        $keys[] = str_replace('_', '', $title);
      }
    }

    $this->resultSorters = $results;
    $this->htmlSettings['sorters'] = $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function buildElementExtra(array &$box, $row, $delta) {
    $settings = $box['settings'];
    $attributes = &$box['attributes'];

    // Adds filter attributes.
    if (!empty($settings['filter']) && isset($this->resultFilters) && !empty($this->resultFilters[$delta])) {
      $attributes['class'][] = $this->resultFilters[$delta];
    }

    // Adds sorter attributes.
    if (!empty($settings['sorter']) && isset($this->resultSorters) && !empty($this->resultSorters[$delta])) {
      foreach ($this->resultSorters[$delta] as $key => $value) {
        $attributes['data-srtr-' . $key] = trim(strip_tags($value));
      }
    }

    // Be sure the stamp overrides other types.
    parent::buildElementExtra($box, $row, $delta);
  }

}
