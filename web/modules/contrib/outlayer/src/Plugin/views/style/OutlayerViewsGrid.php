<?php

namespace Drupal\outlayer\Plugin\views\style;

/**
 * Outlayer style plugin for Masonry or Packery.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "outlayer_grid",
 *   title = @Translation("Outlayer Grid"),
 *   help = @Translation("Display the results in an Outlayer grid."),
 *   theme = "item_list",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class OutlayerViewsGrid extends OutlayerViewsGridStack {

  /**
   * {@inheritdoc}
   */
  protected function getDefinedFormScopes(array $extra_fields = []) {
    $definitions = parent::getDefinedFormScopes($extra_fields);
    $definitions['style'] = TRUE;
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettingsForm(&$form, &$definition) {
    parent::buildSettingsForm($form, $definition);

    // Hide gridstack optionset as we use Masonry and Packery instead.
    $form['optionset']['#type'] = 'hidden';

    if (isset($form['style'])) {
      $form['style']['#description'] = $this->t("<b>Packery</b> or <b>Masonry</b> will use the provided <b>Grid custom</b> value to build irregular grids. Be sure that the Outlayer optionset uses the same layout mode! Masonry for masonry, packery for packery.");
      $options = [
        'masonry' => $this->t('Masonry'),
        'packery' => $this->t('Packery'),
      ];
      $form['style']['#options'] = $options;
      $form['style']['#required'] = TRUE;
      unset($form['style']['#empty_option']);
    }

    // Blazy doesn't need complex grid with multiple groups.
    unset($form['preserve_keys'], $form['visible_items']);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSettings() {
    $dimensions = $this->manager->extractGridCustom($this->options);

    $this->options['dimensions'] = $dimensions;
    $this->options['dimensions_count'] = count($dimensions);

    return parent::buildSettings();
  }

}
