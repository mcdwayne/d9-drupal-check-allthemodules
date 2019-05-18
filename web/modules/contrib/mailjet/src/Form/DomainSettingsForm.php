<?php
/**
 *  * @file
 *  * Contains \Drupal\mailjet\Form\DomainSettingsForm.
 *  */

namespace Drupal\mailjet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DomainSettingsForm extends ConfigFormBase {

  public function getFormId() {

    return 'trusted_domain_form';

  }

  protected function getEditableConfigNames() {

    return ['config.trusted_domains'];

  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];
    $header = [
      'domain' => [
        'data' => t('Domain'),
      ],
      'enabled' => [
        'data' => t('Enabled'),
      ],
      'file_name' => [
        'data' => t('File Name'),
      ],
    ];


    $options = [];

    if ($domains = mailjet_user_domain_list()) {
      foreach ($domains as $domain) {
        if (is_object($domain['Email'])) {
          $email = $domain['Email']['Email'];
        }
        else {
          $email = $domain['Email'];
        }
        $options[$email] = [
          'domain' => str_replace('*@', '', $email),
          'enabled' => $domain['Status'],
          'file_name' => $domain['Filename'],
        ];
      }
    }

    $form['domains'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => t('There are no authorized domains.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check Status'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {


  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach ($form_state->getValue('domains') as $domain) {
      if ($domain) {
        mailjet_user_domain_status($domain);
      }
    }

  }
}