<?php

namespace Drupal\peytz_mail\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\peytz_mail\Form\PeytzMailSubscribePageSettingsFormTrait;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\peytz_mail\PeytzMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Peytz Mail: Sign up' block.
 *
 * @Block(
 *   id = "peytz_mail_signup",
 *   admin_label = @Translation("Peytz Mail: Sign up")
 * )
 */
class PeytzMailSignUpBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use PeytzMailSubscribePageSettingsFormTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory, PeytzMailer $peytz_mailer) {
    $this->peytzMailer = $peytz_mailer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('peytz_mail.peytzmailer')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $account->hasPermission('access peytz_mail signup') ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $config = \Drupal::config('peytz_mail.settings');

    // Peytz mail service url is not set.
    if (!$config->get('service_url')) {
      return [];
    }

    $config = \Drupal::config('peytz_mail.subscribe_page_settings');

    $mailinglists = $this->getMailingLists();
    $selected_options = [];
    $newsletter_lists = $config->get('lists.newsletter_lists');
    if (!empty($newsletter_lists)) {
      foreach ($newsletter_lists as $conf_list) {
        $selected_options[] = $conf_list['newsletter_machine_name'];
      }
    }

    return [
      'mailing_lists' => $mailinglists,
      'newsletter_lists' => $selected_options,
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
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $this->getConfigForm($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $this->validateConfigForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->submitConfigForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    $lists = $form_state->getValue('lists');
    $signup_settings = $form_state->getValue('signup_settings');
    $misc = $form_state->getValue('misc');

    $newsletter_list = $lists['newsletter_lists'];

    $all_mailing_lists = $form_state->getValue('mailing_lists');
    $selected_mailing_list = [];
    if (!empty($newsletter_list)) {
      foreach ($newsletter_list as $id => $list) {
        $selected_mailing_list[$id] = [
          'newsletter_machine_name' => $id,
          'newsletter_name' => $all_mailing_lists[$id]['title'],
          'newsletter_description' => $all_mailing_lists[$id]['description'],
        ];
      }
    }

    $this->configuration['newsletter_lists'] = $selected_mailing_list;
    $this->configuration['hide_newsletter_lists'] = $lists['hide_newsletter_lists'];
    $this->configuration['multiple_newsletter_lists'] = $lists['multiple_newsletter_lists'];
    $this->configuration['header'] = $signup_settings['header'];
    $this->configuration['intro_text'] = $signup_settings['intro_text'];
    $this->configuration['name_field_setting'] = $signup_settings['name_field_setting'];
    $this->configuration['thank_you_page'] = $signup_settings['thank_you_page'];
    $this->configuration['confirmation_checkbox_text'] = $signup_settings['confirmation_checkbox_text'];
    $this->configuration['skip_confirm'] = $signup_settings['skip_confirm'];
    $this->configuration['skip_welcome'] = $signup_settings['skip_welcome'];
    $this->configuration['ajax_enabled'] = $misc['ajax_enabled'];
    $this->configuration['subscribe_page_alias'] = $misc['subscribe_page_alias'];
    $this->configuration['use_subscription_queue'] = $misc['use_subscription_queue'];

    // Language is only set if language.module is enabled, otherwise save
    // for all languages.
    $langcode = $form_state->getValue('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $source = Url::fromRoute('peytz_mail.subscribe')->toString();
    $alias = $misc['subscribe_page_alias'];

    if (!empty($alias)) {
      \Drupal::service('path.alias_storage')->save($source, $alias, $langcode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = \Drupal::config('peytz_mail.subscribe_page_settings');

    if (empty($config->get('newsletter_lists'))) {
      if (\Drupal::currentUser()->hasPermission('administer peytz_mail configuration')) {
        $link = Link::fromTextAndUrl(t('Peytz Mail settings'), Url::fromRoute('peytz_mail.subscribe_page_settings'))->toRenderable();
        return ['#markup' => t('You need to configure @link first.', ['@link' => render($link)])];
      }
      else {
        return [];
      }
    }
    elseif (empty($this->configuration['newsletter_lists'])) {
      if (\Drupal::currentUser()->hasPermission('administer peytz_mail configuration')) {
        return ['#markup' => t('You need to setup newsletter lists in the block configuration.')];
      }
      else {
        return [];
      }
    }

    return \Drupal::formBuilder()->getForm('Drupal\peytz_mail\Form\PeytzMailSignUpBlockForm', $this->configuration);
  }

}
