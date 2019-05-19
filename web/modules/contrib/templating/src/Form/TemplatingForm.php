<?php

namespace Drupal\templating\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\templating\Templating;

/**
 * Class TemplatingForm.
 */
class TemplatingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'templating.templating',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'templating_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('templating.templating');

    $form['template_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template Path'),
      '#description' => $this->t('Default Location of your template list , for example: /modules/custom/templating'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('template_path'),
    ];
    $form['allowed_to_edit_content_via_admi'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allowed to edit content via Admin'),
      '#description' => $this->t('It is not advice this option , because any error in file will may be crash your website'),
      '#default_value' => $config->get('allowed_to_edit_content_via_admi'),
    ];
    if($config->get('template_path')){
      $template = new Templating();
      $form['content_file'] = array(
        '#type'   => 'textarea',
        '#title' => $this->t('File Template config content'),
        '#value'  => $template->getConfigTemplateFile(),
      );
      $form['content_file']['#attributes']['readonly'] = 'true';
      $form['actions']['another_button'] = array(
        '#type'   => 'submit',
        '#value'  => 'Import File Templating',
        '#submit' => array('templating_import_custom_form_submit')
      );
    }
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

    $template_module = $form_state->getValue('template_path');
    $template_class = new \Drupal\templating\Templating();
    $template_class->generateConfigTemplating($template_module);

    $this->config('templating.templating')
      ->set('template_path', $form_state->getValue('template_path'))
      ->set('allowed_to_edit_content_via_admi', $form_state->getValue('allowed_to_edit_content_via_admi'))
      ->save();
  }

}
