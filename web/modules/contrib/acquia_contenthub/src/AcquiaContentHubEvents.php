<?php

namespace Drupal\acquia_contenthub;

/**
 * Defines events for the acquia_contenthub module.
 *
 * @see \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent
 * @see \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent
 * @see \Drupal\acquia_contenthub\Event\EntityDataTamperEvent
 * @see \Drupal\acquia_contenthub\Event\EntityImportEvent
 */
final class AcquiaContentHubEvents {

  /**
   * The event fired to collect ContentHub settings.
   *
   * ContentHub's settings can be provided in many different ways. This event
   * allows modules to provide a Settings object.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\AcquiaContentHubSettingsEvent
   * @see \Drupal\acquia_contenthub\Client\ClientFactory::populateSettings
   *
   * @var string
   */
  const GET_SETTINGS = 'acquia_contenthub_get_settings';

  /**
   * The event fired when an entity is being serialized to CDF.
   *
   * This event allows modules to collaborate on entity CDF serialization.
   * The event listener method receives a
   * \Drupal\acquia_contenthub\Event\CreateCdfEntityEvent instance
   * which allows modules to provide serialization logic for custom attributes
   * or additional serializations for a given entity beyond the normal
   * "structure" serializations.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\CreateCdfEntityEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::serializeEntities()
   *
   * @var string
   */
  const CREATE_CDF_OBJECT = 'acquia_contenthub_serialize_entity';

  /**
   * Adds attributes beyond those added during the CDF object creation process.
   *
   * This event allows modules to evaluate entities during CDF creation and add
   * custom attribute definitions to the CDF format before it is sent to the
   * ContentHub service backend.
   *
   * The event listener method will receive a
   * \Drupal\acquia_contenthub\Event\CdfAttributesEvent instance which provides
   * methods to evaluate the entity and modify the corresponding CDF Object.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\CdfAttributesEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::serializeEntities
   *
   * @var string
   */
  const POPULATE_CDF_ATTRIBUTES = 'acquia_contenthub_populate_cdf_attributes';

  /**
   * Parses a CDF Object to turn it into an entity.
   *
   * Allows modules to provide custom parsing instructions on a per CDF type
   * basis. The acquia_contenthub module provides basic handling for Content,
   * Configuration and File entities. Files are technically content entities.
   * If custom handling of a native Drupal entity type needs to be added, look
   * at how the file and content parsers operate in conjunction to model your
   * own solution.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\ParseCdfEntityEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::unserializeEntities
   *
   * @var string
   */
  const PARSE_CDF = 'acquia_contenthub_parse_cdf';

  /**
   * The event fired when a content entity field is being serialized to CDF.
   *
   * This event allows modules to collaborate on entity field serialization.
   * The event listener method receives a
   * \Drupal\acquia_contenthub_publisher\Event\SerializeCdfEntityFieldEvent
   * instance which allows modules to provide serialization logic for their own
   * field or entity types.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::serializeEntities()
   *
   * @var string
   */
  const SERIALIZE_CONTENT_ENTITY_FIELD = 'serialize_content_entity_field';


  /**
   * Name of the event fired when a CDF based PHP array is being unserialized.
   *
   * This event allows modules to collaborate on CDF unserialization.
   * The event listener method receives a
   * \Drupal\acquia_contenthub_publisher\Event\UnserializeCdfEntityFieldEvent
   * instance which allows modules to provide unserialization logic for specific
   * attribute types.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\UnserializeCdfEntityFieldEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::unserializeEntities()
   *
   * @var string
   */
  const UNSERIALIZE_CONTENT_ENTITY_FIELD = 'unserialize_content_entity_field';

  /**
   * Name of the event fired before a CDF based PHP array is being unserialized.
   *
   * This event allows modules to manipulate the data of the entire CDF array
   * before being unserialized by the normal process.
   * The event listener method receives a
   * \Drupal\acquia_contenthub_publisher\Event\EntityDataTamperEvent instance
   * which allows modules to tamper with the entity data before its unserialed.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\EntityDataTamperEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::unserializeEntities()
   *
   * @var string
   */
  const ENTITY_DATA_TAMPER = 'entity_data_tamper';

