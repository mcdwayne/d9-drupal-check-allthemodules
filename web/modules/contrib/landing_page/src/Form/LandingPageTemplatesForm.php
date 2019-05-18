<?php

namespace Drupal\landing_page\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Landing page form class.
 */
class LandingPageTemplatesForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'landing_page_templates_form';
  }

  /**
   * Callback from webform_insightly_menu().
   *
   * Form to processes all template files.
   * To configure custom path and rescan the template files.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('landing_page.settings');
    $form['template_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to templates'),
      '#default_value' => $config->get('landing_page_templates_custom_path'),
      '#description' => $this->t('Enter the custom path to template folder that holds landing page tpl files, Eg.,
      "core/themes/bartik/templates/landing_pages/". By default template folders of active theme and
      template folder of landing page module will be scanned. Naming of the template should be
      landing-page--{type}.html.twig'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Re-scan template files'),
    ];

    return $form;
  }

  /**
   * Callback for admin configuration form.
   *
   * If a custom path is set, set it into a variable.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom_path = $form_state->getValue('template_path');
    $this->config('landing_page.settings')
      ->set('landing_page_templates_custom_path', $custom_path)
      ->save();
    // Get list of all landing page template files.
    $template_list = landing_page_get_templates($custom_path);
    $this->config('landing_page.settings')
      ->set('landing_page_templates_list', $template_list)
      ->save();

    drupal_set_message(t('Template configuration is up to date.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'landing_page.settings',
    ];
  }

}
