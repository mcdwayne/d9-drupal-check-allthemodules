<?php

namespace Drupal\ui_components\TwigExtension;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Template\TwigExtension;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * A Twig extension for UI Components.
 */
class UiComponents extends TwigExtension {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(RendererInterface $renderer, UrlGeneratorInterface $url_generator, ThemeManagerInterface $theme_manager, DateFormatterInterface $date_formatter, ModuleHandlerInterface $module_handler) {
    parent::__construct($renderer, $url_generator, $theme_manager, $date_formatter);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'ui_components';
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobals() {
    $globals = [];
    $this->moduleHandler->alter('ui_components_globals', $globals);
    return $globals;
  }

}
