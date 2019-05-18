<?php

namespace Drupal\domain_lang\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\domain_lang\DomainLangHandlerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Form\NegotiationBrowserForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the browser language negotiation method for this site.
 */
class DomainLangNegotiationBrowserForm extends NegotiationBrowserForm {

  /**
   * The domain lang handler.
   *
   * @var \Drupal\domain_lang\DomainLangHandlerInterface
   */
  protected $domainLangHandler;

  /**
   * Language mappings config name for current active domain.
   *
   * @var string
   */
  protected $languageMappingsConfig;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   * @param \Drupal\domain_lang\DomainLangHandlerInterface $domain_lang_handler
   *   The domain lang handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigurableLanguageManagerInterface $language_manager, DomainLangHandlerInterface $domain_lang_handler) {
    parent::__construct($config_factory, $language_manager);
    $this->domainLangHandler = $domain_lang_handler;
    $this->languageMappingsConfig = $this->domainLangHandler->getDomainConfigName('language.mappings');
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
    return ['language.mappings', $this->languageMappingsConfig];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    // Initialize a language list to the ones available, including English.
    $languages = $this->languageManager->getLanguages();

    $existing_languages = array();
    foreach ($languages as $langcode => $language) {
      $existing_languages[$langcode] = $language->getName();
    }

    // If we have no languages available, present the list of predefined
    // languages only. If we do have already added languages, set up two option
    // groups with the list of existing and then predefined languages.
    if (empty($existing_languages)) {
      $language_options = $this->languageManager->getStandardLanguageListWithoutConfigured();
    }
    else {
      $language_options = array(
        (string) $this->t('Existing languages') => $existing_languages,
        (string) $this->t('Languages not yet added') => $this->languageManager->getStandardLanguageListWithoutConfigured(),
      );
    }

    $form['mappings'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Browser language code'),
        $this->t('Site language'),
        $this->t('Operations'),
      ],
      '#attributes' => ['id' => 'language-negotiation-browser'],
      '#empty' => $this->t('No browser language mappings available.'),
    ];

    $mappings = $this->language_get_browser_drupal_langcode_mappings();
    foreach ($mappings as $browser_langcode => $drupal_langcode) {
      $form['mappings'][$browser_langcode] = array(
        'browser_langcode' => array(
          '#title' => $this->t('Browser language code'),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#default_value' => $browser_langcode,
          '#size' => 20,
          '#required' => TRUE,
        ),
        'drupal_langcode' => array(
          '#title' => $this->t('Site language'),
          '#title_display' => 'invisible',
          '#type' => 'select',
          '#options' => $language_options,
          '#default_value' => $drupal_langcode,
          '#required' => TRUE,
        ),
      );
      // Operations column.
      $form['mappings'][$browser_langcode]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $form['mappings'][$browser_langcode]['operations']['#links']['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute(
          'domain_lang.negotiation_browser_delete',
          [
            'domain' => $this->domainLangHandler->getDomainFromUrl()->id(),
            'browser_langcode' => $browser_langcode,
          ]
        ),
      ];
    }

    // Add empty row.
    $form['new_mapping'] = array(
      '#type' => 'details',
      '#title' => $this->t('Add a new mapping'),
      '#tree' => TRUE,
    );
    $form['new_mapping']['browser_langcode'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Browser language code'),
      '#description' => $this->t('Use language codes as <a href=":w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', array(':w3ctags' => 'http://www.w3.org/International/articles/language-tags/')),
      '#size' => 20,
    );
    $form['new_mapping']['drupal_langcode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Site language'),
      '#options' => $language_options,
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->get('mappings');
    if (!empty($mappings)) {
      $config = $this->config($this->languageMappingsConfig);
      $config->setData(['map' => $mappings]);
      $config->save();
    }

    $form_state->disableRedirect();
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Retrieves the browser's langcode mapping configuration array.
   *
   * @return array
   *   The browser's langcode mapping configuration array.
   */
  protected function language_get_browser_drupal_langcode_mappings() {
    $config = $this->config($this->languageMappingsConfig);

    if ($config->isNew()) {
      $config->set('map', $this->config('language.mappings')->get('map'));
    }

    return $config->get('map') ? $config->get('map') : [];
  }

}
