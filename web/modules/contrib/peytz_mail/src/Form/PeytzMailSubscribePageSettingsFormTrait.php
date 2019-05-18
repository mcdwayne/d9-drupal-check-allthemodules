<?php

namespace Drupal\peytz_mail\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * A trait which contains common subscription form features.
 */
trait PeytzMailSubscribePageSettingsFormTrait {

  /**
   * PeytzMailer object.
   *
   * @var \Drupal\peytz_mail\PeytzMailer
   */
  protected $peytzMailer = NULL;

  /**
   * Gets Peytz Mail subscription page settings form.
   *
   * @param array $configuration
   *   Configuration array used to build the form.
   *
   * @return array
   *   Config form array.
   */
  public function getConfigForm(array $configuration) {

    $config_factory = \Drupal::service('config.factory');

    $form = [];

    $peytz_mail_settings_config = $config_factory->get('peytz_mail.settings');

    $link = Link::fromTextAndUrl(t('Peytz Mail settings'), Url::fromRoute('peytz_mail.settings'))->toRenderable();

    if (!$peytz_mail_settings_config->get('service_url')) {
      $form['missing_configuration_information'] = [
        '#markup' => t(
          'You need to configure @link first.',
          [
            '@link' => render($link),
          ]
        ),
      ];
      return $form;
    }

    $options = [];
    $mailinglists = $this->getMailingLists();

    foreach ($mailinglists as $list) {
      $options[$list['id']] = $list['title'];
    }

    $selected_options = [];
    if (!empty($configuration['newsletter_lists'])) {
      foreach ($configuration['newsletter_lists'] as $conf_list) {
        $selected_options[] = $conf_list['newsletter_machine_name'];
      }
    }

    $form['lists'] = [
      '#type' => 'details',
      '#title' => t('Newsletter lists'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    if (!empty($mailinglists)) {
      $form['mailing_lists'] = [
        '#type' => 'value',
        '#value' => $mailinglists,
        '#default_value' => $mailinglists,
      ];
      $form['lists']['newsletter_lists'] = [
        '#type' => 'select',
        '#title' => t('Newsletter lists'),
        '#multiple' => TRUE,
        '#description' => t('Select which newsletter lists this sign up box is connected to. If multiple lists are selected they will be presented as checkboxes/radios in the sign up box.'),
        '#options' => $options,
        '#default_value' => $selected_options,
        '#required' => TRUE,
      ];
      $form['lists']['hide_newsletter_lists'] = [
        '#type' => 'checkbox',
        '#title' => t('Hide newsletter lists from users.'),
        '#default_value' => $configuration['hide_newsletter_lists'],
        '#description' => t('Check this if the users are not allowed to select which lists to join.'),
      ];
      $form['lists']['multiple_newsletter_lists'] = [
        '#type' => 'checkbox',
        '#title' => t('Allow selection of more than one list.'),
        '#default_value' => $configuration['multiple_newsletter_lists'],
        '#description' => t('Check this if users are allowed to select more than one list to subscribe.'),
      ];
    }
    else {
      $form['lists']['no_lists'] = [
        '#markup' => t('No public newsletter lists found. Please check your Peytz Mail configuration.'),
      ];
    }

    $form['signup_settings'] = [
      '#type' => 'details',
      '#title' => t('Signup settings'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    // Title that will be displayed above the signup form.
    $form['signup_settings']['header'] = [
      '#type' => 'textfield',
      '#title' => t('Header'),
      '#default_value' => $configuration['header'],
    ];

    // Some introductory text that will be displayed above signup
    // form below the title.
    $form['signup_settings']['intro_text'] = [
      '#type' => 'textarea',
      '#title' => t('Welcoming text'),
      '#default_value' => $configuration['intro_text'],
    ];

    // Name field settings.
    $form['signup_settings']['name_field_setting'] = [
      '#type' => 'select',
      '#title' => t('Name field'),
      '#multiple' => FALSE,
      '#description' => t('Select to display the name field as a single full name field, as first name and last name fields, or not display at all.'),
      '#options' => [
        'none' => t('Disabled'),
        'single' => t('One field'),
        'double' => t('Two fields'),
      ],
      '#default_value' => $configuration['name_field_setting'],
    ];

    $form['signup_settings']['thank_you_page'] = [
      '#type' => 'textfield',
      '#title' => t('Thank you page'),
      '#description' => t('A page to be shown after the user subscribes for this newsletter. Leave empty for no redirect.'),
      '#default_value' => $configuration['thank_you_page'],
    ];

    $form['signup_settings']['confirmation_checkbox_text'] = [
      '#type' => 'textarea',
      '#title' => t('Confirmation checkbox text'),
      '#description' => t('Enter a text here if you want to request confirmation via checkbox that user does want to subscribe.'),
      '#default_value' => $configuration['confirmation_checkbox_text'],
    ];

    $form['signup_settings']['skip_confirm'] = [
      '#type' => 'checkbox',
      '#title' => t('Skip confirmation mails.'),
      '#default_value' => $configuration['skip_confirm'],
    ];

    $form['signup_settings']['skip_welcome'] = [
      '#type' => 'checkbox',
      '#title' => t('Skip welcome mails.'),
      '#default_value' => $configuration['skip_welcome'],
    ];

    $form['misc'] = [
      '#type' => 'details',
      '#title' => t('Misc'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    $form['misc']['ajax_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Submit the form using Ajax (no page reload).'),
      '#default_value' => $configuration['ajax_enabled'],
    ];

    $subscribe_link = Link::fromTextAndUrl(t('default url'), Url::fromRoute('peytz_mail.subscribe', [], ['alias' => TRUE]))->toRenderable();

    $form['misc']['subscribe_page_alias'] = [
      '#type' => 'textfield',
      '#title' => t('Subscribe page URL'),
      '#description' => t('Enter the address of the page that will show the signup form. That will be an alias to @link',
        [
          '@link' => render($subscribe_link),
        ]
      ),
    ];

    $form['misc']['use_subscription_queue'] = [
      '#type' => 'checkbox',
      '#title' => t('Use subscription queue.'),
      '#description' => t('If enabled subscription request will be queued and will be processed at a latter stage when cron runs.'),
      '#default_value' => $configuration['use_subscription_queue'],
    ];

    // A hidden value unless language.module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $languages = \Drupal::languageManager()->getLanguages();
      $language_options = [];
      foreach ($languages as $langcode => $language) {
        $language_options[$langcode] = $language->getName();
      }

      $form['langcode'] = [
        '#type' => 'select',
        '#title' => t('Language'),
        '#options' => $language_options,
        '#empty_value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        '#empty_option' => t('- None -'),
        '#default_value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        '#weight' => -10,
        '#description' => t('A path alias set for a specific language will always be used when displaying this page in that language, and takes precedence over path aliases set as <em>- None -</em>.'),
      ];
    }
    else {
      $form['langcode'] = [
        '#type' => 'value',
        '#value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ];
    }

    return $form;

  }

  /**
   * Validates Peytz Mail subscription page settings form.
   *
   * @param array $form
   *   Config form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Config FormStateInterface object.
   */
  public function validateConfigForm(array &$form, FormStateInterface $form_state) {

    $alias_storage = \Drupal::service('path.alias_storage');

    if ($form['#tree']) {
      // Block forms have $form['#tree'] set to TRUE and plugin form is set to
      // 'settings' in the main form. Validation however doesn't work well on
      // a form built on plugins such as block. But we'll just put the required
      // code here and most likely will work when core is fixed for the
      // following issues.
      // @see \Drupal\block\BlockForm::form().
      // @see https://www.drupal.org/node/2617466
      // @see https://www.drupal.org/node/2537732#comment-10538192
      $alias = &$form_state->getValue('misc')['subscribe_page_alias'];
      $thank_you_page = $form_state->getValue('signup_settings')['thank_you_page'];
      $alias_form_element = 'settings][misc][subscribe_page_alias';
      $thank_you_page_form_element = 'settings][signup_settings][thank_you_page';
    }
    else {
      // Configuratino forms.
      $alias = &$form_state->getValue('subscribe_page_alias');
      $thank_you_page = $form_state->getValue('thank_you_page');
      $alias_form_element = 'subscribe_page_alias';
      $thank_you_page_form_element = 'thank_you_page';
    }

    if (!empty($alias)) {
      // Trim the submitted value of whitespace and slashes. Ensure to not trim
      // the slash on the left side.
      $alias = rtrim(trim(trim($alias), ''), "\\/");

      // Language is only set if language.module is enabled, otherwise save
      // for all languages.
      $langcode = $form_state->getValue('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);

      if ($alias[0] !== '/') {
        $form_state->setErrorByName($alias_form_element, 'Subscribe page URL has to start with a slash.');
      }

      if ($alias_storage->aliasExists($alias, $langcode)) {
        $stored_alias = $alias_storage->load(['alias' => $alias, 'langcode' => $langcode]);
        if ($stored_alias['alias'] !== $alias) {
          // The alias already exists with different capitalization as the
          // default implementation of AliasStorageInterface::aliasExists is
          // case-insensitive.
          $form_state->setErrorByName($alias_form_element, t('Subscribe page URL %alias could not be added because it is already in use in this language with different capitalization: %stored_alias.', [
            '%alias' => $alias,
            '%stored_alias' => $stored_alias['alias'],
          ]));
        }
        else {
          $form_state->setErrorByName($alias_form_element, t('Subscribe page URL %alias is already in use in this language.', ['%alias' => $alias]));
        }
      }
    }

    $path_validator = \Drupal::service('path.validator');

    // Check if thank you page is valid.
    if (!empty($thank_you_page) && !$path_validator->isValid($thank_you_page)) {
      $form_state->setErrorByName($thank_you_page_form_element, t('Thank you page URL does not exist.'));
    }
  }

  /**
   * Submits Peytz Mail subscription page settings form.
   *
   * @param array $form
   *   Config form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   config FormStateInterface object.
   */
  abstract public function submitConfigForm(array &$form, FormStateInterface $form_state);

  /**
   * Retrieve mailing lists from Peytz Mail API.
   *
   * @param string $id
   *   Mailing List ID.
   *
   * @return array
   *   Available mailing lists.
   */
  protected function getMailingLists($id = '') {

    if (empty($id)) {
      $mailing_list_result = $this->peytzMailer->getMailingLists();
    }
    else {
      $mailing_list_result = $this->peytzMailer->getMailingList($id);
    }

    if (!empty($mailing_list_result) && is_string($mailing_list_result)) {
      $data = json_decode($mailing_list_result);
    }
    else {
      $data = (array) $mailing_list_result;
    }

    $list_data = [];
    if (isset($data['mailinglists'])) {
      $list_data = (array) $data['mailinglists'];
    }
    elseif (isset($data['mailinglist'])) {
      $list_data[] = (array) $data['mailinglist'];
    }

    $mailinglists = [];
    foreach ($list_data as $list) {
      $list = (array) $list;
      $mailinglists[$list['id']] = [
        'id' => Html::escape($list['id']),
        'title' => Html::escape($list['title']),
        'description' => Html::escape($list['description']),
        'weight' => Html::escape($list['public_position']),
      ];
    }

    return $mailinglists;

  }

}
