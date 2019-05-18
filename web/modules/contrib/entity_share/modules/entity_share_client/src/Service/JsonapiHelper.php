<?php

namespace Drupal\entity_share_client\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_share_client\Entity\RemoteInterface;
use Drupal\entity_share_client\Event\RelationshipFieldValueEvent;
use Drupal\file\FileInterface;
use Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class JsonapiHelper.
 *
 * @package Drupal\entity_share_client\Service
 */
class JsonapiHelper implements JsonapiHelperInterface {
  use StringTranslationTrait;

  /**
   * The JsonApiDocumentTopLevelNormalizer normalizer.
   *
   * @var \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer
   */
  protected $jsonapiDocumentTopLevelNormalizer;

  /**
   * The resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * The bundle infos from the website.
   *
   * @var array
   */
  protected $bundleInfos;

  /**
   * The entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $entityDefinitions;

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The remote manager.
   *
   * @var \Drupal\entity_share_client\Service\RemoteManagerInterface
   */
  protected $remoteManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * A prepared HTTP client for file transfer.
   *
   * @var \GuzzleHttp\Client
   */
  protected $fileHttpClient;

  /**
   * A prepared HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The remote website on which to prepare the clients.
   *
   * @var \Drupal\entity_share_client\Entity\RemoteInterface
   */
  protected $remote;

  /**
   * The list of the currently imported entities.
   *
   * @var array
   */
  protected $importedEntities;

