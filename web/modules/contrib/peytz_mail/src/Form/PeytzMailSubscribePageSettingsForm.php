<?php

namespace Drupal\peytz_mail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\peytz_mail\PeytzMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents the Peytz Mail subscription page settings form.
 */
class PeytzMailSubscribePageSettingsForm extends ConfigFormBase {

  use PeytzMailSubscribePageSettingsFormTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, PeytzMailer $peytz_mailer) {
    $this->peytzMailer = $peytz_mailer;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('peytz_mail.peytzmailer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'peytz_mail_subscribe_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['peytz_mail.subscribe_page_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('peytz_mail.subscribe_page_settings');

    $configuration = [
      'newsletter_lists' => $config->get('newsletter_lists'),
      'hide_newsletter_lists' => $config->get('lists.hide_newsletter_lists'),
      'multiple_newsletter_lists' => $config->get('lists.multiple_newsletter_lists'),
      'header' => $config->get('signup_settings.header'),
      'intro_text' => $config->get('signup_settings.intro_text'),
      'name_field_setting' => $config->get('signup_settings.name_field_setting'),
      'thank_you_page' => $config->get('signup_settings.thank_you_page'),
      'confirmation_checkbox_text' => $config->get('signup_settings.confirmation_checkbox_text'),
      'skip_confirm' => $config->get('signup_settings.skip_confirm'),
      'skip_welcome' => $config->get('signup_settings.skip_welcome'),
      'ajax_enabled' => $config->get('misc.ajax_enabled'),
      'subscribe_page_alias' => $config->get('misc.subscribe_page_alias'),
      'use_subscription_queue' => $config->get('misc.use_subscription_queue'),
    ];

    $form = $this->getConfigForm($configuration);

    if (isset($form['missing_configuration_information'])) {
      // Required config information is missing.
      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateConfigForm($form, $form_state);
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitConfigForm($form, $form_state);
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    $newsletter_list = $form_state->getValue('newsletter_lists');
    $all_mailing_lists = $form_state->getValue('mailing_lists');
    $selected_mailing_list = [];
    foreach ($newsletter_list as $id => $list) {
      $selected_mailing_list[$id] = [
        'newsletter_machine_name' => $id,
        'newsletter_name' => $all_mailing_lists[$id]['title'],
        'newsletter_description' => $all_mailing_lists[$id]['description'],
      ];
    }

    $this->config('peytz_mail.subscribe_page_settings')
      ->set('newsletter_lists', $selected_mailing_list)
      ->set('lists.hide_newsletter_lists', $form_state->getValue('hide_newsletter_lists'))
      ->set('lists.multiple_newsletter_lists', $form_state->getValue('multiple_newsletter_lists'))
      ->set('signup_settings.header', $form_state->getValue('header'))
      ->set('signup_settings.intro_text', $form_state->getValue('intro_text'))
      ->set('signup_settings.name_field_setting', $form_state->getValue('name_field_setting'))
      ->set('signup_settings.thank_you_page', $form_state->getValue('thank_you_page'))
      ->set('signup_settings.confirmation_checkbox_text', $form_state->getValue('confirmation_checkbox_text'))
      ->set('signup_settings.skip_confirm', $form_state->getValue('skip_confirm'))
      ->set('signup_settings.skip_welcome', $form_state->getValue('skip_welcome'))
      ->set('misc.ajax_enabled', $form_state->getValue('ajax_enabled'))
      ->set('misc.subscribe_page_alias', $form_state->getValue('subscribe_page_alias'))
      ->set('misc.use_subscription_queue', $form_state->getValue('use_subscription_queue'))
      ->save();

    // Language is only set if language.module is enabled, otherwise save for
    // all languages.
    $langcode = $form_state->getValue('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $source = Url::fromRoute('peytz_mail.subscribe')->toString();
    $alias = $form_state->getValue('subscribe_page_alias');

    if (!empty($alias)) {
      \Drupal::service('path.alias_storage')->save($source, $alias, $langcode);
    }
  }

}
