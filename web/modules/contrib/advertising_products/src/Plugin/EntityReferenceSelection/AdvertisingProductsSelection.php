<?php

namespace Drupal\advertising_products\Plugin\EntityReferenceSelection;

use Drupal\advertising_products\AdvertisingProductsProviderManager;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'advertising_products:product' entity selection.
 *
 * @EntityReferenceSelection(
 *   id = "advertising_products:product",
 *   label = @Translation("Advertising Products selection"),
 *   entity_types = {"advertising_product"},
 *   group = "advertising_products",
 *   weight = 1
 * )
 */
class AdvertisingProductsSelection extends DefaultSelection {

  /**
   * @var \Drupal\advertising_products\AdvertisingProductsProviderManager
   */
  protected $providerManager;

  /**
   * @var string
   */
  private $providerId = NULL;

  /**
   * Constructs a new AdvertisingProductsSelection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, AdvertisingProductsProviderManager $providerManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->providerManager = $providerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('plugin.manager.advertising_products.provider')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery(NULL, $match_operator);

    if (isset($match)) {
      // Get original input
      $input = \Drupal::request()->query->get('q');
      // Determine if a product url is entered
      if ($host = parse_url($input, PHP_URL_HOST)) {
        // Determine product provider
        if ($provider = $this->determineProductProvider($host)) {
          $provider_id = $provider->getPluginId();
          // Get the product id from url
          if ($product_id = $provider->getProductIdFromUrl($input)) {
            // Add query condition to select by product ID
            $query->condition('product_id', $product_id);
            $query->condition('product_provider', $provider_id);
          }
        }
      }
      else {
        // Add query condition to select by product name
        $target_type = $this->configuration['target_type'];
        $entity_type = $this->entityManager->getDefinition($target_type);
        $label_key = $entity_type->getKey('label');
        $query->condition($label_key, $match, $match_operator);
      }
    }

    return $query;
  }


  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $entities = parent::getReferenceableEntities($match, $match_operator, $limit);
    if (empty($entities)) {
      // Get original input
      $input = \Drupal::request()->query->get('q');
      // Fetch the product on the fly
      if ($this->providerId) {
        // Create provider plugin instance
        $provider = $this->providerManager->createInstance($this->providerId);
        // Extract product ID from url
        if ($product_id = $provider->getProductIdFromUrl($input)) {
          // Fetch product from the provider and add it to options
          if ($product = $provider->fetchProductOnTheFly($product_id)) {
            $entities[$provider::$productBundle][$product->id()] = $product->product_name->value;
          }
        }
      }
      // in case there is no product id and no provider in the Uri we allow generic searching
      else {
        $provider_plugins = $this->providerManager->getDefinitions();
        foreach ($provider_plugins as $plugin => $definition) {
          // Create provider plugin instance
          $provider = $this->providerManager->createInstance($plugin);
          $search_result = $provider->searchProduct($input);
          if (is_array($search_result) && count($search_result)) {
            foreach ($search_result as $product) {
              $entities[$provider::$productBundle][$product->id()] = $product->product_name->value;
            }
          }
        }
      }
    }

    return $entities;
  }

  /**
   * Determine product provider by hostname.
   *
   * @param string $host
   */
  public function determineProductProvider($host) {
    // Collect available provider plugins
    $provider_plugins = $this->providerManager->getDefinitions();
    foreach ($provider_plugins as $plugin => $definition) {
      // Create provider plugin instance
      $provider = $this->providerManager->createInstance($plugin);
      $domains = $provider::$providerDomains;
      // Check if provider corresponds to the given url
      if (in_array($host, $domains) !== FALSE) {
        // Set provider plugin ID for further use
        $this->providerId = $plugin;
        // Return provider plugin instance
        return $provider;
      }
    }
    return FALSE;
  }

}
