ContentHub Events: When They Fire, What They Do
===============================================

ContentHub is largely an event driven architecture. This means that in order to interact with it, you must write event subscribers which subscribe to the appropriate event. The main ContentHub module's events can all be found within the ``\Drupal\acquia_contenthub\AcquiaContentHubEvents`` class. This class is documented extensively, but what follows are more elaborate explanations of all the documentation you will find in that class.

Getting Settings
^^^^^^^^^^^^^^^^

The settings object within ContentHub is provided by our external PHP library. It is a simple class that just holds our credentials for connecting to the ContentHub Service. This class could be instantiated any number of different ways, while it's primarily useful for Acquia, it is worth understanding how it works.

The ``Drupal\acquia_contenthub\Client\ClientFactory::populateSettings`` method dispatches an event to retrieve the Settings object. Different subscribers can attempt to resolve this request in their own way. The core ContentHub module provides code which will store and retrieve these settings from normal Drupal configuration if no other Settings object has been found before that code operates. This has the benefit of both allowing the configuration form to work, and also automatically locking the configuration form when Settings are provided by some other event subscriber.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::GET_SETTINGS``

The dispatched event is ``Drupal\acquia_contenthub\Event\AcquiaContentHubSettingsEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\GetSettings\GetSettingsFromCoreConfig``
- ``Drupal\acquia_contenthub\EventSubscriber\GetSettings\GetSettingsFromCoreSettings``

Creating a CDF Object
^^^^^^^^^^^^^^^^^^^^^

CDF Objects are designed to be a robust format which supports both data you might think of from a "faceting" approach as well as a form of serialization that suites the needs of the effectively syndicating data between sites. If you are reading these docs to learn how to handle your own custom entity type, know that ContentHub handles all Drupal entities by default. However, if you find its default handling to be missing features you need, read on.

ContentHub provides default handlers for Content, Configuration and File entities. These are all separate event subscribers, but in the case of files, the file entities are acted upon by both the File and Content handlers. This is because, while the Content handler does most of what we need, the File handler adds specific attributes to the CDF object which give insights into HOW to handle a file, where to get the file, and where to save the file.

Ultimately any CDF handler will create a new CDFObject, add appropriate attributes to that object via the ``Acquia\ContentHubClient\CDF\CDFObject::addAttribute`` method, and interact with the attributes and metadata as necessary to support their data. If you are creating a completely new data type, be sure to "type" that data with your own custom string. For Drupal 8 content entities, ContentHub uses the string ``drupal8_content_entity``. You can do similar with your type. Just be sure to namespace it in such a way as that it is unlikely to conflict with anything else. For more details on creating your own CDF Object, refer to the CDFObject class.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::CREATE_CDF_OBJECT``

The dispatched event is ``Drupal\acquia_contenthub\Event\CreateCdfEntityEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\Cdf\ContentEntityHandler``
- ``Drupal\acquia_contenthub\EventSubscriber\Cdf\FileEntityHandler``
- ``Drupal\acquia_contenthub\EventSubscriber\Cdf\ConfigEntityHandler``
- ``Drupal\acquia_lift_support\EventSubscriber\Cdf\EntityRenderHandler``

Additional related classes:

- ``Acquia\ContentHubClient\CDF\CDFObject``
- ``Acquia\ContentHubClient\CDFAttribute``

Creating Global CDF Attributes
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

When creating your own CDF Object and type, it is very easy to add custom CDF Attributes. However, sometimes you will need to affect CDF attribute generation across multiple CDF data types. The ContentHub module uses this capability to elevate attributes like entity type, bundle, label and hash value as well as a number of other attributes used to expose better data to user interfaces or search criteria. Any attribute added to a CDF object is indexed by the ContentHub Service, so data placed at this layer is able to be queried against. In addition to this, while the event is meant to allow for customization of attributes, it can also be used to alter the CDF object's metadata. Metadata is not indexed.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES``

The dispatched event is ``Drupal\acquia_contenthub\Event\CdfAttributesEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\CdfAttributes\EntityTagsCdfAttribute``
- ``Drupal\acquia_contenthub\EventSubscriber\CdfAttributes\EntityTypeBundleCdfAttribute``
- ``Drupal\acquia_contenthub\EventSubscriber\CdfAttributes\HashCdfAttribute``

