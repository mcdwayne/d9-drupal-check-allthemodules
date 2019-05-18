<?php

namespace Drupal\healthz\Plugin\HealthzCheck;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\healthz\Plugin\HealthzCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a check that the a server provided by SAPI solr can be connected to.
 *
 * @HealthzCheck(
 *   id = "sapi_solr",
 *   title = @Translation("Search API Solr"),
 *   description = @Translation("Checks that a SOLR server configured via search_api_solr can be connected to.")
 * )
 */
class SearchApiSolr extends HealthzCheckBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The search api server entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SearchApiSolr constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    $storage = $this->entityTypeManager->getStorage('search_api_server');
    $id = $this->getConfiguration()['settings']['search_api_server'];
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = $storage->load($id);
    if (!$server) {
      $this->addError($this->t('Server entity @id not found', ['@id' => $id]));
      return FALSE;
    }

    if (!$server->getBackend()->isAvailable()) {
      $this->addError($this->t('SOLR server "@id" not available', ['@id' => $id]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return $this->moduleHandler->moduleExists('search_api_solr');
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\search_api\ServerInterface[] $servers */
    $servers = $this->entityTypeManager->getStorage('search_api_server')->loadMultiple();
    $options = [];
    foreach ($servers as $server) {
      if ($server->getBackendId() !== 'search_api_solr') {
        continue;
      }
      $options[$server->id()] = $server->label();
    }
    return [
      'search_api_server' => [
        '#type' => 'select',
        '#title' => $this->t('SOLR Server'),
        '#description' => $this->t('Choose the SOLR server to check the connectivity of.'),
        '#required' => TRUE,
        '#options' => $options,
        '#default_value' => isset($this->getConfiguration()['settings']['search_api_server']) ? $this->getConfiguration()['settings']['search_api_server'] : '',
      ],
    ];
  }

}
