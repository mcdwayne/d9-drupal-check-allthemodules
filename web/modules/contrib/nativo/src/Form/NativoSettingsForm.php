<?php
/**
 * @file
 * Contains \Drupal\nativo\Form\NativoSettingsForm.
 */
namespace Drupal\nativo\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\form\FormStateInterface;

/**
 * Implements Nativo's settings form.
 */
class NativoSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'nativo_settings_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nativo.settings');

    $form['html'] = array(
      '#type' => 'textarea',
      '#title' => t('HTML for Nativo template'),
      '#description' => t('See <a target="_blank" href="@url">the Nativo Integration Guide</a> for required/optional HTML elements', array('@url' => 'http://www.nativo.net/download/postrelease_js_install.pdf')),
      '#default_value' => $config->get('html'),
      '#rows' => 5,
      '#cols' => 30,
    );

    $form['js_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include Nativo JS'),
      '#description' => t('Include the Natvio javascript file on front-facing pages'),
      '#default_value' => $config->get('js_enabled'),
    );

    $form['admin_path_pattern'] = array(
      '#type' => 'textfield',
      '#size' => 128,
      '#title' => t('Advanced users: Path pattern to NOT load Nativo JS'),
      '#description' => t('Regex pattern to match against current_path() to prevent loading Nativo Javascript. This is run through preg_match().'),
      '#default_value' => $config->get('admin_path_pattern'),
    );

    $form['help'] = array(
      '#markup' => t('NOTE: You can place a nativo.css in the theme(s)\'s folder,
        and it will be automatically included on the Nativo template page.'),
      '#prefix' => '<p class="warning">',
      '#suffix' => '</p>',
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('nativo.settings')
      ->set('html', $form_state->getValue('html'))
      ->set('js_enabled', $form_state->getValue('js_enabled'))
      ->set('admin_path_pattern', $form_state->getValue('admin_path_pattern'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
