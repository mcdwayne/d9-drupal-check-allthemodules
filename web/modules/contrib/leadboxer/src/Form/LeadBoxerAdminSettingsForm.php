<?php

namespace Drupal\leadboxer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure LeadBoxer settings for this site.
 */
class LeadBoxerAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leadboxer_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['leadboxer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('leadboxer.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['leadboxer_dataset_id'] = [
      '#default_value' => $config->get('dataset_id'),
      '#description' => $this->t('If you have not done so yet, start by registering for a free trial at <a href=":leadboxer">LeadBoxer</a>. You will receive an email which contains your dataset ID. To get things setup you need to copy/paste your dataset ID in the field above and click to save your settings.', [
        ':leadboxer' => 'https://www.leadboxer.com?utm_source=drupal-plugin',
      ]),
      '#maxlength' => 40,
      '#required' => TRUE,
      '#size' => 50,
      '#title' => $this->t('LeadBoxer dataset ID'),
      '#type' => 'textfield',
    ];

    // Visibility settings.
    $form['tracking_scope'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Lead Pixel scope'),
      '#attached' => [
        'library' => [
          'leadboxer/leadboxer.admin',
        ],
      ],
    ];

    $account = \Drupal::currentUser();
    $visibility_request_path_pages = $config->get('visibility.request_path_pages');
    $form['tracking']['page_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
    ];

    if ($config->get('visibility.request_path_mode') == 2) {
      $form['tracking']['page_visibility_settings'] = [];
      $form['tracking']['page_visibility_settings']['leadboxer_visibility_request_path_mode'] = [
        '#type' => 'value',
        '#value' => 2,
      ];
      $form['tracking']['page_visibility_settings']['leadboxer_visibility_request_path_pages'] = [
        '#type' => 'value',
        '#value' => $visibility_request_path_pages,
      ];
    }
    else {
      $form['tracking']['page_visibility_settings']['leadboxer_visibility_request_path_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Load the Lead Pixel to specific pages'),
        '#options' => $options = [
          t('Every page except the listed pages'),
          t('The listed pages only'),
        ],
        '#default_value' => $config->get('visibility.request_path_mode'),
      ];
      $form['tracking']['page_visibility_settings']['leadboxer_visibility_request_path_pages'] = [
        '#type' => 'textarea',
        '#title' => $title = t('Pages'),
        '#title_display' => 'invisible',
        '#default_value' => !empty($visibility_request_path_pages) ? $visibility_request_path_pages : '',
        '#description' => t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", [
          '%blog' => '/blog',
          '%blog-wildcard' => '/blog/*',
          '%front' => '<front>',
        ]),
        '#rows' => 10,
      ];
    }

    // Render the role overview.
    $visibility_user_role_roles = $config->get('visibility.user_role_roles');

    $form['tracking']['role_visibility_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
    ];

    $form['tracking']['role_visibility_settings']['leadboxer_visibility_user_role_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Load the Lead Pixel for specific roles'),
      '#options' => [
        t('Add to the selected roles only'),
        t('Add to every role except the selected ones'),
      ],
      '#default_value' => $config->get('visibility.user_role_mode'),
    ];
    $form['tracking']['role_visibility_settings']['leadboxer_visibility_user_role_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_user_role_roles) ? $visibility_user_role_roles : [],
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Trim some text values.
    $form_state->setValue('leadboxer_dataset_id', trim($form_state->getValue('leadboxer_dataset_id')));
    $form_state->setValue('leadboxer_visibility_request_path_pages', trim($form_state->getValue('leadboxer_visibility_request_path_pages')));
    $form_state->setValue('leadboxer_visibility_user_role_roles', array_filter($form_state->getValue('leadboxer_visibility_user_role_roles')));

    // Verify that every path is prefixed with a slash, but don't check PHP
    // code snippets.
    if ($form_state->getValue('leadboxer_visibility_request_path_mode') != 2) {
      $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('leadboxer_visibility_request_path_pages'));
      foreach ($pages as $page) {
        if (strpos($page, '/') !== 0 && $page !== '<front>') {
          $form_state->setErrorByName('leadboxer_visibility_request_path_pages', t('Path "@page" not prefixed with slash.', ['@page' => $page]));
          // Drupal forms show one error only.
          break;
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('leadboxer.settings');
    $config->set('dataset_id', $form_state->getValue('leadboxer_dataset_id'))
      ->set('visibility.request_path_mode', $form_state->getValue('leadboxer_visibility_request_path_mode'))
      ->set('visibility.request_path_pages', $form_state->getValue('leadboxer_visibility_request_path_pages'))
      ->set('visibility.user_role_mode', $form_state->getValue('leadboxer_visibility_user_role_mode'))
      ->set('visibility.user_role_roles', $form_state->getValue('leadboxer_visibility_user_role_roles'))
      ->set('visibility.user_account_mode', $form_state->getValue('leadboxer_visibility_user_account_mode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
