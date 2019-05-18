<?php

namespace Drupal\acquia_contenthub_moderation\EventSubscriber\UnserializeContentField;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ContentModerationStateField.
 *
 * Provides a handler for moderation_state field during unserialization.
 *
 * @package Drupal\acquia_contenthub_moderation\EventSubscriber\UnserializeContentField
 */
class ContentModerationStateField implements EventSubscriberInterface {

  /**
   * The field name that stores content moderation states.
   *
   * @var string
   */
  protected $fieldName = 'moderation_state';

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Configuration for Content Hub Moderation.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * ContentModerationStateField constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The Config Factory.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The bundle information service.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeBundleInfoInterface $bundle_info) {
    $this->config = $config_factory->get('acquia_contenthub_moderation.settings');
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD] = ['onUnserializeContentField', 10];
    return $events;
  }

  /**
   * Extracts the target storage and retrieves the referenced entity.
   *
   * @param \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent $event
   *   The unserialize event.
   *
   * @throws \Exception
   */
  public function onUnserializeContentField(UnserializeCdfEntityFieldEvent $event) {
    if ($event->getFieldName() !== $this->fieldName) {
      return;
    }
    $entity_type = $event->getEntityType();
    $bundle = $event->getBundle();

    /** @var \Drupal\content_moderation\ModerationInformationInterface $moderation_info */
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    if (!$moderation_info->shouldModerateEntitiesOfBundle($entity_type, $bundle)) {
      return;
    }

    $values = [];
    $field = $event->getField();

    // Is there a workflow moderation state defined for imported entities.
    $workflow = $this->getContentModerationWorkflow($entity_type->id(), $bundle);
    $import_moderation_state = $this->config->get("workflows.{$workflow}.moderation_state");
    if (!empty($field['value'])) {
      foreach ($field['value'] as $langcode => $value) {
        if (!empty($value)) {
          $values[$langcode][$event->getFieldName()] = $import_moderation_state ?? $value;
        }
      }
    }

    // Set updated event values.
    $event->setValue($values);
    $event->stopPropagation();
  }

  /**
   * Obtains the workflow ID for a particular entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return string|null
   *   The workflow id or NULL if it does not have a workflow.
   */
  protected function getContentModerationWorkflow($entity_type_id, $bundle) {
    $bundles = $this->bundleInfo->getBundleInfo($entity_type_id);
    return $bundles[$bundle]['workflow'] ?? NULL;
  }
}
