<?php

namespace Drupal\pricespider\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;

/**
 * Class PriceSpiderService.
 */
class PriceSpiderService implements PriceSpiderServiceInterface {
  use StringTranslationTrait;

  protected $config;
  protected $configFactory;
  protected $entityTypeManager;
  protected $entityFieldManager;
  protected $languageManager;
  protected $moduleHandler;
  protected $themeManager;
  protected $logger;

  /**
   * PriceSpiderService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, LanguageManagerInterface $languageManager, ModuleHandlerInterface $moduleHandler, ThemeManagerInterface $themeManager, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->config = $configFactory->get('pricespider.settings');
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->languageManager = $languageManager;
    $this->moduleHandler = $moduleHandler;
    $this->themeManager = $themeManager;
    $this->logger = $loggerChannelFactory->get('pricespider');
  }

  /**
   * {@inheritdoc}
   */
  public function isProductType($entity_type, $bundle) {

    $product_types = $this->getProductTypes();

    return isset($product_types[$entity_type][$bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSkuFieldOptions($entity_type, $bundle) {
    $options = [];

    // Get entity info.
    if ($entity_info = $this->entityTypeManager->getDefinition($entity_type, FALSE)) {
      // Get entity label information.
      if ($label_key = $entity_info->getKey('label')) {
        $options[$label_key] = $this->t('@label (label)', ['@label' => ucwords($label_key)]);
      }
      // Get entity id information.
      if ($id = $entity_info->getKey('id')) {
        $options[$id] = $this->t('@entity_label ID (@id)', [
          '@entity_label' => $entity_info->get('label'),
          '@id' => $id,
        ]);
      }
      // Support UUID?
      if ($uuid = $entity_info->getKey('uuid')) {
        $options[$uuid] = $this->t('Universal Unique Identifier (uuid)');
      }

      // Get bundle fields.
      if ($entity_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle)) {
        // Loop through the entity fields.
        foreach ($entity_fields as $field_name => $field) {
          // This field of type text or string?
          if ($this->isSkuFieldType($field->getType())) {
            // Add it as an option.
            $options[$field_name] = (method_exists($field, 'getLabel') ? $field->getLabel() : $field->getName());
          }
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkuField($entity_type, $bundle) {
    $sku_field = FALSE;

    $product_types = $this->getProductTypes();

    // Is a product type?
    if (isset($product_types[$entity_type][$bundle])) {
      // Get the sku field associated to that bundle.
      $sku_field = $product_types[$entity_type][$bundle];
    }

    return $sku_field;
  }

  /**
   * {@inheritdoc}
   */
  public function setSkuField($entity_type, $bundle, $sku_field = '') {
    $product_types = $this->getProductTypes();
    // Update this product type.
    $product_types[$entity_type][$bundle] = $sku_field;
    // Save array.
    $this->setProductTypes($product_types);
    $this->logger->notice('Field @field saved as Sku Field for @type @bundle', [
      '@field' => $sku_field,
      '@type' => $entity_type,
      '@bundle' => $bundle,
    ]);
    // Is sku field blank?
    if ($sku_field == '') {
      $this->logger->warning('No field selected as Sku Field for @type @bundle', [
        '@type' => $entity_type,
        '@bundle' => $bundle,
      ]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getSkuValue(EntityInterface $entity) {
    $sku_value = FALSE;
    // Get entity type and bundle.
    $entity_type = $entity->getEntityType()->getProvider();
    $bundle = $entity->bundle();

    // Have a sku field?
    if ($sku_field = $this->getSkuField($entity_type, $bundle)) {
      // Able to get field value?
      if ($field_value = $this->getFieldValue($entity, $sku_field)) {
        $sku_value = $field_value;
      }
    }

    return $sku_value;
  }

  /**
   * {@inheritdoc}
   */
  public function removeProductType($entity_type, $bundle = FALSE) {
    $product_types = $this->getProductTypes();

    // If given a bundle.
    if ($bundle) {
      // Only remove that bundle.
      unset($product_types[$entity_type][$bundle]);
      $this->logger->notice('Entity Type @type @bundle removed as Price Spider product type', [
        '@type' => $entity_type,
        '@bundle' => $bundle,
      ]);
    }
    else {
      // Else remove all bundles of this type.
      unset($product_types[$entity_type]);
      $this->logger->notice('Entity Type @type and all bundles removed as Price Spider product types', [
        '@type' => $entity_type,
        '@bundle' => $bundle,
      ]);
    }

    $this->setProductTypes($product_types);
  }

  /**
   * {@inheritdoc}
   */
  public function getProductTypes() {
    $product_types = $this->config->get('product_types');
    // Allow others to alter the list.
    $this->callAlter('pricespider_product_types', $product_types);
    return $product_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getWTBUri($absolute = FALSE) {
    $uri = '';

    if ($absolute) {
      $uri = Url::fromRoute('pricespider.wtb');
    }
    else {
      $uri = $this->config->get('wtb.uri');
    }

    return $uri;
  }

  /**
   * Get the country code.
   *
   * @return string
   *   The country code.
   */
  public function getCountryCode() {
    $country_code = '';

    // Country code from drupal system config (sites's default country).
    if (empty($country_settings = \Drupal::config('system.date')->get('country'))) {
      return $country_code;
    }

    $country_code = strtoupper($country_settings['default']);

    return $country_code;
  }

  /**
   * Add meta tags for PriceSpider.
   *
   * @param array $tag_names
   *   Meta tags.
   *
   * @return array
   *   Array of meta tags.
   */
  public function getMetaTags(array $tag_names = []) {
    // Build metatags.
    $metatags = [];

    $metatags['ps-account'] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'ps-account',
          'content' => $this->config->get('ps.account'),
        ],
      ],
      'ps-account',
    ];

    $metatags['ps-config'] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'ps-config',
          'content' => $this->config->get('ps.config'),
        ],
      ],
      'ps-config',
    ];

    $metatags['ps-key'] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'ps-key',
          'content' => $this->config->get('ps.key'),
        ],
      ],
      'ps-key',
    ];

    $metatags['ps-country'] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'ps-country',
          'content' => $this->getCountryCode(),
        ],
      ],
      'ps-country',
    ];

    $metatags['ps-language'] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'ps-language',
          'content' => $this->languageManager->getCurrentLanguage()->getId(),
        ],
      ],
      'ps-language',
    ];

    if (!empty($tag_names)) {
      $filtered_tags = [];
      foreach ($tag_names as $tag) {
        if (isset($metatags[$tag])) {
          $filtered_tags[$tag] = $metatags[$tag];
        }
      }
      $metatags = $filtered_tags;
    }


    return array_values($metatags);

  }

  /**
   * Save the product types to config settings.
   *
   * @param array $product_types
   *   Product types array.
   */
  private function setProductTypes(array $product_types = []) {
    $this->configFactory->getEditable('pricespider.settings')
      ->set('product_types', $product_types)
      ->save();
  }

  /**
   * Return if the field type is an allowed field type.
   *
   * @param string $field_type
   *   A field type.
   *
   * @return bool
   *   Is the field type in the allowed list.
   */
  private function isSkuFieldType($field_type) {
    // Default array of supported types.
    $field_types = ['integer', 'string', 'string_long', 'text', 'text_long', 'text_with_summary'];
    // Allow other modules to alter this list.
    $this->callAlter('pricespider_sku_field_types', $field_types);

    return in_array($field_type, $field_types);
  }

  /**
   * Get the fields value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   * @param string $field_name
   *   The field name.
   *
   * @return mixed
   *   Field value.
   */
  private function getFieldValue(EntityInterface $entity, $field_name) {
    $field_value = FALSE;

    // Does entity have this field and able to retrieve it?
    if (($entity->hasField($field_name)) && ($field = $entity->get($field_name))) {
      // Get the field type.
      $field_type = $field->getFieldDefinition()->getType();

      switch ($field_type) {
        case 'integer':
        case 'string':
        case 'string_long':
        case 'text':
        case 'text_long':
        case 'text_with_summary':
          // Call get value.
          $field_value = $field->getValue();
          // Field value is an array?
          if (is_array($field_value)) {
            // Loop through each value.
            foreach ($field_value as $v => &$value) {
              // Have a value element? use that, else first array item.
              $value = isset($value['value']) ? $value['value'] : array_shift($value);
              // Empty value?
              if (!$value) {
                // Remove it from the array.
                unset($field_value[$v]);
              }
            }
          }
          // Array with a single item?
          if (is_array($field_value) && count($field_value) == 1) {
            // Then use the single item.
            $field_value = array_shift($field_value);
          }
          break;
      }
    }

    // Allow others to alter.
    $this->callAlter('pricespider_field_value', $field_value, $entity, $field_name);

    return $field_value;
  }

  /**
   * Implement module and theme alter.
   *
   * @param string|array $function
   *   Function name to use when altering the value.
   * @param mixed $data
   *   The variable that will be passed to hook_TYPE_alter() implementations
   *   to be altered.
   * @param mixed $context_one
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context_two
   *   (optional) An additional variable that is passed by reference.
   */
  private function callAlter($function, &$data, $context_one = NULL, $context_two = NULL) {
    // Let modules alter.
    $this->moduleHandler->alter($function, $data, $context_one, $context_two);
    // Let themes alter.
    $this->themeManager->alter($function, $data, $context_one, $context_two);
  }

}
