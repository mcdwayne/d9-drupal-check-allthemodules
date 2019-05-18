<?php

namespace Drupal\entity_print_form\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\UserSession;

use Drupal\entity_print\Plugin\ExportTypeManagerInterface;
use Drupal\entity_print\PrintBuilderInterface;
use Drupal\entity_print\PrintEngineException;
use Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntityPrintFormController extends ControllerBase {

  /**
   * The plugin manager for our Print engines.
   *
   * @var \Drupal\entity_print\Plugin\EntityPrintPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The export type manager.
   *
   * @var \Drupal\entity_print\Plugin\ExportTypeManagerInterface
   */
  protected $exportTypeManager;

  /**
   * The Print builder.
   *
   * @var \Drupal\entity_print\PrintBuilderInterface
   */
  protected $printBuilder;

  /**
   * The Entity Type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityPrintPluginManagerInterface $plugin_manager, ExportTypeManagerInterface $export_type_manager, PrintBuilderInterface $print_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->pluginManager = $plugin_manager;
    $this->exportTypeManager = $export_type_manager;
    $this->printBuilder = $print_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_print.print_engine'),
      $container->get('plugin.manager.entity_print.export_type'),
      $container->get('entity_print_form.print_form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Print an entity to the selected format.
   *
   * @param string $export_type
   *   The export type.
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   * @param string $mode
   *   The form display mode to use.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object on error otherwise the Print is sent.
   */
  public function viewPrint($export_type, $entity_type, $entity_id, $mode) {
    // Create the Print engine plugin.
    $config = $this->config('entity_print.settings');
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);

    $print_engine = $this->pluginManager->createSelectedInstance($export_type);

    return (new StreamedResponse(function () use ($entity, $print_engine, $config, $mode) {
      $this->printBuilder->deliverPrintable([$entity], $print_engine, $config->get('force_download'), $config->get('default_css'), $mode);

    }))->send();
  }

  /**
   * A debug callback for styling up the Print.
   *
   * @param string $export_type
   *   The export type.
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   * @param string $mode
   *   The form display mode to use.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @TODO, improve permissions in https://www.drupal.org/node/2759553
   */
  public function viewPrintDebug($export_type, $entity_type, $entity_id, $mode) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    $use_default_css = $this->config('entity_print.settings')->get('default_css');

    return new Response($this->printBuilder->printHtml($entity, $use_default_css, FALSE, $mode));
  }

}