Parsing a CDF Object
^^^^^^^^^^^^^^^^^^^^

As mention under the `CDF Creation`_ subsection, CDF is able to support custom data types. ContentHub provides support for Configuration, Content and File entities as part of its basic integration with Drupal. That being said, it is perfectly possible for a developer to establish their own serialization types both inside and outside of Drupal. As a theoretical example, it could be possible to write code in something like Magento which sends Magento products to the ContentHub Service, and write a corresponding CDF Parser in Drupal sites which knows how to interpret and import that Magento data. With a custom CDF parser, a developer can target specific kinds of CDF object upon which they would like to act. From there, they can decode the data in the CDF and import it in whatever way makes the most sense.

The ContentHub module couples subscription of this event to the CREATE_CDF_OBJECT event. This isn't strictly necessary, but makes great sense when the originator and consumer of the data are both Drupal. In the event you need to import data that started its life outside of Drupal, just subscribing to this one event should be enough to get access to the data and define how it should be imported.

.. _CDF Creation: #creating-global-cdf-attributes

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::POPULATE_CDF_ATTRIBUTES``

The dispatched event is ``Drupal\acquia_contenthub\Event\ParseCdfEntityEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\Cdf\ContentEntityHandler``
- ``Drupal\acquia_contenthub\EventSubscriber\Cdf\FileEntityHandler``
- ``Drupal\acquia_contenthub\EventSubscriber\Cdf\ConfigEntityHandler``

Handling Field Data
^^^^^^^^^^^^^^^^^^^

Field data is handled specially and has its own set of events which are dispatched to serialize and unserialize the field data. In addition to the normal serialization/unserialization process, these events can suppress field data from being exported at all. For example, ContentHub excludes the native serial ids associated to content entities for id and revision id. This essentially flattens the data sent into the hub so that it can be rehydrated and assigned new serial ids on the receiving site. The guiding principles in field data export are:

 - Consider all possible languages
 - Track entity references in all forms. If a field implicitly, or explicitly references another entity, ensure that the dependency tracking already knows about this, and use the entity's uuid for export.
 - The data must be able to be turned back into normal field level data during import.

 These events shouldn't matter unless you have created your own field type or are using a field from contrib that does not yet have ContentHub support. Many fields will be supported automatically by ContentHub's General and Fallback handlers. Only special fields with strange formats or entity reference implications should need special handling.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::SERIALIZE_CONTENT_ENTITY_FIELD`` to serialize field data.

The dispatched event is ``Drupal\acquia_contenthub\Event\SerializeCdfEntityFieldEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\SerializeContentField\EntityReferenceFieldSerializer``
- ``Drupal\acquia_contenthub\EventSubscriber\SerializeContentField\LanguageFieldSerializer``
- ``Drupal\acquia_contenthub\EventSubscriber\SerializeContentField\TextItemFieldSerializer``

For more examples of field serialization, look at all the files in ``acquia_contenthub/src/EventSubscriber/SerializeContentField``.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::UNSERIALIZE_CONTENT_ENTITY_FIELD`` to unserialize field data.

The dispatched event is ``Drupal\acquia_contenthub\Event\UnserializeContentFieldEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField\EntityReferenceField``
- ``Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField\EntityLanguage``
- ``Drupal\acquia_contenthub\EventSubscriber\UnserializeContentField\TextItemField``

For more examples of field unserialization, look at all the files in ``acquia_contenthub/src/EventSubscriber/UnserializeContentField``.

Tampering with Data
^^^^^^^^^^^^^^^^^^^

Data tampers are a feature of many import/migrate processes and ContentHub is no exception. The data about to be imported is offered up to the developer to manipulate as they see fit. This could be as complicated as remapping data from one entity bundle into another and updating or removing dependencies as necessary to support that action or as simple as just removing data from being imported if it hasn't changed since the last time it was imported. The ContentHub Subscriber module implements this latter scenario by comparing hash values of the entity versus the last time it was imported. If the hash has not changed, ContentHub will load the local version of that entity and add it to the `DependencyStack`_ object which will prevent the CDF representation from being processed for import.

