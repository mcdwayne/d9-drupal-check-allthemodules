<?php

namespace Drupal\views_autorefresh_d8\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an area plugin for the Autorefresh header.
 *
 * @ingroup vicd ews_area_handlers
 *
 * @ViewsArea("views_autorefresh_area")
 */
class ViewsAutorefreshArea extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['interval'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['interval'] = [
      '#type' => 'textfield',
      '#title' => t('Interval to check for new items'),
      '#default_value' => $this->options['interval'],
      '#field_suffix' => 'milliseconds',
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    $view = $this->view;
    $interval = $this->options['interval'];

    // Attach the views_autorefresh library and the settings.
    $build['#attached']['library'][] = 'views_autorefresh_d8/views_autorefresh';
    $build['#attached']['drupalSettings']['views_autorefresh'][$view->id()][$view->current_display] = $interval;

    // Enable ajax, attach views.ajax library and add the view to
    // drupalSettings.
    $view->setAjaxEnabled(TRUE);
    views_views_pre_render($view);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('options')['interval'])) {
      $form_state->setError($form['interval'], $this->t('The interval has to be a numeric value.'));
    }
  }

}
