<?php

/**
 * @file
 * Contains \Drupal\zohosalesiq\Form\SettingsForm.
 */

namespace Drupal\zohosalesiq\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides the path admin overview form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zohosalesiq_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['zohosalesiq.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zohosalesiq.settings');
    $form['zohosalesiq_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Chat Widget'),
      '#default_value' => $config->get('zohosalesiq_enabled')
    ];
    $link = Link::fromTextAndUrl(t('Click here to sign up'), Url::fromUri('https://salesiq.zoho.com/register.sas?source=Drupal.salesiqpluginconfig',array('attributes' => array('target' => '_blank'))))->toString();
    $description = t('@link and get the widget code..', array("@link" => $link));
    $form['zohosalesiq_widget_code'] = [
      '#type' => 'textarea',
      '#title' => t('Zoho SalesIQ widget code'),
      '#default_value' => $config->get('zohosalesiq_widget_code'),
      '#description' => $description,
      '#required' => TRUE,
      '#rows' => 6,
      '#cols' => 40
    ];
    $form['zohosalesiq_show_in'] = [
    '#type' => 'radios',
    '#title' => t('Show Chat Widget in'),
    '#options' => array(t('View Pages only'), t('All pages (View & Edit)')),
    '#default_value' => $config->get('zohosalesiq_show_in'),
  ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $code = $values['zohosalesiq_widget_code'];
    $config = $this->config('zohosalesiq.settings');
    $config->set('zohosalesiq_widget_code', $code)
      ->set('zohosalesiq_enabled', $values['zohosalesiq_enabled'])
      ->set('zohosalesiq_show_in',$values['zohosalesiq_show_in'])
      ->save();
  
    parent::submitForm($form, $form_state);
  }

}
