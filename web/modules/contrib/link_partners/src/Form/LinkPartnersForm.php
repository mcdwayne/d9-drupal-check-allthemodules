<?php

namespace Drupal\link_partners\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class LinkPartnersForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'link_partners_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'link_partners.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('link_partners.settings');
    //kint($config);
    /*    $form['your_message'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Your message'),
          '#default_value' => $config->get('your_message'),
        );*/

    $form = [];

    $form['linkfeed']['l_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Linkfeed'),
      '#description' => $this->t('Check this checkbox for show links with @partner', ['@partner' => 'Linkfeed']),
      '#default_value' => $config->get('linkfeed.status'),
    ];

    $form['trustlink']['t_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('TrustLink'),
      '#description' => $this->t('Check this checkbox for show links with @partner', ['@partner' => 'Trustlink']),
      '#default_value' => $config->get('trustlink.status'),
    ];

    $form['sape']['s_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sape'),
      '#description' => $this->t('Check this checkbox for show links with @partner', ['@partner' => 'Sape']),
      '#default_value' => $config->get('sape.status'),
    ];

    $form['mainlink']['m_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Mainlink'),
      '#description' => $this->t('Check this checkbox for show links with @partner', ['@partner' => 'Mainlink']),
      '#default_value' => $config->get('mainlink.status'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Configuration success saved'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('link_partners.settings');

    $config->set('linkfeed.status', $values['l_checkbox'])->save();
    $config->set('trustlink.status', $values['t_checkbox'])->save();
    $config->set('sape.status', $values['s_checkbox'])->save();
    $config->set('mainlink.status', $values['m_checkbox'])->save();

  }

}
