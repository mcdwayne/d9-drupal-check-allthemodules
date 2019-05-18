<?php

namespace Drupal\purest_content\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\views\Views;

/**
 * Converts the Drupal entity object structures to a normalized array.
 */
class ContentNormalizer extends ContentEntityNormalizer {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityTypeBundle;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeRepositoryInterface $entity_type_repository, ConfigFactoryInterface $config_factory, EntityTypeBundleInfo $entity_type_bundle, EntityFieldManager $entity_field_manager) {
    parent::__construct($entity_manager, $entity_type_repository, $entity_field_manager);
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('purest_content.settings');
    $this->entityManager = $entity_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundle = $entity_type_bundle;

    $this->entity_types = [
      'node' => $this->entityTypeBundle->getBundleInfo('node'),
      'taxonomy_term' => $this->entityTypeBundle->getBundleInfo('taxonomy_term'),
    ];

    $this->processedEntities = [];
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // If we aren't dealing with an object or the format is not
    // supported return now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    // This custom normalizer should be supported for all entities of type
    // node and taxonomy term.
    if ($data instanceof NodeInterface || $data instanceof TermInterface) {
      if ($data instanceof NodeInterface) {
        $bundle = $data->bundle();
        $this->entityConfig = $this
          ->configFactory->get('purest_content.node.' . $bundle);
        $normalize = $this->entityConfig->get('normalize');

        if (NULL !== $normalize && !$normalize) {
          return FALSE;
        }
      }

      if ($data instanceof TermInterface) {
        $bundle = $data->getVocabularyId();
        $this->entityConfig = $this
          ->configFactory->get('purest_content.taxonomy_term.' . $bundle);
        $normalize = $this->entityConfig->get('normalize');

        if (NULL !== $normalize && !$normalize) {
          return FALSE;
        }
      }

      return TRUE;
    }

    // Otherwise, this normalizer does not support the $data object.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $uuid = $entity->uuid();
    if (isset($this->processedEntities[$uuid])) {
      return $this->processedEntities[$uuid];
    }

    $fields = parent::normalize($entity, $format, $context);
    $entity_type = ($entity instanceof NodeInterface) ? 'node' : 'taxonomy_term';
    $bundle = $entity_type === 'node' ? $entity->bundle() : $entity->getVocabularyId();

    return $this->normalizeEntity(
      $uuid,
      $fields,
      $format,
      $context
    );
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeEntity($uuid, $fields, $format, array $context, $entity_config = NULL) {
    if ($entity_config !== NULL) {
      $entity_config_factory = $this->configFactory->get($entity_config);
      $rest_settings = $entity_config_factory->get('fields');
    }
    else {
      $rest_settings = $this->entityConfig->get('fields');
    }
    $output = [];

    foreach ($fields as $key => $field) {
      if (isset($rest_settings[$key])) {
        if (intval($rest_settings[$key]['exclude'])) {
          continue;
        }

        if (intval($rest_settings[$key]['hide_empty'])) {
          if ($field === NULL || empty($field)) {
            continue;
          }

          if ($key === 'path') {
            if (!isset($field['alias']) || ($field['alias'] === NULL)) {
              continue;
            }
          }
        }
      }

      $label = $key;

      if (isset($rest_settings[$key]['custom_label']) &&
              !empty($rest_settings[$key]['custom_label'])) {
        $label = $rest_settings[$key]['custom_label'];
      }

      // Handle entity reference fields for types nodes, taxonomy terms and
      // views.
      if (is_array($field)) {
        if (isset($field['purest_type']) || isset($field[0])
                          && isset($field[0]['purest_type'])) {
          if (!isset($this->purestSerializer)) {
            $this->purestSerializer = \Drupal::service('serializer');
          }

          $entities = $field;
          $cardinality = !isset($field['purest_type']) ? TRUE : FALSE;
          $processed_entities = [];

          if (!$cardinality) {
            $entities = [$field];
          }

          foreach ($entities as $entity_key => $entity_reference) {
            switch ($entity_reference['target_type']) {
              case 'node':
              case 'taxonomy_term':
                if (isset($this->processedEntities[$entity_reference['target_uuid']])) {
                  $processed_entities[] = $this->processedEntities[$entity_reference['target_uuid']];
                  continue;
                }

                $attached_entity = \Drupal::entityManager()
                  ->loadEntityByUuid($entity_reference['target_type'], $entity_reference['target_uuid']);

                switch ($entity_reference['target_type']) {
                  case 'node_type':
                    $entity_type = 'node';
                    $bundle = $attached_entity->bundle();
                    break;

                  case 'taxonomy_term':
                    $entity_type = 'taxonomy_term';
                    $bundle = $attached_entity->getVocabularyId();
                    break;
                }

                $config_id = 'purest_content.' . $entity_type . '.' . $bundle;
                $config_name = 'purest_config_' . $entity_type . '_' . $bundle;

                if (!isset($this->{$config_name})) {
                  $this->{$config_name} = $this
                    ->configFactory->get($config_id);
                }

                $attached_entity_fields = $this->purestSerializer
                  ->normalize($attached_entity, $format, $context);

                $processed_entities[] = $this->normalizeEntity(
                  $entity_reference['target_uuid'],
                  $attached_entity_fields,
                  $format,
                  $context,
                  $config_name
                );
                break;

              case 'view';
                // For entity reference fields that reference views, load the
                // view, find the first available rest_export display and
                // return the rendered view.
                $display_id = NULL;
                $view = Views::getView($entity_reference['target_id']);
                foreach ($view->storage->get('display') as $id => $display) {
                  if ($display['display_plugin'] === 'rest_export') {
                    $display_id = $id;
                    break;
                  }
                }

                if ($display_id !== NULL) {
                  $render_array = $view->buildRenderable($display_id);
                  $rendered = \Drupal::service('renderer')->render($render_array);
                  $json_string = $rendered->jsonSerialize();
                  $json_object = json_decode($rendered);
                  // @Todo check any fields returned in view for views entity reference fields
                  // and render any of them that should be.
                  $this->processEntities['view_' . $entity_reference['target_id']] = $json_object;
                  $processed_entities[$entity_reference['target_id']] = $json_object;
                }
                else {
                  $processed_entities[$entity_reference['target_id']] = NULL;
                }
                break;

            }
          }

          $fields[$key] = $cardinality ? $processed_entities : reset($processed_entities);
        }
      }

      $output[$label] = $fields[$key];
    }

    $this->processedEntities[$uuid] = $output;

    return $output;
  }

}
