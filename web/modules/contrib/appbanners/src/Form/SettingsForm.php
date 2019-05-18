<?php

namespace Drupal\appbanners\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appbanners_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'appbanners.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('appbanners.settings');

    $form['ios'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('iOS'),
      '#description' => $this->t('See the <a href=":apple">Safari Web Content Guide</a> for more information on App Banners in iOS',
        [
          ':apple' => 'https://developer.apple.com/library/content/documentation/AppleApplications/Reference/SafariWebContent/PromotingAppswithAppBanners/PromotingAppswithAppBanners.html',
        ]),
    ];

    $form['ios']['ios_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#default_value' => $config->get('ios_app_id'),
    ];

    $form['ios']['ios_affiliate_data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Affiliate Data'),
      '#default_value' => $config->get('ios_affiliate_data'),
    ];

    $form['ios']['ios_app_argument'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Argument'),
      '#default_value' => $config->get('ios_app_argument'),
    ];

    $form['android'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Android'),
      '#description' => $this->t('See the <a href=":android">Google Developer Fundamentals</a> for more information on Native App Install Banners in Android',
        [
          ':android' => 'https://developers.google.com/web/fundamentals/app-install-banners/#native_app_install_banners',
        ]),
    ];

    $form['android']['android_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#default_value' => $config->get('android_app_id'),
    ];

    $form['android']['android_short_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short Name'),
      '#default_value' => $config->get('android_short_name'),
    ];

    $form['android']['android_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $config->get('android_name'),
    ];

    $form['android']['android_icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon (192px)'),
      '#default_value' => $config->get('android_icon'),
    ];

    $form['android']['android_icon_large'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon (512px)'),
      '#default_value' => $config->get('android_icon_large'),
    ];

    $form['visibility'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Pages'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['visibility']['admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include app banners tags on admin pages?'),
      '#default_value' => $config->get('admin', FALSE),
    ];

    $form['visibility']['visibility'] = [
      '#type' => 'radios',
      '#options' => [
        'exclude' => $this->t('All pages except those listed'),
        'include' => $this->t('Only the listed pages'),
      ],
      '#default_value' => $config->get('visibility'),
    ];

    $form['visibility']['pages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Include script on specific pages'),
      '#default_value' => $config->get('pages'),
      '#description' => $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.",
        [
          '%blog' => '/blog',
          '%blog-wildcard' => '/blog/*',
          '%front' => '<front>',
        ]),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('appbanners.settings')
      ->set('ios_app_id', $values['ios_app_id'])
      ->set('ios_affiliate_data', $values['ios_affiliate_data'])
      ->set('ios_app_argument', $values['ios_app_argument'])

      ->set('android_app_id', $values['android_app_id'])
      ->set('android_short_name', $values['android_short_name'])
      ->set('android_name', $values['android_name'])
      ->set('android_icon', $values['android_icon'])
      ->set('android_icon_large', $values['android_icon_large'])

      ->set('admin', $values['admin'])
      ->set('visibility', $values['visibility'])
      ->set('pages', $values['pages'])

      ->save();

    parent::submitForm($form, $form_state);

  }

}
