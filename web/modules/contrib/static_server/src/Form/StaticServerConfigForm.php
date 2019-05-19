<?php
/**
 * @file
 * Contains \Drupal\static_server\Form\StaticServerConfigForm.
 */

namespace Drupal\static_server\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Static Server Config Form.
 */
class StaticServerConfigForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('static_server.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_static_server_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $default_css = $base_url;
    $default_js = $base_url;
    $default_files = $base_url;
    $default_ext = 'gif jpg jpeg png';

    if (!empty(\Drupal::config('static_server.settings')->get('static_server_css_path'))) {
      $default_css = \Drupal::config('static_server.settings')->get('static_server_css_path');
    }
    if (!empty(\Drupal::config('static_server.settings')->get('static_server_js_path'))) {
      $default_js = \Drupal::config('static_server.settings')->get('static_server_js_path');
    }
    if (!empty(\Drupal::config('static_server.settings')->get('static_server_files_path'))) {
      $default_files = \Drupal::config('static_server.settings')->get('static_server_files_path');
    }
    if (!empty(\Drupal::config('static_server.settings')->get('static_server_files_extension'))) {
      $default_ext = \Drupal::config('static_server.settings')->get('static_server_files_extension');
    }

  	$form['static_server']['static_server_css_path'] = array(
      '#title' => t("CSS Files Path"),
      '#type' => 'textfield',
      '#default_value' => $default_css,
    );

    $form['static_server']['static_server_js_path'] = array(
      '#title' => t("JS Files Path"),
      '#type' => 'textfield',
      '#default_value' => $default_js,
    );

    $form['static_server']['static_server_files_path'] = array(
      '#title' => t("Files Path"),
      '#type' => 'textfield',
      '#default_value' => $default_files,
    );

    $form['static_server']['static_server_files_extension'] = array(
      '#title' => t("Files Extension"),
      '#type' => 'textfield',
      '#default_value' => $default_ext,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $static_css = $form_state->getValue('static_server_css_path');
    $static_js = $form_state->getValue('static_server_js_path');
    $static_file = $form_state->getValue('static_server_files_path');
    $static_extensions = $form_state->getValue('static_server_files_extension');

    // Load configuration object and save values.
    $config = \Drupal::getContainer()->get('config.factory')->getEditable('static_server.settings');
    $config->set('static_server_css_path', $static_css)->save();
    $config->set('static_server_js_path', $static_js)->save();
    $config->set('static_server_files_path', $static_file)->save();
    $config->set('static_server_files_extension', $static_extensions)->save();
  	drupal_set_message($this->t('The settings have been saved'), 'status');
  }
}
