<?php

/**
 * @file
 * Contains Drupal\cookie_banner\Form\cookieBannerConfigForm.
 */

namespace Drupal\cookie_banner\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class cookieBannerConfigForm.
 *
 * @package Drupal\cookie_banner\Form
 */
class cookieBannerConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cookie_banner.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cookie_banner_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cookie_banner.settings');

    $form['cookie_banner']['use_cookie_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cookie policy main message'),
      '#default_value' => $config->get('use_cookie_message'),
      '#size' => 62,
      '#maxlength' => 220,
      '#required' => TRUE,
      '#description' => $this->t('Enter the message to warn the user about the site using cookies.'),
    );

    $form['cookie_banner']['more_info'] = array(
      '#type' => 'fieldset',
      '#title' => t('Cookie Policy link'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['cookie_banner']['more_info']['more_info_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#default_value' => $config->get('more_info_url'),
      '#size' => 60,
      '#maxlength' => 220,
      '#required' => TRUE,
      '#description' => $this->t('Enter link to your privacy policy or other page that will explain cookies to your users. For external links prepend http://'),
    );

    $form['cookie_banner']['more_info']['more_info_message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $config->get('more_info_message'),
      '#size' => 60,
      '#maxlength' => 220,
      '#required' => TRUE,
      '#description' => $this->t('Enter the text for the Privacy Policy link.'),
    );

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

    $this->config('cookie_banner.settings')
      ->set('use_cookie_message', $form_state->getValue('use_cookie_message'))
      ->set('more_info_url', $form_state->getValue('more_info_url'))
      ->set('more_info_message', $form_state->getValue('more_info_message'))
      ->save();
  }

}
