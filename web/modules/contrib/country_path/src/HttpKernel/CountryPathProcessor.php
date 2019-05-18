<?php

namespace Drupal\country_path\HttpKernel;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\country_path\CountryPathTrait;

/**
 * Path processor for country_path.
 */
class CountryPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  use CountryPathTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a DomainPathProcessor object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $active_domain = $this->getActiveDomain();

    // Only act on valid internal paths and when a domain loads.
    if (empty($active_domain) || empty($path)) {
      return $path;
    }

    $domain_suffix = $active_domain->getThirdPartySetting('country_path', 'domain_path');

    if (empty($domain_suffix)) {
      return $path;
    }

    if (preg_match('/^\/' . $domain_suffix . '/i', $path, $matches)) {
      $path = preg_replace('@^/' . $domain_suffix . '(.*)@', '$1', $path);
    }

    return empty($path) ? '/' : $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $active_domain = $this->getActiveDomain();

    // Only act on valid internal paths and when a domain loads.
    if (empty($active_domain) || empty($path) || !empty($options['external'])) {
      return $path;
    }

    $domain_suffix = $active_domain->getThirdPartySetting('country_path', 'domain_path');
    if (!empty($bubbleable_metadata)) {
      $bubbleable_metadata->addCacheContexts(['url.country']);
    }

    if (empty($domain_suffix)) {
      return $path;
    }

    // Add the domain suffix in front of any existing suffix, for example
    // language. Due to the low priority of this domain processor, the domain
    // will be the first URL element. For example: [domain]/[language]/.
    $options['prefix'] = "$domain_suffix/" . $options['prefix'];

    return $path;
  }

}
