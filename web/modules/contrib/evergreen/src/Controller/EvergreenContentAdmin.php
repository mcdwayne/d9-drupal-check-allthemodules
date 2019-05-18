<?php

namespace Drupal\evergreen\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\evergreen\EvergreenService;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Class EvergreenContentAdmin.
 *
 * @package Drupal\evergreen\Controller
 */
class EvergreenContentAdmin extends ControllerBase {

  /**
   * Drupal\evergreen\EvergreenService definition.
   *
   * @var \Drupal\evergreen\EvergreenService
   */
  protected $evergreen;

  protected $pluginManager;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  protected $form_builder;

  /**
   * {@inheritdoc}
   */
  public function __construct(EvergreenService $evergreen, PluginManagerInterface $plugin_manager, FormBuilderInterface $form_builder, ConfigFactory $config_factory, QueryFactory $entity_query, Connection $database) {
    $this->evergreen = $evergreen;
    $this->pluginManager = $plugin_manager;
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory;
    $this->entityQuery = $entity_query;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('evergreen'),
      $container->get('plugin.manager.evergreen'),
      $container->get('form_builder'),
      $container->get('config.factory'),
      $container->get('entity.query'),
      $container->get('database')
    );
  }

  /**
   * Display evergreen content.
   */
  public function content() {
    $content_view_options = [];
    foreach ($this->pluginManager->getDefinitions() as $key => $plugin) {
      $content_view_options[$key] = $plugin['label'];
    }
    asort($content_view_options);

    if (!$content_view_options) {
      drupal_set_message($this->t('No evergreen entity modules have been enabled yet. Please contact your system administrator'));
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Please enable an evergreen entity module to view evergreen content.'),
      ];
    }

    // $content_view_options = ['' => $this->t('Select content to view')] + $content_view_options;

    $form = 'Drupal\evergreen\Form\ContentViewForm';
    $content = [
      'content_view' => $this->formBuilder->getForm($form, $content_view_options, $this->pluginManager),
    ];

    // $content['content_view'] = [
    //   '#type' => 'select',
    //   '#title' => 'Select view',
    //   '#options' => $content_view_options,
    // ];



    return $content;

  }

}
