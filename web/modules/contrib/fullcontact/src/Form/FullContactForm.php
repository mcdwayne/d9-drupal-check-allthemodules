<?php

namespace Drupal\fullcontact\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\fullcontact\Controller\FullContactController;

/**
 * Configure Full contact settings for this site.
 *
 * @internal
 */
class FullContactForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fullcontact_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fullcontact.adminsettings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('fullcontact.adminsettings');
    $social_setting = $config->get('fullcontact_social_settings');

    $form['fullcontact_description'] = [
      '#type' => 'item',
      '#markup' => '<div><div><strong>' . $this->t("Intructions to get the FullContact Api ID.") . '</strong></div><ul><li>' . t("Please read document to create fullcontact api <a href='https://www.fullcontact.com/developer/docs/' target='_blank'>click here</a>") . '</li> <li>' . t('To get FullContact Api please create an account on the <a href="https://www.fullcontact.com/developer/try-fullcontact/" target="_blank">fullcontact.com</a>') . '</li><li>' . t("You will get an api of Fullcontact on your registered email id.") . '</li><li>' . t("Please Fill the Fullcontact Api Id in the FullContact API ID field.") . '</li></div>',
    ];

    $form['fullcontact_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('FullContact Api Setting'),
      '#collapsible' => TRUE,
    ];

    $form['fullcontact_settings']['fullcontact_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FullContact APP ID'),
      '#default_value' => $config->get('fullcontact_api'),
      '#required' => TRUE,
    ];

    $form['fullcontact_social_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('FullContact Social Enable/Disable'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];

    $socialarray = new FullContactController();
    $_socialarray = $socialarray->fullcontactGetSocialArray();

    foreach ($_socialarray as $key => $value) {
      $form['fullcontact_social_settings'][$key] = [
        '#type' => 'checkbox',
        '#title' => '<strong>' . $value . '</strong>',
        '#default_value' => $social_setting[$key],
      ];
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

    $this->config('fullcontact.adminsettings')
      ->set('fullcontact_api', $form_state->getValue('fullcontact_api'))
      ->set('fullcontact_social_settings', $form_state->getValue('fullcontact_social_settings'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
