<?php

namespace Drupal\domain_lang\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain_lang\DomainLangHandlerInterface;
use Drupal\language\Form\NegotiationUrlForm;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the URL language negotiation method for this site.
 */
class DomainLangNegotiationUrlForm extends NegotiationUrlForm {

  /**
   * The domain lang handler.
   *
   * @var \Drupal\domain_lang\DomainLangHandlerInterface
   */
  protected $domainLangHandler;

  /**
   * Language negotiation config name for current active domain.
   *
   * @var string
   */
  protected $languageNegotiationConfig;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\domain_lang\DomainLangHandlerInterface $domain_lang_handler
   *   The domain lang handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, DomainLangHandlerInterface $domain_lang_handler) {
    parent::__construct($config_factory, $language_manager);
    $this->domainLangHandler = $domain_lang_handler;
    $this->languageNegotiationConfig = $this->domainLangHandler->getDomainConfigName('language.negotiation');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('domain_lang.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.negotiation', $this->languageNegotiationConfig];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->languageNegotiationConfig);
    $form = parent::buildForm($form, $form_state);

    // Fill with initial values on first page visit.
    if (!$config->get('url.source')) {
      $config->set('url.source', $this->config('language.negotiation')->get('url.source'));
    }
    if (!$config->get('url.prefixes')) {
      $config->set('url.prefixes', $this->config('language.negotiation')->get('url.prefixes'));
    }
    if (!$config->get('url.domains')) {
      $config->set('url.domains', $this->config('language.negotiation')->get('url.domains'));
    }

    if (isset($form['language_negotiation_url_part'])) {
      $form['language_negotiation_url_part']['#default_value'] = $config->get('url.source');
    }

    $domain = $this->domainLangHandler->getDomainFromUrl();
    $languages = $this->languageManager->getLanguages();
    $prefixes = $config->get('url.prefixes');
    $domains = $config->get('url.domains');

    foreach ($languages as $langcode => $language) {
      if (isset($form['prefix'], $form['prefix'][$langcode])) {
        $form['prefix'][$langcode]['#default_value'] = isset($prefixes[$langcode]) ? $prefixes[$langcode] : '';
        $form['prefix'][$langcode]['#field_prefix'] = $domain->getPath();
      }
      if (isset($form['domain'], $form['domain'][$langcode])) {
        $form['domain'][$langcode]['#default_value'] = isset($domains[$langcode]) ? $domains[$langcode] : '';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $languages = $this->languageManager->getLanguages();

    // Count repeated values for uniqueness check.
    $count = array_count_values($form_state->getValue('prefix'));
    $default_langcode = $this->config($this->languageNegotiationConfig)->get('selected_langcode');
    if ($default_langcode == LanguageInterface::LANGCODE_SITE_DEFAULT) {
      $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    foreach ($languages as $langcode => $language) {
      $value = $form_state->getValue(array('prefix', $langcode));
      if ($value === '') {
        if (!($default_langcode == $langcode) && $form_state->getValue('language_negotiation_url_part') == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
          // Throw a form error if the prefix is blank for a non-default language,
          // although it is required for selected negotiation type.
          $form_state->setErrorByName("prefix][$langcode", $this->t('The prefix may only be left blank for the <a href=":url">selected detection fallback language.</a>', [
            ':url' => $this->getUrlGenerator()->generate('language.negotiation_selected'),
          ]));
        }
      }
      elseif (strpos($value, '/') !== FALSE) {
        // Throw a form error if the string contains a slash,
        // which would not work.
        $form_state->setErrorByName("prefix][$langcode", $this->t('The prefix may not contain a slash.'));
      }
      elseif (isset($count[$value]) && $count[$value] > 1) {
        // Throw a form error if there are two languages with the same
        // domain/prefix.
        $form_state->setErrorByName("prefix][$langcode", $this->t('The prefix for %language, %value, is not unique.', array('%language' => $language->getName(), '%value' => $value)));
      }
    }

    // Count repeated values for uniqueness check.
    $count = array_count_values($form_state->getValue('domain'));
    foreach ($languages as $langcode => $language) {
      $value = $form_state->getValue(array('domain', $langcode));

      if ($value === '') {
        if ($form_state->getValue('language_negotiation_url_part') == LanguageNegotiationUrl::CONFIG_DOMAIN) {
          // Throw a form error if the domain is blank for a non-default language,
          // although it is required for selected negotiation type.
          $form_state->setErrorByName("domain][$langcode", $this->t('The domain may not be left blank for %language.', array('%language' => $language->getName())));
        }
      }
      elseif (isset($count[$value]) && $count[$value] > 1) {
        // Throw a form error if there are two languages with the same
        // domain/domain.
        $form_state->setErrorByName("domain][$langcode", $this->t('The domain for %language, %value, is not unique.', array('%language' => $language->getName(), '%value' => $value)));
      }
    }

    // Domain names should not contain protocol and/or ports.
    foreach ($languages as $langcode => $language) {
      $value = $form_state->getValue(array('domain', $langcode));
      if (!empty($value)) {
        // Ensure we have exactly one protocol when checking the hostname.
        $host = 'http://' . str_replace(array('http://', 'https://'), '', $value);
        if (parse_url($host, PHP_URL_HOST) != $value) {
          $form_state->setErrorByName("domain][$langcode", $this->t('The domain for %language may only contain the domain name, not a trailing slash, protocol and/or port.', ['%language' => $language->getName()]));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save selected format (prefix or domain).
    $this->config($this->languageNegotiationConfig)
      ->set('url.source', $form_state->getValue('language_negotiation_url_part'))
      // Save new domain and prefix values.
      ->set('url.prefixes', $form_state->getValue('prefix'))
      ->set('url.domains', $form_state->getValue('domain'))
      ->save();

    $form_state->disableRedirect();
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
