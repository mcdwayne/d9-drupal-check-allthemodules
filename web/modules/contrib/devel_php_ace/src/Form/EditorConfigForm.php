<?php

namespace Drupal\devel_php_ace\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Form to add student groups.
 */
class EditorConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'devel_php_ace_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'devel_php_ace.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('devel_php_ace.settings');

    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Active theme'),
      '#description' => Link::fromTextAndUrl($this->t('Browse available themes'), Url::fromUri('http://ace.c9.io/build/kitchen-sink.html')),
      '#default_value' => $config->get('theme'),
      '#options' => [],
    ];

    $form['devel_php_ace_src'] = array(
      '#title' => $this->t('Editor version'),
      '#type' => 'select',
      '#description' => $this->t('Which provided version of Ace should be used?'),
      '#options' => array(
        'src' => 'concatenated but not minified',
        'src-min' => 'concatenated and minified with uglify.js',
        'src-noconflict' => 'uses ace.require instead of require',
        'src-min-noconflict' => 'uses ace.require instead of require and minifed',
      ),
      '#default_value' => 'src-min-noconflict',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    dpm($form_state->getValues());
  }

}