  /**
   * JsonapiHelper constructor.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   A serializer.
   * @param \Drupal\jsonapi\Normalizer\JsonApiDocumentTopLevelNormalizer $jsonapi_document_top_level_normalizer
   *   The JsonApiDocumentTopLevelNormalizer normalizer.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepository $resource_type_repository
   *   The resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entity_definition_update_manager
   *   The entity definition update manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\entity_share_client\Service\RemoteManagerInterface $remote_manager
   *   The remote manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(
    SerializerInterface $serializer,
    JsonApiDocumentTopLevelNormalizer $jsonapi_document_top_level_normalizer,
    ResourceTypeRepository $resource_type_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDefinitionUpdateManagerInterface $entity_definition_update_manager,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    LanguageManagerInterface $language_manager,
    RemoteManagerInterface $remote_manager,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->jsonapiDocumentTopLevelNormalizer = $jsonapi_document_top_level_normalizer;
    $this->jsonapiDocumentTopLevelNormalizer->setSerializer($serializer);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->bundleInfos = $entity_type_bundle_info->getAllBundleInfo();
    $this->entityDefinitions = $entity_type_manager->getDefinitions();
    $this->entityDefinitionUpdateManager = $entity_definition_update_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->languageManager = $language_manager;
    $this->remoteManager = $remote_manager;
    $this->eventDispatcher = $event_dispatcher;
    // TODO: Maybe use an API if the array is too big. State API, Tempstore or
    // Cache API.
    $this->importedEntities = [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntitiesOptions(array $json_data) {
    $options = [];
    foreach ($this->prepareData($json_data) as $data) {
      $this->addOptionFromJson($options, $data);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function extractEntity(array $data) {
    // Format JSON as in
    // JsonApiDocumentTopLevelNormalizerTest::testDenormalize().
    $prepared_json = [
      'data' => [
        'type' => $data['type'],
        'attributes' => $data['attributes'],
      ],
    ];
    $parsed_type = explode('--', $data['type']);

    return $this->jsonapiDocumentTopLevelNormalizer->denormalize($prepared_json, NULL, 'api_json', [
      'resource_type' => $this->resourceTypeRepository->get(
        $parsed_type[0],
        $parsed_type[1]
      ),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function updateRelationships(ContentEntityInterface $entity, array $data) {
    if (isset($data['relationships'])) {
      $resource_type = $this->resourceTypeRepository->get(
        $entity->getEntityTypeId(),
        $entity->bundle()
      );
      // Reference fields.
      foreach ($data['relationships'] as $field_name => $field_data) {
        $field_name = $resource_type->getInternalName($field_name);
        $field = $entity->get($field_name);
        if ($this->relationshipHandleable($field)) {
          $field_values = [];

          // Check that the field has data.
          if ($field_data['data'] != NULL && isset($field_data['links']['related']['href'])) {
            $referenced_entities_response = $this->getHttpClient()->get($field_data['links']['related']['href'])
              ->getBody()
              ->getContents();
            $referenced_entities_json = Json::decode($referenced_entities_response);

            if (!isset($referenced_entities_json['errors'])) {
              $referenced_entities_ids = $this->importEntityListData($referenced_entities_json['data']);

              $main_property = $field->getItemDefinition()->getMainPropertyName();

              // Add field metadatas.
              foreach ($this->prepareData($field_data['data']) as $key => $field_value_data) {
                // When dealing with taxonomy term entities which has a
                // hierarchy, there is a virtual entity for the root. So
                // $referenced_entities_ids[$key] may not exist.
                // See https://www.drupal.org/node/2976856.
                if (isset($referenced_entities_ids[$key])) {
                  $field_value = [
                    $main_property => $referenced_entities_ids[$key],
                  ];

                  if (isset($field_value_data['meta'])) {
                    $field_value += $field_value_data['meta'];
                  }

                  // Allow to alter the field value with an event.
                  $event = new RelationshipFieldValueEvent($field, $field_value);
                  $this->eventDispatcher->dispatch(RelationshipFieldValueEvent::EVENT_NAME, $event);
                  $field_values[] = $event->getFieldValue();
                }
              }
            }
          }
          $entity->set($field_name, $field_values);
        }
      }

      // Save the entity once all the references have been updated.
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handlePhysicalFiles(ContentEntityInterface $entity, array &$data) {
    if ($entity instanceof FileInterface) {
      // TODO: $resource_type->getPublicName().
      $remote_uri = $data['attributes']['uri']['value'];
      $remote_url = $data['attributes']['uri']['url'];
      $stream_wrapper = $this->streamWrapperManager->getViaUri($remote_uri);
      $directory_uri = $stream_wrapper->dirname($remote_uri);

      // Create the destination folder.
      if (file_prepare_directory($directory_uri, FILE_CREATE_DIRECTORY)) {
        // TODO: Check the case of large files.
        // TODO: Transfer file only if necessary.
        try {
          $file_content = $this->getFileHttpClient()->get($remote_url)
            ->getBody()
            ->getContents();
          file_put_contents($remote_uri, $file_content);
        }
        catch (ClientException $e) {
          drupal_set_message($this->t('Missing file: %url', ['%url' => $remote_url]), 'warning');
        }
      }
      else {
        drupal_set_message($this->t('Impossible to write in the directory %directory', ['%directory' => $directory_uri]), 'error');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setRemote(RemoteInterface $remote) {
    $this->remote = $remote;
  }

  /**
   * {@inheritdoc}
   */
  public function importEntityListData(array $entity_list_data) {
    $imported_entity_ids = [];
    foreach ($this->prepareData($entity_list_data) as $entity_data) {
      $parsed_type = explode('--', $entity_data['type']);
      $entity_type = $this->entityDefinitionUpdateManager->getEntityType($parsed_type[0]);
      $entity_keys = $entity_type->getKeys();

      $this->prepareEntityData($entity_data, $entity_keys);

      // TODO: $resource_type->getPublicName().
      $data_langcode = $entity_data['attributes'][$entity_keys['langcode']];

      // Prepare entity label.
      if (isset($entity_keys['label'])) {
        // TODO: $resource_type->getPublicName().
        $entity_label = $entity_data['attributes'][$entity_keys['label']];
      }
      else {
        // Use the entity type if there is no label.
        $entity_label = $parsed_type[0];
      }

      if (!$this->dataLanguageExists($data_langcode, $entity_label)) {
        continue;
      }

      // Check if an entity already exists.
      // JSONAPI no longer includes uuid in attributes so we're using id
      // instead. See https://www.drupal.org/node/2984247.
      $existing_entities = $this->entityTypeManager
        ->getStorage($parsed_type[0])
        ->loadByProperties(['uuid' => $entity_data['id']]);

      // Here is the supposition that we are importing a list of content
      // entities. Currently this is ensured by the fact that it is not possible
      // to make a channel on config entities and on users. And that in the
      // relationshipHandleable() method we prevent handling config entities and
      // users relationships.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->extractEntity($entity_data);

      // New entity.
      if (empty($existing_entities)) {
        $entity->save();
        $imported_entity_ids[] = $entity->id();
        // Prevent the entity of being reimported.
        $this->importedEntities[] = $entity->uuid();
        $this->updateRelationships($entity, $entity_data);
        $this->handlePhysicalFiles($entity, $entity_data);
        // Change the entity "changed" time because it could have been altered
        // with relationship save by example.
        if (method_exists($entity, 'setChangedTime')) {
          // TODO: $resource_type->getPublicName().
          $changed_datetime = \DateTime::createFromFormat(\DateTime::RFC3339, $entity_data['attributes']['changed']);
          $entity->setChangedTime($changed_datetime->getTimestamp());
        }
        $entity->save();
      }
      // Update the existing entity.
      else {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $existing_entity */
        $existing_entity = array_shift($existing_entities);
        $imported_entity_ids[] = $existing_entity->id();

        if (!in_array($existing_entity->uuid(), $this->importedEntities)) {
          // Prevent the entity of being reimported.
          $this->importedEntities[] = $existing_entity->uuid();
          $has_translation = $existing_entity->hasTranslation($data_langcode);
          // Update the existing translation.
          if ($has_translation) {
            $resource_type = $this->resourceTypeRepository->get(
              $entity->getEntityTypeId(),
              $entity->bundle()
            );
            $existing_translation = $existing_entity->getTranslation($data_langcode);
            foreach ($entity_data['attributes'] as $field_name => $value) {
              $field_name = $resource_type->getInternalName($field_name);
              $existing_translation->set(
                $field_name,
                $entity->get($field_name)->getValue()
              );
            }
            $existing_translation->save();
          }
          // Create the new translation.
          else {
            $translation = $entity->toArray();
            $existing_entity->addTranslation($data_langcode, $translation);
            $existing_entity->save();
            $existing_translation = $existing_entity->getTranslation($data_langcode);
          }
          $this->updateRelationships($existing_translation, $entity_data);
          $this->handlePhysicalFiles($existing_translation, $entity_data);
          // Change the entity "changed" time because it could have been altered
          // with relationship save by example.
          if (method_exists($existing_translation, 'setChangedTime')) {
            // TODO: $resource_type->getPublicName().
            $changed_datetime = \DateTime::createFromFormat(\DateTime::RFC3339, $entity_data['attributes']['changed']);
            $existing_translation->setChangedTime($changed_datetime->getTimestamp());
          }
          $existing_translation->save();
        }
      }
    }
    return $imported_entity_ids;
  }

