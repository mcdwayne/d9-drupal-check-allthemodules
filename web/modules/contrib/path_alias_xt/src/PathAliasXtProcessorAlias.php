<?php

namespace Drupal\path_alias_xt;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Processes inbound and outbound path determining alias.
 */
class PathAliasXtProcessorAlias extends PathProcessorAlias {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
  * The module handler.
  *
  * @var \Drupal\Core\Extension\ModuleHandlerInterface
  */
  protected $moduleHandler;

  /**
  * The entity type manager.
  *
  * @var \Drupal\Core\Entity\EntityTypeManagerInterface
  */
  protected $entityTypeManager;

  /**
   * Constructs a Path alias processor.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AliasManagerInterface $alias_manager, ConfigFactory $config_factory, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($alias_manager);
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $path_to_process = ltrim($path, '/');
    // If Redirect module exists we must check for an active redirect.
    if ($this->moduleHandler->moduleExists('redirect')) {
      $query = $this->entityTypeManager->getStorage('redirect')->getQuery();
      $query->condition('redirect_source__path', $path_to_process);
      if ($query->execute()) {
        return $path;
      }
    }

    $removed_elements = [];
    $path_elements = explode('/', $path_to_process);

    foreach ($path_elements as $element) {
      $candidate_alias = '/' . implode('/', $path_elements);
      $source = $this->aliasManager->getPathByAlias($candidate_alias);

      if ($source != $candidate_alias) {
        // Change the order of the elements.
        krsort($removed_elements);
        $return_path = $source;
        if (!empty($removed_elements)) {
          $return_path .= '/' . implode('/', $removed_elements);
        }

        // Validate the path.
        // Injecting the service threw ServiceCircularReferenceException.
        if (\Drupal::service('path.validator')->getUrlIfValidWithoutAccessCheck($return_path)) {
          return $return_path;
        }
      }
      // Remove the last element from the elements array to be able to add it
      // to the end of the found path.
      $removed_elements[] = array_pop($path_elements);
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $path = parent::processOutbound($path, $options, $request, $bubbleable_metadata);

    $config = $this->configFactory->get('path_alias_xt.settings');
    if (preg_match($config->get('regex_pattern'), Unicode::substr($path, 1), $matches)) {
      $langcode = isset($options['language']) ? $options['language']->getId() : NULL;
      if ($alias = $this->aliasManager->getAliasByPath("/$matches[1]/$matches[2]", $langcode)) {
        $path = "$alias/$matches[3]";
      }
    }

    return $path;
  }

}
