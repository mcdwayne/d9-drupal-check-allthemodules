<?php

namespace Drupal\bynder\Plugin\Field\FieldFormatter;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\Entity\MediaType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Bynder' formatter.
 *
 * @FieldFormatter(
 *   id = "bynder",
 *   label = @Translation("Bynder"),
 *   field_types = {"string", "string_long", "entity_reference"}
 * )
 */
class BynderFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The Bynder API service.
   *
   * @var \Drupal\bynder\BynderApiInterface
   */
  protected $bynder;

  /**
   * Renderer object.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a BynderFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\bynder\BynderApiInterface $bynder
   *   The Bynder API service.
   * @param \Drupal\Core\Render\RendererInterface $renderer_object
   *   Renderer object.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, BynderApiInterface $bynder, RendererInterface $renderer_object, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->bynder = $bynder;
    $this->renderer = $renderer_object;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('bynder_api'),
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'thumbnail' => 'webimage',
      'alt_field' => '',
      'title_field' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $derivatives = array_merge(
      ['mini', 'webimage', 'thul'],
      array_map(function ($item) { return $item['prefix']; }, $this->bynder->getDerivatives())
    );

    $elements['thumbnail'] = [
      '#type' => 'select',
      '#options' => array_combine($derivatives, $derivatives),
      '#title' => $this->t('Derivative'),
      '#description' => $this->t('Select the name of the derivative to be used to display the image. Besides custom derivatives that you created in Bynder there are also default thumbnail sizes available that can be used. Go to @form and reload derivatives.', ['@form' => Link::createFromRoute($this->t('Bynder configuration form'), 'bynder.configuration_form')->toString()]),
      '#default_value' => $this->getSetting('thumbnail'),
    ];

    $field_candidates = $this->getAttributesFieldsCandidates();
    $elements['alt_field'] = [
      '#type' => 'select',
      '#options' => $field_candidates,
      '#title' => $this->t('Alt attribute field'),
      '#description' => $this->t('Select the name of the field that should be used for the "alt" attribute of the image.'),
      '#default_value' => $this->getSetting('alt_field'),
      '#empty_value' => '',
    ];

    $elements['title_field'] = [
      '#type' => 'select',
      '#options' => $field_candidates,
      '#title' => $this->t('Title attribute field'),
      '#description' => $this->t('Select the name of the field that should be used for the "title" attribute of the image.'),
      '#default_value' => $this->getSetting('alt_field'),
      '#empty_value' => '',
    ];

    return $elements;
  }

  /**
   * Gets list of fields that are candidates for IMG attributes.
   *
   * @return array
   *   List of fields with machine names as keys and human-readable names as
   *   values.
   */
  protected function getAttributesFieldsCandidates() {
    $allowed_field_types = ['string', 'string_long', 'text', 'text_long'];
    $handler_settings = NULL;

    if (strpos($this->fieldDefinition->getSetting('handler'), 'default:') === 0) {
      $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    }

    $bundles = [];
    if ($handler_settings && is_array($handler_settings['target_bundles'])) {
      foreach ($handler_settings['target_bundles'] as $bundle) {
        /** @var \Drupal\media\MediaTypeInterface $type */
        $type = $this->entityTypeManager->getStorage('media_type')->load($bundle);
        if ($type && ($type->getSource() instanceof Bynder)) {
          $bundles[] = $type;
        }
      }
    }
    else {
      /** @var \Drupal\media\MediaTypeInterface $type */
      foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $type) {
        if ($type && ($type->getSource() instanceof Bynder)) {
          $bundles[] = $type;
        }
      }
    }

    $options = [];
    foreach ($bundles as $type) {
      foreach ($this->entityFieldManager->getFieldDefinitions('media', $type->id()) as $field_name => $field) {
        if (in_array($field->getType(), $allowed_field_types)) {
          $options[$field_name] = $field->getLabel();
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $settings = $this->getSettings();
    $summary[] = $this->t('Derivative: @style', ['@style' => $settings['thumbnail']]);

    $field_candidates = $this->getAttributesFieldsCandidates();
    if (empty($settings['title_field'])) {
      $summary[] = $this->t('Title attribute not displayed (not recommended).');
    }
    else {
      $summary[] = $this->t('Title attribute field: @field', ['@field' => $field_candidates[$settings['title_field']]]);
    }

    if (empty($settings['alt_field'])) {
      $summary[] = $this->t('Alt attribute not displayed (not recommended).');
    }
    else {
      $summary[] = $this->t('Alt attribute field: @field', ['@field' => $field_candidates[$settings['alt_field']]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $element = [];
    $is_entityreference = $this->fieldDefinition->getType() == 'entity_reference';

    foreach ($items as $delta => $item) {
      /** @var \Drupal\media\MediaInterface $media_entity */
      if ($media_entity = $is_entityreference ? $item->entity : $items->getEntity()) {
        /** @var \Drupal\media\MediaSourceInterface $source_plugin */
        $source_plugin = $media_entity->getSource();
        if ($source_plugin instanceof Bynder && ($thumbnails = $source_plugin->getMetadata($media_entity, 'thumbnail_urls'))) {
          $element['#attached']['library'][] = 'bynder/formatter';
          $element[$delta]['bynder_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['bynder-wrapper'],
            ],
          ];
          $thumbnail_uri = isset($thumbnails[$settings['thumbnail']]) ? $thumbnails[$settings['thumbnail']] : $thumbnails['webimage'];
          $element[$delta]['bynder_wrapper']['image'] = [
            '#theme' => 'image',
            '#uri' => $thumbnail_uri,
            '#attributes' => ['class' => ['bynder-image']],
            // TODO width, height - we don't have this info (unless we
            // download the thumbnail). Would be nice to have support in the
            // API.
          ];
          if ($settings['title_field'] && $media_entity->hasField($settings['title_field']) && !$media_entity->get($settings['title_field'])->isEmpty()) {
            $element[$delta]['bynder_wrapper']['image']['#title'] = $media_entity->get($settings['title_field'])->value;
          }
          if ($settings['alt_field'] && $media_entity->hasField($settings['alt_field']) && !$media_entity->get($settings['alt_field'])->isEmpty()) {
            $element[$delta]['bynder_wrapper']['image']['#alt'] = $media_entity->get($settings['alt_field'])->value;
          }
          $this->renderer->addCacheableDependency($element[$delta]['bynder_wrapper']['image'], $item);
          $element[$delta]['bynder_wrapper']['usage_image'] = [
            '#theme' => 'image',
            '#uri' => \Drupal::moduleHandler()->getModule('bynder')->getPath() . '/images/icons/bynder-logo.png',
            '#alt' => 'usage-image',
            // @todo Information is not available yet. Fix when API supports it.
            '#title' => $this->t('Usage info is not available yet. Usage restriction level: @restriction', [
              '@restriction' => get_media_restriction($source_plugin->getMetadata($media_entity, 'propertyOptions')),
            ]),
            '#attributes' => ['class' => ['usage-image']],
            '#access' => AccessResult::allowedIfHasPermission($this->currentUser, 'view bynder media usage'),
          ];
          $this->renderer->addCacheableDependency($element[$delta]['bynder_wrapper']['usage_image'], $this->configFactory->get('bynder.settings'));
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getType() == 'entity_reference') {
      if ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media') {
        if (strpos($field_definition->getSetting('handler'), 'default:') === 0) {
          $handler_settings = $field_definition->getSetting('handler_settings');
          if ($handler_settings['target_bundles'] === NULL) {
            return TRUE;
          }
          elseif (is_array($handler_settings['target_bundles'])) {
            foreach ($handler_settings['target_bundles'] as $bundle) {
              /** @var \Drupal\media\MediaTypeInterface $type */
              $type = \Drupal::entityTypeManager()->getStorage('media_type')->load($bundle);
              if ($type->getSource() instanceof Bynder) {
                return TRUE;
              }
            }
          }
        }
        else {
          // If some other selection plugin than default is used we can't
          // reliably determine if we apply or not so we allow.
          return TRUE;
        }
      }

      return FALSE;
    }
    elseif (in_array($field_definition->getType(), ['string', 'string_long'])) {
      if ($field_definition->getTargetEntityTypeId() != 'media') {
        return FALSE;
      }

      /** @var \Drupal\media\MediaTypeInterface $type_entity */
      if (!($type_entity = $field_definition->getTargetBundle()) || !($type_entity = MediaType::load($field_definition->getTargetBundle()))) {
        return FALSE;
      }

      $source = $type_entity->getSource();
      if (!($source instanceof Bynder)) {
        return FALSE;
      }

      if ($source->getConfiguration()['source_field'] != $field_definition->getName()) {
        return FALSE;
      }

      return TRUE;
    }
  }

}
