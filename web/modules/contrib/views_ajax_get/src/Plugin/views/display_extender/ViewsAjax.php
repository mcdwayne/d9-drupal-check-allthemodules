<?php

namespace Drupal\views_ajax_get\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Defines a display extender for views_ajax_get.
 *
 * @ViewsDisplayExtender(
 *   id = "views_ajax_get",
 *   title = @Translation("Views ajax get")
 * )
 */
class ViewsAjax extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['ajax_get'] = ['default' => FALSE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($form_state->get('section') === 'use_ajax') {
      $form['ajax_get'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use GET requests'),
        '#default_value' => $this->options['ajax_get'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'use_ajax':
        $this->options['ajax_get'] = $form_state->getValue('ajax_get');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections(&$sections, $section = NULL) {
    $sections['ajax_get'] = ['ajax_get'];
  }

}
