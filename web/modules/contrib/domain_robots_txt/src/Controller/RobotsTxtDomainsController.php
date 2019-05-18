<?php

namespace Drupal\domain_robots_txt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\domain\DomainNegotiator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\domain_robots_txt\Form\RobotsTxtDomainForm;

/**
 * Provides output robots.txt output.
 */
class RobotsTxtDomainsController extends ControllerBase {

  /**
   * Domain ID of config.
   *
   * @var string
   */
  protected $domainId;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Initializes a content translation controller.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\domain\DomainNegotiator $domain_negotiator
   *   A content translation manager instance.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainNegotiator $domain_negotiator, ModuleHandlerInterface $module_handler) {
    $this->domainId = $domain_negotiator->getActiveId();
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('domain.negotiator'),
      $container->get('module_handler')
    );
  }

  /**
   * Serves the configured robots.txt file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The robots.txt file as a response object with 'text/plain' content type.
   */
  public function content() {
    $content = [];
    $content[] = $this->configFactory->get(RobotsTxtDomainForm::getConfigNameByDomainId($this->domainId))
      ->get('robots_txt');

    // Hook other modules for adding additional lines.
    if ($additions = $this->moduleHandler->invokeAll('domain_robots_txt')) {
      $content = array_merge($content, $additions);
    }
    // Trim any extra whitespace and filter out empty strings.
    $content = array_map('trim', $content);
    $content = array_filter($content);
    $content = implode("\n", $content);
    // TODO: cache?
    return new Response($content, 200, ['Content-Type' => 'text/plain']);
  }

}
