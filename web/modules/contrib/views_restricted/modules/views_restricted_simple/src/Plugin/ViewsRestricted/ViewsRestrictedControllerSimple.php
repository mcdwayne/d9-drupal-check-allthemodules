<?php

namespace Drupal\views_restricted_simple\Plugin\ViewsRestricted;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views_restricted\ViewsRestrictedPatternControllerBase;
use Drupal\views_restricted_simple\ViewsRestrictedSimple;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Example plugin implementation of the views_restricted.
 *
 * @ViewsRestricted(
 *   id = "views_restricted_simple",
 * )
 */
class ViewsRestrictedControllerSimple extends ViewsRestrictedPatternControllerBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Config\ConfigFactoryInterface */
  protected $configFactory;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @noinspection PhpParamsInspection */
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('config.factory'));
  }

  protected function checkInfoString($infoString) {
    if (!\Drupal::currentUser()->hasPermission('use views_restricted_simple views')) {
      return FALSE;
    }
    $patterns = $this->getPatterns();
    $return = FALSE;
    foreach ($patterns as $pattern) {
      // Unicode, ungreedy.
      $fits = preg_match("#^$pattern#uU", $infoString);
      if ($fits) {
        $return = TRUE;
        break;
      }
    }
    return $return;
  }

  protected function getPatterns() {
    $config = $this->configFactory->get('views_restricted_simple.settings');
    $patternString = $config->get('patterns');
    $patterns = ViewsRestrictedSimple::parsePatternString($patternString);
    $patterns = array_filter($patterns);
    return $patterns;
  }

}
