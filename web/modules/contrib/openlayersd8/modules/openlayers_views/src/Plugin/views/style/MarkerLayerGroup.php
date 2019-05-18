<?php

namespace Drupal\openlayers_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;

/**
 * Style plugin to render openlayers features in layer groups.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "openlayers_marker_group",
 *   title = @Translation("Grouped Markers"),
 *   help = @Translation("Render data as openlayers markers, grouped in layers."),
 *   display_types = {"openlayers"},
 * )
 */
class MarkerLayerGroup extends MarkerDefault {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = TRUE;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function renderGrouping($records, $groupings = array(), $group_rendered = NULL) {
    $sets = parent::renderGrouping($records, $groupings, $group_rendered);
    if (!$groupings) {
      // Set group label to display label, if empty.
      $attachment_title = $this->view->getDisplay()->getOption('title');
      $sets['']['group'] = $attachment_title ? $attachment_title : $this->t('Label missing');
    }
    return $sets;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderOpenlayersGroup(array $features = array(), $title = '', $level = 0) {
    return array(
      'group' => TRUE,
      'label' => $title,
      'features' => $features,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
    // Add group options.
  }

}
