<?php

namespace Drupal\tacjs\Form\Steps;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class StepOneForm.
 *
 * @package Drupal\tacjs\Form
 */
class StepSettingsTacjsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tacjs_configuration_three_step';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');

    $form['tacjs_settings'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Settings Tarte au Citron'),
    ];

    $options = array(
      'false' => t('Désactivé'),
      'true' => t('Activer'),
    );
    // Options.
    $form['tacjs_settings']['cookie_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie name'),
      '#description' => t('Cookie name'),
      '#default_value' => $config->get('cookie_name'),
    );

    $form['tacjs_settings']['high_privacy'] = [
      '#type' => 'radios',
      '#title' => t('HighPrivacy'),
      '#description' => t('Désactiver le consentement implicite (en naviguant)'),
      '#options' => $options,
      '#default_value' => $config->get('high_privacy'),
    ];
    $form['tacjs_settings']['orientation'] = [
      '#type' => 'textfield',
      '#title' => t('Orientation'),
      '#description' => t('le bandeau doit être en haut (top) ou en bas (bottom)'),
      '#default_value' => $config->get('orientation'),
    ];
    $form['tacjs_settings']['adblocker'] = [
      '#type' => 'radios',
      '#title' => t('Adblocker'),
      '#description' => t('Afficher un message si un adblocker est détecté '),
      '#options' => $options,
      '#default_value' => $config->get('adblocker'),
    ];
    $form['tacjs_settings']['show_alertSmall'] = [
      '#type' => 'radios',
      '#title' => t('showAlertSmall'),
      '#description' => t('afficher le petit bandeau en bas à droite ? '),
      '#options' => $options,
      '#default_value' => $config->get('show_alertSmall'),
    ];
    $form['tacjs_settings']['cookieslist'] = [
      '#type' => 'radios',
      '#title' => t('cookieslist'),
      '#description' => t('Afficher la liste des cookies installés ?'),
      '#options' => $options,
      '#default_value' => $config->get('cookieslist'),
    ];
    $form['tacjs_settings']['removeCredit'] = [
      '#type' => 'radios',
      '#title' => t('removeCredit'),
      '#description' => t('supprimer le lien vers la source ? '),
      '#options' => $options,
      '#default_value' => $config->get('removeCredit'),
    ];
    $form['tacjs_settings']['handleBrowserDNTRequest'] = [
      '#type' => 'radios',
      '#title' => t('handleBrowserDNTRequest'),
      '#description' => t('Deny everything if DNT is on '),
      '#options' => $options,
      '#default_value' => $config->get('handleBrowserDNTRequest'),
    ];
    // Actions.
    $form['actions']['previous'] = [
      '#type' => 'link',
      '#title' => $this->t('Previous'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 0,
      '#url' => Url::fromRoute('tacjs.admin_settings_form'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');
    // Save values.
    $config->set('cookie_name', $form_state->getValue('cookie_name'));
    $config->set('high_privacy', $form_state->getValue('high_privacy'));
    $config->set('orientation', $form_state->getValue('orientation'));
    $config->set('adblocker', $form_state->getValue('adblocker'));
    $config->set('show_alertSmall', $form_state->getValue('show_alertSmall'));
    $config->set('removeCredit', $form_state->getValue('removeCredit'));
    $config->set('cookieslist', $form_state->getValue('cookieslist'));
    $config->set('orientation', $form_state->getValue('orientation'));
    $config->set('handleBrowserDNTRequest', $form_state->getValue('handleBrowserDNTRequest'));
    $config->save();
    // Redirect to step one.
    $form_state->setRedirect('tacjs.admin_settings_form');
  }

}
