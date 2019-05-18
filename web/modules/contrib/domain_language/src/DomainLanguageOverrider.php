<?php

namespace Drupal\domain_language;

use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class DomainLanguageOverrider
 * @package Drupal\domain_language
 */
class DomainLanguageOverrider implements ConfigFactoryOverrideInterface {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * A storage controller instance for reading and writing configuration data.
   *
   * @var StorageInterface
   */
  protected $storage;

  /**
   * The domain context of the request.
   *
   * @var \Drupal\domain\DomainInterface $domain
   */
  protected $domain;

  /**
   * @var int
   */
  protected $nestedLevel;

  /**
   * Indicates that the request context is set.
   *
   * @var boolean
   */
  protected $contextSet;

  /**
   * Constructs a DomainLanguageOverrider object.
   *
   * @param StorageInterface $storage
   *   The configuration storage engine.
   */
  public function __construct(StorageInterface $storage) {
    $this->storage = $storage;
    $this->nestedLevel = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    global $config;

    $this->nestedLevel++;
    $overrides = [];
    if (empty($this->contextSet)) {
      $this->initiateContext();
    }

    if ($this->domain) {
      foreach ($names as $name) {
        if ($name == 'system.site') {
          $overrider = \Drupal::service('domain_config.overrider');
          $configs = $overrider->loadOverrides([$name]);

          if (!empty($configs[$name])) {
            // Initialize site settings.
            $overrides[$name] = $configs[$name];
          }
        }
        elseif ($name == 'language.negotiation') {
          $languages = \Drupal::config('domain.language.' . $this->domain->getOriginalId() . '.' . $name)->get(
            'languages'
          );

          // Setting array as null will reset keys.
          if (!empty($languages)) {
            // Todo: use alter mechanism to properly remove array entries.
            // @see: https://www.drupal.org/node/2829242
            $overrides[$name]['url']['prefixes'] = NULL;
            $overrides[$name]['url']['domains'] = NULL;

            // Set global config var to use second override mechanisms.
            if ($this->nestedLevel == 1) {
              $negotiations = \Drupal::config($name)->getRawData();

              // Todo: use 'Drupal\Core\Site\Settings' when available.
              // @see: https://www.drupal.org/node/2183591
              $config[$name]['url']['prefixes'] = array_intersect_key(
                $negotiations['url']['prefixes'],
                $languages
              );
              $config[$name]['url']['domains'] = array_intersect_key(
                $negotiations['url']['domains'],
                $languages
              );
            }
          }
        }
      }
    }

    $this->nestedLevel--;

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    $suffix = $this->domain ? $this->domain->id() : '';

    return ($suffix) ? $suffix : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    if (empty($this->contextSet)) {
      $this->initiateContext();
    }
    $metadata = new CacheableMetadata();
    if (!empty($this->domain)) {
      $metadata->addCacheContexts(['url.site']);
    }

    return $metadata;
  }

  /**
   * Sets domain and language contexts for the request.
   *
   * We wait to do this in order to avoid circular dependencies
   * with the locale module.
   */
  protected function initiateContext() {
    $this->contextSet = TRUE;

    $this->domainNegotiator = \Drupal::service('domain.negotiator');
    // Get the domain context.
    $this->domain = $this->domainNegotiator->getActiveDomain();
    // If we have fired too early in the bootstrap, we must force the routine to run.
    if (empty($this->domain)) {
      $this->domain = $this->domainNegotiator->getActiveDomain(TRUE);
    }
  }

}
