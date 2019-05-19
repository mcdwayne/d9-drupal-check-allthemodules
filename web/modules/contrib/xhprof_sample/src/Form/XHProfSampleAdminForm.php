<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\Form\XHProfSampleAdminForm.
 */

namespace Drupal\xhprof_sample\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for administering xhprof_sample settings.
 */
class XHProfSampleAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xhprof_sample_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xhprof_sample.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('xhprof_sample.settings');

    $form['xhprof_sample_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable sampling of requests.'),
      '#default_value' => $config->get('enabled'),
      '#description' => t('Enables sampling and collecting of the resulting data.'),
      '#disabled' => !extension_loaded('xhprof'),
    );

    $form['settings'] = array(
      '#title' => t('Sampling settings'),
      '#type' => 'fieldset',
      '#states' => array(
        'invisible' => array(
          'input[name="xhprof_sample_enabled"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['settings']['xhprof_sample_output_dir'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Output Directory'),
      '#required' => TRUE,
      '#description' => $this->t('Specify the output directory for sample files as a stream wrapper URI. <em>Note that using a public:// scheme for this setting may have security implications.</em>'),
      '#default_value' => $config->get('output_dir'),
    );

    $form['settings']['xhprof_sample_header_enable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Conditionally enable sampling using request header.'),
      '#description' => $this->t('If enabled, requests will only be sampled when the X-XHProf-Sample-Enable header is supplied and set to a truthy value.'),
      '#default_value' => $config->get('header_enable'),
    );

    $form['settings']['xhprof_sample_path_enable_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable sampling of specific paths'),
      '#options' => array(
        XHPROF_SAMPLE_ENABLE_PATH_NOTLISTED => $this->t('All paths except those listed'),
        XHPROF_SAMPLE_ENABLE_PATH_LISTED => $this->t('Only the listed paths'),
      ),
      '#default_value' => $config->get('path_enable_type'),
    );

    $description = $this->t("Specify pages to enable sampling on by using their paths. Leave blank for all pages. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array(
      '%blog' => 'blog',
      '%blog-wildcard' => 'blog/*',
      '%front' => '<front>',
    ));
    $form['settings']['xhprof_sample_path_enable_paths'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#description' => $description,
      '#default_value' => $config->get('path_enable_paths'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('xhprof_sample.settings')
      ->set('enabled', $form_state->getValue('xhprof_sample_enabled'))
      ->set('output_dir', $form_state->getValue('xhprof_sample_output_dir'))
      ->set('header_enable', $form_state->getValue('xhprof_sample_header_enable'))
      ->set('path_enable_type', $form_state->getValue('xhprof_sample_path_enable_type'))
      ->set('path_enable_paths', $form_state->getValue('xhprof_sample_path_enable_paths'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