  /**
   * Name of the event fired after a CDF entity has been first saved.
   *
   * This event allows modules to respond to the saving of new entities after
   * they've been imported.
   * The event listener method receives a
   * \Drupal\acquia_contenthub_publisher\Event\EntityImportEvent instance which
   * allows modules to perform post-save events that might normally
   * happen (for instance) in a form submission.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\EntityImportEvent
   *
   * @var string
   */
  const ENTITY_IMPORT_NEW = 'entity_import_new';

  /**
   * Name of the event fired after a CDF entity has been updated.
   *
   * This event allows modules to respond to the saving of entities after
   * they've been imported.
   * The event listener method receives a
   * \Drupal\acquia_contenthub_publisher\Event\EntityImportEvent instance which
   * allows modules to perform post-save events that might normally
   * happen (for instance) in a form submission.
   *
   * @Event
   *
   * @see \Drupal\acquia_contenthub\Event\EntityImportEvent
   *
   * @var string
   */
  const ENTITY_IMPORT_UPDATE = 'entity_import_update';

  /**
   * Allows modules to remove items from the CDF Document before importing.
   *
   * This event provides a mechanism which allows modules to remove items from
   * the CDF before an import is attempted. This is especially good for items
   * which might are stand alone and not desired to ever be imported. More
   * interconnected entities will require some sort of clean up of the CDF
   * Document before processing can reliably happen which is to say, use this
   * event sparingly or only when you really know what you are doing. The
   * \Drupal\acquia_contenthub\AcquiaContentHubEvents::ENTITY_DATA_TAMPER event
   * is preferable under most circumstances.
   *
   * @see \Drupal\acquia_contenthub\Event\PruneCdfEntitiesEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::unserializeEntities
   */
  const PRUNE_CDF = 'prune_cdf';

  /**
   * Allows module to react to webhook events from the ContentHub Service.
   *
   * The ContentHub service sends a number of different webhooks for various
   * events and actions that occur within the service. An exhaustive list of
   * supported webhooks will be kept up to date within the docs for this
   * project, but many are implemented by this module and its submodules.
   *
   * @see \Drupal\acquia_contenthub\Event\HandleWebhookEvent
   * @see \Drupal\acquia_contenthub\Controller\ContentHubWebhookController::receiveWebhook
   */
  const HANDLE_WEBHOOK = 'acquia_contenthub_handle_webhook';

  /**
   * Allows modules to react to import failures.
   *
   * When import fails, a module may want to react any number of ways. This
   * event allows modules to dig through the CDF Document that was being
   * processed during failure and its corresponding DependencyStack object as
   * well as a count of items processed before failure. These objects give deep
   * insight to the process and could be used to diagnose problems under custom
   * use case circumstances.
   *
   * @see \Drupal\acquia_contenthub\Event\FailedImportEvent
   * @see \Drupal\acquia_contenthub\EntityCdfSerializer::unserializeEntities
   */
  const IMPORT_FAILURE = 'acquia_contenthub_import_failure';

  /**
   * Allows modules to provide custom entity mapping support for cdf objects.
   *
   * A CDF object may represent a pre-existing local entity either during
   * update or initial import. Successfully mapping that relationship can
   * require custom code. This event allows modules to provide custom logic for
   * loading or creating local entities for data import/update purposes.
   */
  const LOAD_LOCAL_ENTITY = 'acquia_contenthub_load_local_entity';

  /**
   * Event used to determine which entities should be published to ContentHub.
   *
   * This event provides an opportunity for all subscribers to remove entities
   * bound for ContentHub. This is useful for removing things like calculated
   * dependencies that have not changed since the last time they were pushed
   * to ContentHub.
   */
  const PUBLISH_ENTITIES = 'acquia_contenthub_publish_entities';

  /**
   * Event used to inform of remote entity deletion.
   *
   * When an entity is deleted from the hub by some local runtime process, this
   * event allows module to respond to that event as necessary. Only the uuid
   * of the deleted entity is available to subscribers. It is assumed modules
   * will utilize some sort of tracking table like the publisher and subscriber
   * modules do within the contenthub package for any additional tracking.
   */
  const DELETE_REMOTE_ENTITY = 'acquia_contenthub_delete_remote_entity';

  /**
   * Event used to build the clientcdf.
   *
   * When the clientcdf is created, this event allows modules to inject extra
   * data to be contained within the clientcdf.
   */
  const BUILD_CLIENT_CDF = 'acquia_contenthub_build_client_cdf';

}