.. _DependencyStack: dependencyStack.html

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::ENTITY_DATA_TAMPER``

The dispatched event is ``Drupal\acquia_contenthub\Event\EntityDataTamperEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\EntityDataTamper\DefaultLanguage``
- ``Drupal\acquia_contenthub\EventSubscriber\EntityDataTamper\AnonymousUser``
- ``Drupal\acquia_contenthub_subscriber\EventSubscriber\EntityDataTamper\ExistingEntity``

Entity Import New/Update
^^^^^^^^^^^^^^^^^^^^^^^^

Best practices generally dictate that everything required to make an entity work after saving happens during the save process. Unfortunately, this best practice is either not always followed, or cannot be followed for various technical reasons. In order to combat this and allow developers the necessary freedom to import entities of all types, ContentHub dispatches events which correspond to initial saves and updates. This allows logic that might normally have been invoked in, for example, a form submit method, to be copied and run as appropriate. While this is not the best case scenario for code, it's often an unfortunate necessity. Obviously, this isn't the only potential use case for these events, but they're the only ones for which core ContentHub has an implementation.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::ENTITY_IMPORT_NEW``
Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::ENTITY_IMPORT_UPDATE``

The dispatched event is ``Drupal\acquia_contenthub\Event\EntityImportEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\EntityImport\ContentLanguageSettings``

Data Pruning
^^^^^^^^^^^^

As part of normal operation, ContentHub will request all required content from the ContentHub Service before proceeding with an import operation. Sometimes, data which is gathered during this process is completely irrelevant to a site and can be discarded completely. An example of this is ``rendered_entity`` data. This is generated exclusively for the use of Acquia's Lift Personalization product, and there's no reason for any site to ever process that data. While the ContentHub modules do not actually subscribe to this event, it would be optional to do so and would save on processing entities queued for import which might never be imported.

Another important aspect of the prune event is that it happens before any other processing of the CDF, specifically module enabling, so it could be used to discard data for which you do not even have the modules present to support.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::PRUNE_CDF``

The dispatched event is ``Drupal\acquia_contenthub\Event\PruneCdfEntitiesEvent``.

Example Implementations:

- none

Webhook Handling
^^^^^^^^^^^^^^^^

As part of the interaction pattern with the ContentHub Service, webhooks are dispatched both to publishers and subscribers. These webhooks can indicate a number of different types of operations. Mostly the ContentHub Service sends webhooks which inform sites about new or updated content that should be imported. All of these incoming webhooks arrive at the same route within Drupal, and so to allow for multiple actors to inspect and act upon the incoming data, webhook handling dispatches an event. This allows modules to layer their own custom handling on top of the sane baseline of reactions that ContentHub will take to these incoming webhooks.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::HANDLE_WEBHOOK``

The dispatched event is ``Drupal\acquia_contenthub\Event\HandleWebhookEvent``.

Example Implementations:

- ``Drupal\acquia_contenthub\EventSubscriber\HandleWebhook\RegisterWebhook``
- ``Drupal\acquia_contenthub_publisher\EventSubscriber\HandleWebhook\UpdatePublished``
- ``Drupal\acquia_contenthub_subscriber\EventSubscriber\HandleWebhook\ImportUpdateAssets``

Handling Import Failures
^^^^^^^^^^^^^^^^^^^^^^^^

At the end of the day, no system, no matter how well tested and documented can operate flawlessly in all circumstances. Understanding that up front, it makes sense to handle failures as robustly as possible. The vast majority of failures within ContentHub are mostly likely to happen during the import process, and to that end, ContentHub dispatches an event when it detects a failure scenario. This is specifically invoked when ContentHub determines that it can no longer make forward progress with import process and that it is just needlessly spinning its wheels. At that point, it dispatches an event which contains the CDF document it was processing, the DependencyStack it built thus far and a count of items processed. This should give great insight into what failed, and might even allow for error handling that can finish a failed import under the right circumstances.

Subscribe to: ``Drupal\acquia_contenthub\AcquiaContentHubEvents::IMPORT_FAILURE``

The dispatched event is ``Drupal\acquia_contenthub\Event\FailedImportEvent``.

Example Implementations:

- none