  /**
   * Helper function to add an option.
   *
   * @param array $options
   *   The array of options for the tableselect form type element.
   * @param array $data
   *   An array of data.
   * @param int $level
   *   The level of indentation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \InvalidArgumentException
   */
  protected function addOptionFromJson(array &$options, array $data, $level = 0) {
    $indentation = '';
    for ($i = 1; $i <= $level; $i++) {
      $indentation .= '<div class="indentation">&nbsp;</div>';
    }

    $parsed_type = explode('--', $data['type']);
    $entity_type_id = $parsed_type[0];
    $bundle_id = $parsed_type[1];

    $entity_type = $this->entityDefinitionUpdateManager->getEntityType($entity_type_id);
    $entity_keys = $entity_type->getKeys();

    $label = new FormattableMarkup($indentation . '@label', [
      // TODO: $resource_type->getPublicName().
      '@label' => $data['attributes'][$entity_keys['label']],
    ]);

    $status_info = $this->getStatusInfo($data, $entity_type_id, $entity_keys);

    $options[$data['id']] = [
      'label' => $label,
      'type' => $entity_type->getLabel(),
      'bundle' => $this->bundleInfos[$entity_type_id][$bundle_id]['label'],
      'language' => $this->getEntityLanguageLabel($data, $entity_keys),
      'status' => $status_info['label'],
      '#attributes' => [
        'class' => [
          $status_info['class'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareData(array $data) {
    if ($this->isNumericArray($data)) {
      return $data;
    }
    else {
      return [$data];
    }
  }

  /**
   * Check if a array is numeric.
   *
   * @param array $array
   *   The array to check.
   *
   * @return bool
   *   TRUE if the array is numeric. FALSE in case of associative array.
   */
  protected function isNumericArray(array $array) {
    foreach ($array as $a => $b) {
      if (!is_int($a)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Check if a relationship is handleable.
   *
   * Filter on fields not targeting config entities or users.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item list.
   *
   * @return bool
   *   TRUE if the relationship is handleable.
   */
  protected function relationshipHandleable(FieldItemListInterface $field) {
    $relationship_handleable = FALSE;

    if ($field instanceof EntityReferenceFieldItemListInterface) {
      $settings = $field->getItemDefinition()->getSettings();

      // TODO: Other field types that inherit from entity reference should be
      // handled automatically or using a plugin/event system if possible.
      // Entity reference and Entity reference revisions.
      if (isset($settings['target_type'])) {
        $relationship_handleable = !$this->isUserOrConfigEntity($settings['target_type']);
      }
      // Dynamic entity reference.
      elseif (isset($settings['entity_type_ids'])) {
        foreach ($settings['entity_type_ids'] as $entity_type_id) {
          $relationship_handleable = !$this->isUserOrConfigEntity($entity_type_id);
          if (!$relationship_handleable) {
            break;
          }
        }
      }
    }

    return $relationship_handleable;
  }

  /**
   * Helper function to get the language from an extracted entity.
   *
   * We can't use $entity->language() because if the entity is in a language not
   * enabled, it is the site default language that is returned.
   *
   * @param array $data
   *   The data from the JSON API payload.
   * @param array $entity_keys
   *   The entity keys from the entity definition.
   *
   * @return string
   *   The language of the entity.
   */
  protected function getEntityLanguageLabel(array $data, array $entity_keys) {
    if (!isset($entity_keys['langcode'])) {
      return $this->t('Untranslatable entity');
    }

    // TODO: $resource_type->getPublicName().
    $langcode = $data['attributes'][$entity_keys['langcode']];
    $language = $this->languageManager->getLanguage($langcode);
    // Check if the entity is in an enabled language.
    if (is_null($language)) {
      $language_list = LanguageManager::getStandardLanguageList();
      if (isset($language_list[$langcode])) {
        $entity_language = $language_list[$langcode][0] . ' ' . $this->t('(not enabled)', [], ['context' => 'language']);
      }
      else {
        $entity_language = $this->t('Entity in an unsupported language.');
      }
    }
    else {
      $entity_language = $language->getName();
    }

    return $entity_language;
  }

  /**
   * Helper function to get the File Http Client.
   *
   * @return \GuzzleHttp\Client
   *   A HTTP client to retrieve files.
   */
  protected function getFileHttpClient() {
    if (!$this->fileHttpClient) {
      $this->fileHttpClient = $this->remoteManager->prepareClient($this->remote);
    }

    return $this->fileHttpClient;
  }

  /**
   * Helper function to get the Http Client.
   *
   * @return \GuzzleHttp\Client
   *   A HTTP client to request JSONAPI endpoints.
   */
  protected function getHttpClient() {
    if (!$this->httpClient) {
      $this->httpClient = $this->remoteManager->prepareJsonApiClient($this->remote);
    }

    return $this->httpClient;
  }

  /**
   * Prepare the data array before extracting the entity.
   *
   * Used to remove some data.
   *
   * @param array $data
   *   An array of data.
   * @param array $entity_keys
   *   An array of entity keys.
   */
  protected function prepareEntityData(array &$data, array $entity_keys) {
    // TODO: Refactor with extract_entity method.
    $parsed_type = explode('--', $data['type']);

    $resource_type = $this->resourceTypeRepository->get(
      $parsed_type[0],
      $parsed_type[1]
    );

    // Removes some ids.
    unset($data['attributes'][$resource_type->getPublicName($entity_keys['id'])]);
    if (isset($entity_keys['revision']) && !empty($entity_keys['revision'])) {
      unset($data['attributes'][$resource_type->getPublicName($entity_keys['revision'])]);
    }

    // UUID is no longer included as attribute.
    // TODO: Test that we need it to have the UUID preserved.
    $data['attributes'][$resource_type->getPublicName($entity_keys['uuid'])] = $data['id'];

    // Remove the default_langcode boolean to be able to import content not
    // necessarily in the default language.
    // TODO: Handle content_translation_source?
    unset($data['attributes'][$resource_type->getPublicName($entity_keys['default_langcode'])]);

    // To avoid side effects and as currently JSONAPI send null for the path
    // we remove the path attribute.
    // TODO: $resource_type->getPublicName().
    if (isset($data['attributes']['path'])) {
      unset($data['attributes']['path']);
    }
  }

  /**
   * Check if we try to import an entity in a disabled language.
   *
   * @param string $langcode
   *   The langcode of the language to check.
   * @param string $entity_label
   *   The entity label.
   *
   * @return bool
   *   FALSE if the data is not in an enabled language.
   */
  protected function dataLanguageExists($langcode, $entity_label) {
    if (is_null($this->languageManager->getLanguage($langcode))) {
      drupal_set_message($this->t('Trying to import an entity (%entity_label) in a disabled language.', [
        '%entity_label' => $entity_label,
      ]), 'error');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if an entity already exists or not and compare revision timestamp.
   *
   * @param array $data
   *   The data from the JSON API payload.
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $entity_keys
   *   The entity keys from the entity definition.
   *
   * @return array
   *   Returns an array of info:
   *     - class: to add a class on a row.
   *     - label: the label to display.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \InvalidArgumentException
   */
  protected function getStatusInfo(array $data, $entity_type_id, array $entity_keys) {
    $status_info = [
      'label' => $this->t('Undefined'),
      'class' => 'entity-share-undefined',
    ];

    // Check if an entity already exists.
    $existing_entities = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->loadByProperties(['uuid' => $data['id']]);

    if (empty($existing_entities)) {
      $status_info = [
        'label' => $this->t('New entity'),
        'class' => 'entity-share-new',
      ];
    }
    // An entity already exists.
    // Check if the entity type has a changed date.
    else {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $existing_entity */
      $existing_entity = array_shift($existing_entities);

      if (method_exists($existing_entity, 'getChangedTime')) {
        // TODO: $resource_type->getPublicName().
        $changed_datetime = \DateTime::createFromFormat(\DateTime::RFC3339, $data['attributes']['changed']);
        $entity_changed_time = $changed_datetime->getTimestamp();

        if (isset($entity_keys['langcode'])) {
          // TODO: $resource_type->getPublicName().
          $entity_language_id = $data['attributes'][$entity_keys['langcode']];

          // Entity has the translation.
          if ($existing_entity->hasTranslation($entity_language_id)) {
            $existing_translation = $existing_entity->getTranslation($entity_language_id);
            $existing_entity_changed_time = $existing_translation->getChangedTime();

            // Existing entity.
            if ($entity_changed_time != $existing_entity_changed_time) {
              $status_info = [
                'label' => $this->t('Entities not synchronized'),
                'class' => 'entity-share-changed',
              ];
            }
            else {
              $status_info = [
                'label' => $this->t('Entities synchronized'),
                'class' => 'entity-share-up-to-date',
              ];
            }
          }
          else {
            $status_info = [
              'label' => $this->t('New translation'),
              'class' => 'entity-share-new',
            ];
          }
        }
        // Case of untranslatable entity.
        else {
          $existing_entity_changed_time = $existing_entity->getChangedTime();

          // Existing entity.
          if ($entity_changed_time != $existing_entity_changed_time) {
            $status_info = [
              'label' => $this->t('Entities not synchronized'),
              'class' => 'entity-share-changed',
            ];
          }
          else {
            $status_info = [
              'label' => $this->t('Entities synchronized'),
              'class' => 'entity-share-up-to-date',
            ];
          }
        }
      }
    }

    return $status_info;
  }

  /**
   * Helper function to check if an entity type id is a user or a config entity.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return bool
   *   TRUE if the entity type is user or a config entity. FALSE otherwise.
   */
  protected function isUserOrConfigEntity($entity_type_id) {
    if ($entity_type_id == 'user') {
      return TRUE;
    }
    elseif ($this->entityDefinitions[$entity_type_id]->getGroup() == 'configuration') {
      return TRUE;
    }

    return FALSE;
  }

}
