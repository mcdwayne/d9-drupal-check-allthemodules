<?php

namespace Drupal\evergreen\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Content view form.
 */
class ContentViewForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'evergreen_content_view';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $info = $form_state->getBuildInfo();
    $content_view_options = $info['args'][0];
    $evergreen_plugin_manager = $info['args'][1];

    $plugin = array_keys($content_view_options)[0];
    if ($form_state->getValue('select_view')) {
      $plugin = $form_state->getValue('select_view');
    }

    $form['select_view'] = [
      '#type' => 'select',
      '#title' => 'Select view',
      '#options' => $content_view_options,
      '#default_value' => $plugin,
    ];

    $form['view'] = [
      '#type' => 'container',
      '#prefix' => '<div id="evergreen-content-view-container">',
      '#suffix' => '</div>',
    ];

    $plugin = $evergreen_plugin_manager->createInstance($plugin);
    $view = $plugin->getContentView();

    switch ($view['type']) {
      case 'view':
        $form['view']['view'] = view_embed_view($view['name'], isset($view['display_id']) ? $view['display_id'] : '');
        break;

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
