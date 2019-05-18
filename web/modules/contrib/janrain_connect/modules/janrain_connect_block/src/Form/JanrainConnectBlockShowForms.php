<?php

namespace Drupal\janrain_connect_block\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFlowExtractorService;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class for show forms.
 */
class JanrainConnectBlockShowForms extends ConfigFormBase {

  /**
   * Janrain Connect FOrm Service.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService
   */
  private $janrainConnectFormService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  protected $configFactory;
  private $janrainConnectFlowExtractor;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageManagerInterface $language_manager,
    JanrainConnectUiFormService $janrain_connect_form_service,
    ConfigFactory $config_factory,
    ContainerInterface $container,
    JanrainConnectUiFlowExtractorService $janrain_flow_extractor
  ) {
    $this->languageManager = $language_manager;
    $this->janrainConnectFormService = $janrain_connect_form_service;
    $this->configFactory = $config_factory;
    $this->container = $container;
    $this->janrainConnectFlowExtractor = $janrain_flow_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('language_manager'),
        $container->get('janrain_connect_ui.form'),
        $container->get('config.factory'),
        $container->get('service_container'),
        $container->get('janrain_connect_ui.flow_extractor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_form_as_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');
    $application_id = $config->get('application_id');
    $flowjs_url = $config->get('flowjs_url');

    $forms_to_show = $this->janrainConnectFlowExtractor->getFormsData();

    if (!$forms_to_show) {
      drupal_set_message($this->t('No Janrain Forms were found in the flow. Did you perform Janrain Sync?'), 'warning');
      return [];
    }

    // Check configurations.
    if (empty($application_id) || empty($flowjs_url)) {

      drupal_set_message($this->t('Please fill Janrain settings.'), 'error');

      $url_settings = Url::fromRoute('janrain_connect.settings')->toString();

      return new RedirectResponse($url_settings);
    }

    $form['forms_as_block'] = [
      '#type' => 'details',
      '#title' => 'Forms as Block',
      '#open' => TRUE,
    ];

    // Default values.
    $options_forms = [];

    foreach ($forms_to_show as $key => $form_to_show) {

      // Replace because user not allow char ".".
      $form_id = str_replace('.', '@DOT@', $key);

      $options_forms[$form_id] = $form_id;
    }

    $forms_as_block = $config->get('forms_as_block');

    if (!$forms_as_block) {
      $forms_as_block = [];
    }

    $form['forms_as_block']['forms_as_block'] = [
      '#type' => 'checkboxes',
      '#options' => $options_forms,
      '#description' => $this->t('The selected forms will be available as Blocks.'),
      '#title' => $this->t('Forms to render as block'),
      '#default_value' => $forms_as_block,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

    $config = $this->config('janrain_connect.settings');

    $forms_as_block = $form_state->getValue('forms_as_block');

    $config->set('forms_as_block', $forms_as_block);

    $config->save();

    // Clear all caches to make blocks available.
    drupal_flush_all_caches();
  }

}
