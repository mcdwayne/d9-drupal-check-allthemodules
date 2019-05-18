<?php

namespace Drupal\dcat_export\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure dcat export settings for this site.
 */
class DcatExportSettingsForm extends ConfigFormBase {

  /**
   * EntityTypeManager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ModuleHandler object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dcat_export_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dcat_export.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dcat_export.settings');
    $jsonld_dependency = class_exists('\ML\JsonLD\JsonLD') ? '' : ' <em>(' . $this->t('Please install "ml/json-ld" dependency to use JSON-LD') . ')</em>';

    $form['#tree'] = FALSE;

    $form['source'] = [
      '#type' => 'details',
      '#title' => t('Source'),
      '#open' => TRUE,
    ];

    $source = &$form['source'];
    $source_options = $this->getDcatSourceOptions();

    $source['sources'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sources'),
      '#description' => $this->t('Select the sources to include in the DCAT export output or leave blank to select all data sets.'),
      '#default_value' => $config->get('sources'),
      '#multiple' => TRUE,
      '#options' => $source_options,
      '#access' => (bool) $source_options,
    ];

    $source['no_sources'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('There are no import sources available. All existing datasets will be included.'),
      '#access' => (bool) !$source_options,
    ];

    if ($this->moduleHandler->moduleExists('dcat_import')) {
      $source['dcat_import'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Import sources can be added on the <a href="@url">DCAT source settings page</a>.', [
          '@url' => Url::fromRoute('entity.dcat_source.collection')->toString(),
        ]),
        '#access' => (bool) !$source_options,
      ];
    }

    $form['output'] = [
      '#type' => 'details',
      '#title' => t('Output'),
      '#open' => TRUE,
    ];

    $output = &$form['output'];

    $output['formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Output formats'),
      '#description' => $this->t('Add the "_format" query parameter containing an enabled output format to the /dcat path.'),
      '#default_value' => $config->get('formats'),
      '#multiple' => TRUE,
      '#options' => [
        'rdf' => 'RDF (xml)',
        'ttl' => 'Turtle (ttl)',
        'json' => 'JSON (json)',
        'jsonld' => 'JSON-LD (jsonld)' . $jsonld_dependency,
        'nt' => 'N-Tripples (nt)',
      ],
      '#required' => TRUE,
    ];

    $output['endpoints'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Available endpoints'),
    ];

    $output['endpoints']['list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $this->getEndpointLinks(),
    ];

    $form['catalog'] = [
      '#type' => 'details',
      '#title' => t('Catalog settings'),
      '#description' => t('Configure the general catalog properties. This data will be visible in the DCAT feed. A data catalog is a curated collection of metadata about datasets.'),
      '#open' => TRUE,
    ];

    $cat = &$form['catalog'];

    $cat['catalog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $config->get('catalog_title'),
      '#description' => $this->t('The title of the DCAT feed.'),
      '#required' => TRUE,
    ];

    $cat['catalog_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $config->get('catalog_description'),
      '#description' => $this->t('The description of the DCAT feed.'),
      '#required' => TRUE,
    ];

    $cat['catalog_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URI'),
      '#default_value' => $config->get('catalog_uri'),
      '#description' => $this->t('The URI of the catalog.'),
      '#required' => TRUE,
    ];

    $cat['catalog_language_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language URI'),
      '#default_value' => $config->get('catalog_language_uri'),
      '#description' => $this->t('The URI of the catalog language.'),
      '#required' => TRUE,
    ];

    $cat['catalog_homepage_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Homepage'),
      '#default_value' => $config->get('catalog_homepage_uri'),
      '#description' => $this->t('The homepage of the DCAT feed.'),
      '#required' => TRUE,
    ];

    $cat['catalog_issued'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Issued date'),
      '#default_value' => new DrupalDateTime($config->get('catalog_issued')),
      '#description' => $this->t('The date when this feed was first issued.'),
      '#required' => TRUE,
    ];

    $cat['catalog_publisher_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher URI'),
      '#default_value' => $config->get('catalog_publisher_uri'),
      '#description' => $this->t('The uri of the publisher of this feed.'),
      '#required' => TRUE,
    ];

    $cat['catalog_publisher_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publisher name'),
      '#default_value' => $config->get('catalog_publisher_name'),
      '#description' => $this->t('The name of the publisher of this feed.'),
      '#required' => TRUE,
    ];

    $cat['catalog_license_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License URI'),
      '#default_value' => $config->get('catalog_license_uri'),
      '#description' => $this->t('This links to the license document under which the catalog is made available and not the datasets. Even if the license of the catalog applies to all of its datasets and distributions, it should be replicated on each distribution.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('dcat_export.settings');

    foreach ($form_state->getvalues() as $key => $value) {
      if ($key === 'catalog_issued') {
        $value = (string) $value;
      }

      $config->set($key, $value);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get an array of available endpoint links.
   *
   * @return array
   *   Array containing endpoint links.
   */
  protected function getEndpointLinks() {
    $config = $this->config('dcat_export.settings');

    $default_url = Url::fromRoute('dcat_export.export', [], ['absolute' => TRUE])->toString();
    $default_link = Link::createFromRoute($default_url, 'dcat_export.export');

    $export_links = [$default_link];

    foreach (array_filter($config->get('formats')) as $format) {
      $url = Url::fromRoute('dcat_export.export', ['_format' => $format], ['absolute' => TRUE])->toString();
      $export_links[] = Link::createFromRoute($url, 'dcat_export.export', ['_format' => $format]);
    }

    return $export_links;
  }

  /**
   * Get an option list of DCAT sources.
   *
   * @return array
   *   Array containing DCAT sources keyed by ID.
   */
  protected function getDcatSourceOptions() {
    $options = [];

    try {
      $sources = $this->entityTypeManager->getStorage('dcat_source')->loadMultiple();

      foreach ($sources as $source) {
        $options[$source->id()] = $source->label();
      }
    }
    catch (\Exception $ex) {
      // The module dcat_import is not enabled, ignore the exception.
      if (!$ex instanceof PluginNotFoundException && !$ex instanceof InvalidPluginDefinitionException) {
        throw $ex;
      }
    }

    return $options;
  }

}
