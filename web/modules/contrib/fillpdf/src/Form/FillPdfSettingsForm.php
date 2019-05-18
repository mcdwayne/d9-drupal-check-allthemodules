<?php

namespace Drupal\fillpdf\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\fillpdf\Component\Utility\FillPdf;
use Drupal\fillpdf\Service\FillPdfAdminFormHelper;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\fillpdf\Entity\FillPdfForm;
use Drupal\Core\Config\Config;

/**
 * Configure FillPDF settings form.
 */
class FillPdfSettingsForm extends ConfigFormBase {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Definitions of all backend plugins.
   *
   * @var array[]
   *   Associative array of all backend plugin definitions, keyed by plugin ID
   *   and sorted by weight.
   */
  protected $definitions = [];

  /**
   * The FillPDF admin form helper service.
   *
   * @var \Drupal\fillpdf\Service\FillPdfAdminFormHelper
   */
  protected $adminFormHelper;

  /**
   * The Guzzle HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a FillPdfSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   Helpers to operate on files and stream wrappers.
   * @param \Drupal\fillpdf\Service\FillPdfAdminFormHelper $admin_form_helper
   *   The FillPDF admin form helper service.
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle HTTP client service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    FillPdfAdminFormHelper $admin_form_helper,
    Client $http_client
  ) {
    parent::__construct($config_factory);

    $this->fileSystem = $file_system;
    $this->adminFormHelper = $admin_form_helper;
    $this->httpClient = $http_client;

    $backend_manager = \Drupal::service('plugin.manager.fillpdf_backend');
    $this->definitions = $backend_manager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('fillpdf.admin_form_helper'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fillpdf_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['fillpdf.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('fillpdf.settings');

    // Get available scheme options.
    $scheme_options = $this->adminFormHelper->schemeOptions([
      'public' => $this->t('@scheme (discouraged)'),
      'private' => $this->t('@scheme (recommended)'),
    ]);
    $form['allowed_schemes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed file storages'),
      '#default_value' => array_intersect(array_keys($scheme_options), $config->get('allowed_schemes')),
      '#options' => $scheme_options,
      '#description' => $this->t("You may choose one or more file storages to be available for storing generated PDF files with actual entity data; note that %public does not provide any access control.<br />If you don't choose any file storage, generated PDFs may only be sent to the browser instead of being stored.", [
        '%public' => $this->t('Public files'),
      ]),
    ];

    $form['advanced_storage'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced storage settings'),
    ];
    $file_default_scheme = file_default_scheme();
    $template_scheme_options = $this->adminFormHelper->schemeOptions([
      $file_default_scheme => $this->t('@scheme (site default)'),
    ]);
    $template_scheme = $config->get('template_scheme');
    // Set an error if the previously configured scheme doesn't exist anymore.
    if ($template_scheme && !array_key_exists($template_scheme, $template_scheme_options)) {
      $error_message = $this->t('Your previously used file storage %previous_scheme is no longer available on this Drupal site, see the %system_settings. Please reset your default to an existing file storage.', [
        '%previous_scheme' => $template_scheme . '://',
        '%system_settings' => Link::createFromRoute($this->t('File system settings'), 'system.file_system_settings')->toString(),
      ]);

      // @todo: It would be helpful if we could use EntityQuery instead, see
      // https://www.drupal.org/project/fillpdf/issues/3043508.
      $map = $this->adminFormHelper->getFormsByTemplateScheme($template_scheme);
      if ($count = count($map)) {
        $forms = FillPdfForm::loadMultiple(array_keys($map));
        $items = [];
        foreach ($map as $form_id => $file_uri) {
          $fillpdf_form = $forms[$form_id];
          $admin_title = current($fillpdf_form->get('admin_title')->getValue());
          // @todo: We can simpify this once an admin_title is #required,
          // see https://www.drupal.org/project/fillpdf/issues/3040776.
          $link = Link::fromTextAndUrl($admin_title ?: "FillPDF form {$fillpdf_form->id()}", $fillpdf_form->toUrl());
          $items[$form_id] = new FormattableMarkup("@fillpdf_form: {$file_uri}", ['@fillpdf_form' => $link->toString()]);
        }
        $list = [
          '#theme' => 'item_list',
          '#items' => $items,
        ];
        $error_message .= '<br />' . $this->t('Nevertheless, the following FillPDF forms will not work until their respective PDF templates have been moved to an existing file scheme:<br />@list', [
          '@list' => \Drupal::service('renderer')->renderPlain($list),
        ]);
      }

      $this->messenger()->addError(new FormattableMarkup($error_message, []));
      $this->logger('fillpdf')->critical('File storage %previous_scheme is no longer available.' . $count ? " $count FillPDF forms are defunct." : '', [
        '%previous_scheme' => $template_scheme . '://',
      ]);
    }

    $form['advanced_storage']['template_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Template storage'),
      '#default_value' => array_key_exists($template_scheme, $template_scheme_options) ? $template_scheme : $file_default_scheme,
      '#options' => $template_scheme_options,
      '#description' => $this->t('This setting is used as the storage for uploaded templates; note that the use of %public is more efficient, but does not provide any access control.<br />Changing this setting will require you to migrate associated files and data yourself and is not recommended after you have uploaded a template.', [
        '%public' => t('Public files'),
      ]),
    ];

    $form['backend'] = [
      '#type' => 'radios',
      '#title' => $this->t('PDF-filling service'),
      '#description' => $this->t('This module requires the use of one of several external PDF manipulation tools. Choose the service you would like to use.'),
      '#default_value' => $config->get('backend') ?: 'fillpdf_service',
      '#options' => [],
    ];

    foreach ($this->definitions as $id => $definition) {
      // Add a radio option for every backend plugin.
      $label = $definition['label'];
      $description = $definition['description'];
      $form['backend']['#options'][$id] = ("<strong>{$label}</strong>") . ($description ? ": {$description}" : '');

    }

    $form['fillpdf_service'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure %label', ['%label' => $this->definitions['fillpdf_service']['label']]),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="backend"]' => ['value' => 'fillpdf_service'],
        ],
      ],
    ];
    $form['fillpdf_service']['remote_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Server endpoint'),
      '#default_value' => $config->get('remote_endpoint'),
      '#description' => $this->t('The endpoint for the FillPDF Service instance. This does not usually need to be changed, but you may want to if you have, for example, a <a href="https://fillpdf.io/hosting">private server</a>. Do not include the protocol, as this is determined by the <em>Use HTTPS?</em> setting below.'),
    ];
    $form['fillpdf_service']['fillpdf_service_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $config->get('fillpdf_service_api_key'),
      '#description' => $this->t('You need to sign up for an API key at <a href="@link">FillPDF Service</a>', [
        '@link' => Url::fromUri('https://fillpdf.io')->toString(),
      ]),
    ];
    $form['fillpdf_service']['remote_protocol'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use HTTPS?'),
      '#description' => $this->t('It is recommended to select <em>Use HTTPS</em> for this option. Doing so will help prevent
      sensitive information in your PDFs from being intercepted in transit between your server and the remote service. <strong>FillPDF Service will only work with HTTPS.</strong>'),
      '#default_value' => $config->get('remote_protocol'),
      '#options' => [
        'https' => $this->t('Use HTTPS'),
        'http' => $this->t('Do not use HTTPS'),
      ],
    ];

    $form['local_service'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure %label', ['%label' => $this->definitions['local_service']['label']]),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="backend"]' => ['value' => 'local_service'],
        ],
      ],
    ];
    $form['local_service_endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Configure FillPdf LocalServer endpoint (address)'),
      '#default_value' => $config->get('local_service_endpoint'),
      '#description' => $this->t("Enter the network address of your FillPDF LocalServer installation. If you are running the Docker container on port 8085 locally, then the address is <em>http://127.0.0.1:8085</em>."),
      '#group' => 'local_service',
    ];

    $form['pdftk'] = [
      '#type' => 'details',
      '#title' => $this->t('Configure %label', ['%label' => $this->definitions['pdftk']['label']]),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="backend"]' => ['value' => 'pdftk'],
        ],
      ],
    ];
    $form['pdftk_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Configure path to pdftk'),
      '#description' => $this->t("If FillPDF is not detecting your pdftk installation, you can specify the full path to the program here. Include the program name as well. On many systems, <em>/usr/bin/pdftk</em> is a valid value. You can almost always leave this field blank. If you should set it, you'll probably know."),
      '#default_value' => $config->get('pdftk_path') ?: 'pdftk',
      '#group' => 'pdftk',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    switch ($values['backend']) {
      case 'fillpdf_service':
        // @todo: Add validation for FillPDF Service.
        // See https://www.drupal.org/project/fillpdf/issues/3040899.
        break;

      case 'local_service':
        // Set the form_state value to the Config object without saving.
        $config = $this->config('fillpdf.settings')->set('local_service_endpoint', $values['local_service_endpoint']);
        // Check for FillPDF LocalServer.
        $status = FillPdf::checkLocalServiceEndpoint($this->httpClient, $config);
        if ($status === FALSE) {
          $error_message = $this->t('FillPDF LocalService is not properly installed. Was unable to contact %endpoint', [
            '%endpoint' => $values['local_service_endpoint'],
          ]);
          $form_state->setErrorByName('local_service_endpoint', $error_message);
        }
        break;

      case 'pdftk':
        // Check for pdftk.
        $status = FillPdf::checkPdftkPath($values['pdftk_path']);
        if ($status === FALSE) {
          $error_message = $this->t('The path you have entered for <em>pdftk</em> is invalid. Please enter a valid path.');
          $form_state->setErrorByName('pdftk_path', $error_message);
        }
        break;

      case 'local':
        // Check for JavaBridge.
        $status = file_exists(drupal_get_path('module', 'fillpdf') . '/lib/JavaBridge/java/Java.inc');
        if ($status === FALSE) {
          $error_message = $this->t('JavaBridge is not installed locally.');
          $form_state->setErrorByName('local', $error_message);
        }
        break;
    }

    $template_scheme = $values['template_scheme'];
    $schemes_to_prepare = array_filter($values['allowed_schemes']) + [$template_scheme => $template_scheme];
    foreach ($schemes_to_prepare as $scheme) {
      $uri = FillPdf::buildFileUri($scheme, 'fillpdf');
      if (!file_prepare_directory($uri, FILE_CREATE_DIRECTORY + FILE_MODIFY_PERMISSIONS)) {
        $error_message = $this->t('Could not automatically create the subdirectory %directory. Please check permissions before trying again.', [
          '%directory' => $this->fileSystem->realpath($uri),
        ]);
        $form_state->setErrorByName('template_scheme', $error_message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save form values.
    $values = $form_state->getValues();
    $config = $this->config('fillpdf.settings');

    $config->set('allowed_schemes', array_keys(array_filter($values['allowed_schemes'])))
      ->set('template_scheme', $values['template_scheme'])
      ->set('backend', $values['backend']);

    switch ($values['backend']) {
      case 'fillpdf_service':
        $config->set('remote_endpoint', $values['remote_endpoint'])
          ->set('fillpdf_service_api_key', $values['fillpdf_service_api_key'])
          ->set('remote_protocol', $values['remote_protocol']);
        break;

      case 'local_service':
        $config->set('local_service_endpoint', $values['local_service_endpoint']);
        break;

      case 'pdftk':
        $config->set('pdftk_path', $form_state->getValue('pdftk_path'));
        break;
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
