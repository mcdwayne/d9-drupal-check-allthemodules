<?php

namespace Drupal\acquia_contenthub\EventSubscriber\Cdf;

use Acquia\ContentHubClient\CDF\CDFObject;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Client\ClientFactory;
use Drupal\acquia_contenthub\Event\CreateCdfEntityEvent;
use Drupal\acquia_contenthub\Event\ParseCdfEntityEvent;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\depcalc\DependencyCalculator;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The Configuration entity CDF creator.
 *
 * @see \Drupal\acquia_contenthub\Event\CreateCdfEntityEvent
 */
class ConfigEntityHandler implements EventSubscriberInterface {

  /**
   * The dependency calculator.
   *
   * @var \Drupal\depcalc\DependencyCalculator
   */
  protected $calculator;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The client factory.
   *
   * @var \Drupal\acquia_contenthub\Client\ClientFactory
   */
  protected $clientFactory;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * ConfigEntityHandler constructor.
   *
   * @param \Drupal\depcalc\DependencyCalculator $calculator
   *   The dependency calculator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\acquia_contenthub\Client\ClientFactory $factory
   *   The client factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(DependencyCalculator $calculator, ConfigFactoryInterface $config_factory, ClientFactory $factory, LanguageManagerInterface $language_manager) {
    $this->calculator = $calculator;
    $this->configFactory = $config_factory;
    $this->clientFactory = $factory;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::CREATE_CDF_OBJECT][] = ['onCreateCdf', 100];
    $events[AcquiaContentHubEvents::PARSE_CDF][] = ['onParseCdf', 100];
    return $events;
  }

  /**
   * Creates a new CDF representation of Configuration Entities.
   */
  public function onCreateCdf(CreateCdfEntityEvent $event) {
    $entity = $event->getEntity();
    if (!$entity instanceof ConfigEntityInterface) {
      // Bail early if this isn't a config entity.
      return;
    }

    $settings = $this->clientFactory->getSettings();

    $cdf = new CDFObject('drupal8_config_entity', $entity->uuid(), date('c'), date('c'), $settings->getUuid());
    $metadata = [
      'default_language' => $entity->language()->getId(),
    ];
    if ($dependencies = $event->getDependencies()) {
      $metadata['dependencies'] = $dependencies;
    }
    // Some config entities don't have a dependency on their provider module.
    if ($entity->getEntityType()->getProvider() != 'core') {
      if (!empty($metadata['dependencies']['module'])) {
        $metadata['dependencies']['module'] = NestedArray::mergeDeep($metadata['dependencies']['module'], [
          $entity->getEntityType()->getProvider(),
        ]);
      }
      else {
        $metadata['dependencies']['module'][] = $entity->getEntityType()->getProvider();
      }
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityType $entity_type */
    $entity_type = $entity->getEntityType();
    $config_name = $entity_type->getConfigPrefix() . '.' . $entity->get($entity_type->getKey('id'));
    $config = $this->configFactory->get($config_name);

    $data = [
      $entity->language()->getId() => $config->getRawData(),
    ];
    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      foreach ($this->languageManager->getLanguages() as $langcode => $language) {
        if ($langcode === $entity->language()->getId()) {
          continue;
        }

        /** @var \Drupal\language\Config\LanguageConfigOverride $language_config_override */
        $language_config_override = $this->languageManager->getLanguageConfigOverride($langcode, $config_name);
        $overridden_config = $language_config_override->get();
        if ($overridden_config) {
          $data[$langcode] = $overridden_config;
        }
      }
    }

    $metadata['data'] = base64_encode(Yaml::encode($data));
    $cdf->setMetadata($metadata);
    $event->addCdf($cdf);
  }

  /**
   * Parses the CDF representation of Configuration Entities.
   *
   * @throws \Exception
   */
  public function onParseCdf(ParseCdfEntityEvent $event) {
    $cdf = $event->getCdf();
    if ($cdf->getType() !== 'drupal8_config_entity') {
      // Bail early if this isn't a config entity.
      return;
    }
    if (!$event->isMutable()) {
      return;
    }

    $default_langcode = $cdf->getMetadata()['default_language'];
    $data = Yaml::decode(base64_decode($cdf->getMetadata()['data']));
    $default_values = $data[$default_langcode];

    $entity_type_id = $cdf->getAttribute('entity_type')->getValue()['und'];

    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $entity = $this->getEntityWithValues($entity_type_id, $default_values);
    if ($event->hasEntity()) {
      $entity->enforceIsNew(FALSE);
    }
    $event->setEntity($entity);

    if ($this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityType $entity_type */
      $entity_type = $entity->getEntityType();
      $config_name = $entity_type->getConfigPrefix() . '.' . $entity->get($entity_type->getKey('id'));

      foreach ($data as $langcode => $language_override) {
        if ($langcode === $default_langcode) {
          continue;
        }
        if (empty($language_override)) {
          continue;
        }

        // Add language override for a language via Language Manager.
        $this->languageManager->getLanguageConfigOverride($langcode, $config_name)->setData($language_override)->save();
      }
    }
  }

  /**
   * Gets the proper configuration entity with the new values.
   *
   * This will load an existing entity from the local environment if one by the
   * same id exists. Otherwise it will generate a new entity of the right type.
   * In either case, it will populate that entity with the appropriate values.
   *
   * @param string $entity_type_id
   *   The entity type we are creating.
   * @param array $default_values
   *   The values to set for that entity.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityWithValues(string $entity_type_id, array $default_values) {
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->createFromStorageRecord($default_values);
    if ($old_entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity->id())) {
      // @todo check if this entity was previously imported. (Multiple entities of the same ID from different publishers)
      $default_values['uuid'] = $old_entity->uuid();
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $old_entity */
      $old_entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->createFromStorageRecord($default_values);
      $old_entity->enforceIsNew(FALSE);
      return $old_entity;
    }
    return $entity;
  }

}
