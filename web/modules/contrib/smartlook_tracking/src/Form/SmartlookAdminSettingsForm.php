<?php

namespace Drupal\smartlook_tracking\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Smartlook settings for this site.
 */
class SmartlookAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartlook_tracking_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smartlook_tracking.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smartlook_tracking.settings');

    $form['general'] = [
      '#type'  => 'details',
      '#title' => $this->t('General settings'),
      '#open'  => TRUE,
    ];

    $form['general']['smartlook_tracking_account'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Smartlook ID'),
      '#description'   => $this->t('This ID is unique to each site you want to track separately. To get a Smartlook ID, <a href=":link">register your site with Smartlook</a>.', [
        ':link' => 'https://www.smartlook.com',
      ]),
      '#maxlength'     => 40,
      '#size'          => 40,
      '#default_value' => $config->get('account'),
      '#required'      => TRUE,
    ];

    // Visibility settings.
    $form['tracking_scope'] = [
      '#type'     => 'vertical_tabs',
      '#title'    => $this->t('Advanced settings'),
      '#attached' => [
        'library' => [
          'smartlook_tracking/smartlook_tracking.admin',
        ],
      ],
    ];

    // Page specific visibility configurations.
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');

    $form['tracking']['page_visibility_settings'] = [
      '#type'  => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
    ];

    $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
      '%blog'          => '/blog',
      '%blog-wildcard' => '/blog/*',
      '%front'         => '<front>'
    ]);

    $form['tracking']['page_visibility_settings']['smartlook_tracking_visibility_request_path_mode'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Add tracking to specific pages'),
      '#options'       => [
        $this->t('Every page except the listed pages'),
        $this->t('The listed pages only'),
      ],
      '#default_value' => $config->get('visibility.request_path_mode'),
    ];

    $form['tracking']['page_visibility_settings']['smartlook_tracking_visibility_request_path_pages'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Pages'),
      '#title_display' => 'invisible',
      '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
      '#description'   => $description,
      '#rows'          => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Trim some text values.
    $form_state->setValue('smartlook_tracking_account', trim($form_state->getValue('smartlook_tracking_account')));
    $form_state->setValue('smartlook_tracking_visibility_request_path_pages', trim($form_state->getValue('smartlook_tracking_visibility_request_path_pages')));

    if (strlen($form_state->getValue('smartlook_tracking_account')) != 40) {
      $form_state->setErrorByName('smartlook_tracking_account', $this->t('A valid Smartlook ID must be 40 characters long.'));
    }

    // Verify that every path is prefixed with a slash, but don't check PHP
    // code snippets.
    $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('smartlook_tracking_visibility_request_path_pages'));
    foreach ($pages as $page) {
      if (strpos($page, '/') !== 0 && $page !== '<front>') {
        $form_state->setErrorByName('smartlook_tracking_visibility_request_path_pages', $this->t('Path "@page" not prefixed with slash.', [
          '@page' => $page,
        ]));
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $smartlook_id = $form_state->getValue('smartlook_tracking_account');

    $config = $this->config('smartlook_tracking.settings');
    $config->set('account', $smartlook_id);
    $config->set('visibility.request_path_mode', $form_state->getValue('smartlook_tracking_visibility_request_path_mode'));
    $config->set('visibility.request_path_pages', $form_state->getValue('smartlook_tracking_visibility_request_path_pages'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
