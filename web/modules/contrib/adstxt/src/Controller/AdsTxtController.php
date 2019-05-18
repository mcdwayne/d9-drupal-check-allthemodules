<?php

namespace Drupal\adstxt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides output ads.txt output.
 */
class AdsTxtController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * AdsTxt module 'adstxt.settings' configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $moduleConfig;

  /**
   * Constructs a AdsTxtController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ConfigFactoryInterface $config, ModuleHandlerInterface $module_handler) {
    $this->moduleConfig = $config->get('adstxt.settings');
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Serves the configured ads.txt file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The ads.txt file as a response object with 'text/plain' content type.
   */
  public function build() {
    $content = [];
    $content[] = $this->moduleConfig->get('content');

    // Hook other modules for adding additional lines.
    if ($additions = $this->moduleHandler->invokeAll('adstxt')) {
      $content = array_merge($content, $additions);
    }

    // Trim any extra whitespace and filter out empty strings.
    $content = array_map('trim', $content);
    $content = array_filter($content);
    $content = implode("\n", $content);

    return new Response($content, 200, ['Content-Type' => 'text/plain']);
  }

}
