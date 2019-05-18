<?php

/**
 * @file
 * Contains \Drupal\ime\Form\ImeAdminForm.
 */

namespace Drupal\ime\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element;

/**
 * Implements an Ime Admin form.
 */
class ImeAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ime_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ime.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ime.settings'];
  }

  /**
   * Implements an Ime Admin form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $library = libraries_detect('jquery.ime');
    if (!$library['installed']) {
      if ($library['error'] == 'not found' || $library['error'] == 'not detected') {
        $lib_error_message = strip_tags($library['error message']);
        drupal_set_message(t('@error Please make sure the library is <a href="@installedcorrectly">installed correctly</a>.', [
          '@error' => $lib_error_message,
          '@installedcorrectly' => $base_url . '/admin/help/ime',
        ]), 'error');
        \Drupal::logger('jquery.ime')->error($library['error message'], []);
        return;
      }
    }

    $form['ime_enable'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable jquery.ime'),
      '#default_value' => \Drupal::config('ime.settings')->get('ime_enable'),
      '#description' => t("Add jquery.ime to elements with element IDs specified below."),
    ];

    $form['ime_ids'] = [
      '#type' => 'textarea',
      '#title' => t('Element IDs'),
      '#default_value' => \Drupal::config('ime.settings')->get('ime_ids'),
      '#description' => t('Comma separated list of element IDs with "#" tage of elements for which jQuery.ime will be enabled or element name.eg. #edit-title,textarea'),
    ];

    $form['ime_pages'] = [
      '#type' => 'textarea',
      '#title' => t('Enable IME on specific pages'),
      '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are blog for the blog page and blog/* for every personal blog."),
      '#default_value' => \Drupal::config('ime.settings')->get('ime_pages'),
    ];
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

}
