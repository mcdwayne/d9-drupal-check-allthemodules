<?php

namespace Drupal\domain_language\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\domain\DomainInterface;
use Drupal\Core\Link;

/**
 * Class DomainLanguageForm.
 *
 * @package Drupal\domain_language\Form
 */
class DomainLanguageForm extends FormBase {

  const DEFAULT_LANGUAGE_SITE = '***LANGUAGE_site_default***';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_language_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();

    /** @var DomainInterface $domain */
    $domain = $build_info['args'][0];

    // All available languages.
    $languages = \Drupal::languageManager()->getNativeLanguages();

    // Config without any override.
    $configRaw = \Drupal::config('system.site')->getRawData();
    $defaultRaw = $configRaw['default_langcode'];

    // Load the domain default language.
    $config = \Drupal::config('domain.config.' . $domain->getOriginalId() . '.system.site')->getRawData();
    $defaultLanguage = isset($config['default_langcode']) ? $config['default_langcode'] : self::DEFAULT_LANGUAGE_SITE;

    /** @var LanguageInterface $defaultLanguageRaw */
    $defaultLanguageRaw = \Drupal::languageManager()->getLanguage($defaultRaw);

    $options = [
      self::DEFAULT_LANGUAGE_SITE => t(
        "Site's default language (@lang_name)",
        ['@lang_name' => $defaultLanguageRaw->getName()]
      ),
    ];
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }

    $form['domain_id'] = [
      '#type' => 'value',
      '#value' => $domain->getOriginalId(),
    ];

    $form['default_language'] = [
      '#type' => 'select',
      '#title' => t('Default language'),
      '#options' => $options,
      '#description' => t(
        'This will override the default language: %default.',
        ['%default' => $defaultLanguageRaw->getName()]
      ),
      '#default_value' => $defaultLanguage,
      '#required' => TRUE,
    ];

    $options = [];
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }

    $config = \Drupal::configFactory()->get('domain.language.' . $domain->getOriginalId() . '.language.negotiation');
    $data = $config->getRawData();

    $form['languages'] = [
      '#type' => 'checkboxes',
      '#title' => t('Languages allowed'),
      '#description' => t('If none selected, all will be available. Default language will be added automatically.'),
      '#options' => $options,
      '#default_value' => isset($data['languages']) ? $data['languages'] : [],
      '#required' => FALSE,
    ];

    $form['actions'] = [
      '#type' => 'container',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => t('Save'),
    ];

    $form['actions']['cancel'] = Link::createFromRoute(t('Cancel'), 'domain.admin')->toRenderable();
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'button--danger'];

    return $form;
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
    $domain_id = $form_state->getValue('domain_id');
    $default_language = $form_state->getValue('default_language');
    $languages = array_filter($form_state->getValue('languages'));

    // Set default language into domain config file.
    $config = \Drupal::configFactory()->getEditable('domain.config.' . $domain_id . '.system.site');
    if ($default_language == self::DEFAULT_LANGUAGE_SITE) {
      $data = $config->getRawData();
      unset($data['default_langcode']);
      $config->setData($data);

      if (empty($data)) {
        // Delete if config is now empty.
        $config->delete();
      }
      else {
        $config->save();
      }
    }
    else {
      $config->set('default_langcode', $default_language);
      $config->save();
    }

    // Set default language into domain language file.
    $config = \Drupal::configFactory()->getEditable('domain.language.' . $domain_id . '.language.negotiation');
    if (empty($languages)) {
      $config->delete();
    }
    else {
      $languages[$default_language] = $default_language;
      $config->set('languages', $languages);
      $config->save();
    }

    // Remove any prefixes and domains in negotiation settings from domain.config file to avoid
    // any unexpected override.
    $config = \Drupal::configFactory()->getEditable('domain.config.' . $domain_id . '.language.negotiation');
    $data = $config->getRawData();
    unset($data['url']['prefixes'], $data['url']['domains']);
    if (empty($data['url'])) {
      unset($data['url']);
    }
    if (empty($data)) {
      $config->delete();
    }
    else {
      $config->setData($data);
      $config->save();
    }

    /** @var RouteBuilderInterface $routeBuilder */
    $routeBuilder = \Drupal::service('router.builder');
    $routeBuilder->rebuild();
  }

}
