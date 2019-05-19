<?php

namespace Drupal\trail_graph\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render trail graph.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "trail_graph",
 *   title = @Translation("Trail graph"),
 *   help = @Translation("Formats trail graphs"),
 *   theme = "views_view_trail_graph_content",
 *   display_types = { "normal" }
 * )
 */
class TrailGraphViewsStyle extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $data_provider = isset($this->options['data_provider']) ? $this->options['data_provider'] : 'default_trail_graph_data';
    $style_options = $form_state->getValue('style_options');
    if ($style_options && isset($style_options['data_provider'])) {
      $data_provider = $style_options['data_provider'];
    }

    /** @var \Drupal\trail_graph\Plugin\TrailGraphDataManager $trailGraphManager */
    $trailGraphManager = \Drupal::service('plugin.manager.trail_graph_data');
    $data_providers = $trailGraphManager->getDefinitions();
    $data_provider_options = [];
    foreach ($data_providers as $provider) {
      $data_provider_options[$provider['id']] = $provider['label'];
    }

    $form['#prefix'] = '<div id="trail_data_form_wrapper">';
    $form['#suffix'] = '</div>';

    $form['data_provider'] = [
      '#type' => 'select',
      '#title' => t('Data provider'),
      '#options' => $data_provider_options,
      '#required' => TRUE,
      '#default_value' => $data_provider,
      '#description' => t('Data provider for trail metadata.'),
      '#ajax' => [
        'event' => 'change',
        'wrapper' => 'trail_data_form_wrapper',
        'callback' => 'Drupal\trail_graph\Plugin\views\style\TrailGraphViewsStyle::ajaxCallback',
      ],
    ];

    if (array_key_exists($data_provider, $data_provider_options)) {
      $form['trail_data_options'] = $trailGraphManager->createInstance($data_provider)->buildOptionsForm($this);
    }
  }

  /**
   * Ajax callback for options form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\core\form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Returns form array.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['options']['style_options'];
  }

  /**
   * Resets and renders view fields.
   *
   * @param array $results
   *   Array of ResultRow objects from $view->result.
   */
  public function resetRenderFields(array $results) {
    unset($this->rendered_fields);
    $this->renderFields($results);
  }

}
