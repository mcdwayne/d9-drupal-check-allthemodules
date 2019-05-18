<?php

namespace Drupal\entity_content_export\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_content_export\BatchExport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Define entity content export form.
 */
class EntityContentExport extends FormBase {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * @var array
   */
  protected $serializerFormats;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Define entity content export form constructor.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param array $serializer_formats
   */
  public function __construct(
    RendererInterface $renderer,
    SerializerInterface $serializer,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    array $serializer_formats
  ) {
    $this->renderer = $renderer;
    $this->serializer = $serializer;
    $this->setConfigFactory($config_factory);
    $this->serializerFormats = $serializer_formats;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('renderer'),
      $container->get('serializer'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_content_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->getExportableEntityOptions();

    if (empty($options)) {
      $this->messenger()->addError(
        $this->t('No entity type bundles have been configured to be exported.')
      );
    }
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Export Type'),
      '#required' => TRUE,
      '#options' => $options,
    ];
    $form['format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Exported Format'),
      '#description' => $this->t('Select the exported format.'),
      '#options' => array_combine(
        $this->serializerFormats, $this->serializerFormats
      ),
      '#required' => TRUE,
    ];
    $form['actions']['#type'] = 'actions';

    $form['actions']['export'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!isset($values['type']) || !isset($values['format'])) {
      return;
    }
    list($entity_type, $bundle) = explode(':', $values['type']);

    $ids = $this->getEntityIds($entity_type, $bundle);
    $display = $this->getEntityViewDisplay($entity_type, $bundle);

    $batch = [
      'title' => $this->t('Exporting @entity_type of type @bundle', [
        '@bundle' => $bundle,
        '@entity_type' => $entity_type,
      ]),
      'operations' => [
        [
          '\Drupal\entity_content_export\BatchEntityExport::serializeEntityStructuredData',
          [$values['format'], $entity_type, $ids, $this, $display]
        ],
      ],
      'finished' => '\Drupal\entity_content_export\BatchEntityExport::finishedCallback',
    ];
    batch_set($batch);
  }

  /**
   * Get the serializer instance.
   *
   * @return \Symfony\Component\Serializer\SerializerInterface
   *   The serializer instance.
   */
  public function getSerializer() {
    return $this->serializer;
  }

  /**
   * Build entity export data structure.
   *
   * @param $entity_type
   *   The entity type.
   * @param array $entity_ids
   *   An array of entity ids.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity display instance.
   *
   * @return array
   *   An structured array of the exported data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildEntityExportDataStructure(
    $entity_type,
    array $entity_ids,
    EntityViewDisplayInterface $display
  ) {
    $storage = $this
      ->entityTypeManager
      ->getStorage($entity_type);

    $display_settings = $display->getThirdPartySettings(
      'entity_content_export'
    );
    $data_structure = [];

    foreach ($storage->loadMultiple($entity_ids) as $entity_id => $entity) {
      if (!$entity instanceof ContentEntityInterface) {
        continue;
      }
      $data_structure[$entity_id] = [];

      foreach ($display->getComponents() as $field_name => $display_options) {
        if (!isset($entity->{$field_name})) {
          continue;
        }
        $definition = $entity->getFieldDefinition($field_name);

        // Filter out base fields if they're not defined in the display
        // entity content export settings.
        if ($definition instanceof BaseFieldDefinition
          && !isset($display_settings['base_fields'][$field_name])) {
          continue;
        }
        $elements = $entity
          ->{$field_name}
          ->view($display_options);

        $components_settings = isset($display_settings['components'][$field_name])
          ? $display_settings['components'][$field_name]
          : [];

        if (isset($components_settings['render'])
          && $components_settings['render'] === 'value') {
          $elements = array_intersect_key(
            $elements, array_flip(Element::children($elements))
          );
        }
        $name = isset($components_settings['name'])
          ? $components_settings['name']
          : $field_name;

        $data_structure[$entity_id][$name] = (string) $this->renderer->render($elements);
      }
    }

    return $data_structure;
  }

  /**
   * Get exportable entity options.
   *
   * @return array
   *   An array of exportable entity options.
   */
  protected function getExportableEntityOptions() {
    $options = [];

    foreach ($this->getConfiguration()->get('entity_type_bundles') as $entity_type_bundle) {
      list($entity_type, $bundle) = explode(':', $entity_type_bundle);

      $options[$entity_type_bundle] = $this->t('@entity_type: @bundle', [
        '@bundle' => $this->capitalizeWords($bundle),
        '@entity_type' => $this->capitalizeWords($entity_type)
      ]);
    }

    return $options;
  }

  /**
   * Get entity identifiers.
   *
   * @param $entity_type
   *   The entity type.
   * @param $type
   *   The entity bundle type.
   *
   * @return array|int
   *   An array of entity identifiers for the given entity type and bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityIds($entity_type, $type) {
    $storage = $this->entityTypeManager->getStorage($entity_type);

    return $storage
      ->getQuery()
      ->condition('type', $type)
      ->execute();
  }

  /**
   * Get entity view display.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The entity bundle type.
   * @param string $default_mode
   *   The entity default view mode.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity view display instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityViewDisplay($entity_type, $bundle, $default_mode = 'default') {
    $view_mode = $this->getEntityViewDisplayModeFromConfig(
      $entity_type, $bundle, $default_mode
    );

    return $this
      ->entityTypeManager
      ->getStorage('entity_view_display')
      ->load($view_mode);
  }

  /**
   * Get entity view display mode from configuration.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle
   *   The entity bundle type.
   * @param string $default_mode
   *   The entity default mode.
   *
   * @return string
   *   The entity view display mode.
   */
  protected function getEntityViewDisplayModeFromConfig($entity_type, $bundle, $default_mode = 'default') {
    $bundle_config = $this->getConfiguration()
      ->get("entity_bundle_configuration.{$entity_type}.{$bundle}");

    return isset($bundle_config['display_mode'])
      ? $bundle_config['display_mode']
      : "{$entity_type}.{$bundle}.{$default_mode}";
  }

  /**
   * Capitalize words in a string.
   *
   * @param $string
   *   The string to capitalize.
   *
   * @return string
   *   The transformed string.
   */
  protected function capitalizeWords($string) {
    return Unicode::ucwords(strtr($string, '_', ' '));
  }

  /**
   * Get entity content export settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration instance.
   */
  protected function getConfiguration() {
    return $this->configFactory->get('entity_content_export.settings');
  }
}
