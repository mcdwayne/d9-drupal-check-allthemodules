<?php

/**
 * @file
 * Contains Drupal\pinterest_hover\Form\ConfigForm.
 */

namespace Drupal\pinterest_hover\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConfigForm.
 *
 * @package Drupal\pinterest_hover\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pinterest_hover.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pinterest_hover_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pinterest_hover.settings');
    $form['load_pinterest_js'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Pinterest script tag to all pages'),
      '#description' => $this->t('Whether this module should add a script tag to all pages loading Pinterest\'s JavaScript and enabling hover buttons. You may want to turn this off if you are including the Pinterest JavaScript in another way, such as in a different module or theme.'),
      '#default_value' => $config->get('load_pinterest_js'),
    ];
    $form['exclude_hover_selectors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Excluded items based on CSS selectors'),
      '#description' => $this->t('CSS selectors matching images that should not be pinnable or have Pin It hover buttons. One selector per line. Tip: to exclude all images in an area of your page, use descendant selectors like ".sidebar img"'),
      '#default_value' => $config->get('exclude_hover_selectors'),
    ];
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
    parent::submitForm($form, $form_state);

    // normalize line endings to \n
    $selectors = preg_replace('/[\r\n]+/', "\n", $form_state->getValue('exclude_hover_selectors'));

    $this->config('pinterest_hover.settings')
      ->set('load_pinterest_js', $form_state->getValue('load_pinterest_js'))
      ->set('exclude_hover_selectors', $selectors)
      ->save();
  }

}
