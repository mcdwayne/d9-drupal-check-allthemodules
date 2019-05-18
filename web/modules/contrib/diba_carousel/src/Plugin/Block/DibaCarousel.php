<?php

namespace Drupal\diba_carousel\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Diba carousel Block.
 *
 * @Block(
 *   id = "diba_carousel",
 *   admin_label = @Translation("Diba carousel"),
 *   category = @Translation("Content")
 * )
 */
class DibaCarousel extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return [
      '#theme' => 'block__diba_carousel',
      'slides' => $this->getSlides($config),
      'id'     => Html::getUniqueId('diba_carousel'),
      'config' => $config,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(
      parent::getCacheContexts(),
      ['user', 'languages:language_interface']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(
      parent::getCacheTags(),
      ['node_list', 'config:block.block.diba_carousel']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display'              => FALSE,
      'entity_selected'            => 'node',
      'content_types'              => [],
      'publishing_options'         => ['status' => 1],
      'skip_content_without_image' => FALSE,
      'image'                      => 'field_image',
      'title'                      => 'title',
      'image_style'                => '',
      'url'                        => 'canonical',
      'description'                => 'body',
      'description_allow_html'     => FALSE,
      'description_see_more_link'  => FALSE,
      'description_truncate'       => 300,
      'order_field'                => 'created',
      'order_direction'            => 'DESC',
      'limit'                      => 5,
      'filter_by_field'            => '',
      'filter_by_field_operator'   => '=',
      'filter_by_field_value'      => '',
      'carousel_style'             => 'default',
      'more_link'                  => '',
      'more_link_text'             => 'See more',
      'data_interval'              => 5000,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $defaults = $this->defaultConfiguration();
    // If this method receives a subform state instead of the full form state.
    // @See https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $ajax_values = $form_state->getCompleteFormState()->getValues();
    }
    else {
      $ajax_values = $form_state->getValues();
    }

    // Ensure strongly that entity type is not null.
    if (isset($ajax_values['settings']['diba_carousel_settings']['content_selection']['entity_selected'])) {
      $config['entity_selected'] = $ajax_values['settings']['diba_carousel_settings']['content_selection']['entity_selected'];
    }
    if (empty($config['entity_selected'])) {
      $config['entity_selected'] = $defaults['entity_selected'];
    }

    // Ensure some field is rendered in slides.
    if (empty($config['image']) && empty($config['title']) && empty($config['description'])) {
      $config['title'] = $defaults['title'];
    }

    // Settings structure.
    $form['diba_carousel_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Diba carousel configuration'),
      '#description' => $this->t('Configure the content selection and the slide fields used in diba carousel.'),
      '#attributes' => ['id' => 'diba-carousel-wrapper'],
    ];
    $form['diba_carousel_settings']['content_selection'] = [
      '#type' => 'details',
      '#title' => $this->t('Content selection and ordering'),
      '#description' => $this->t('Filter, limit and sort the contents shown on slides.'),
      '#open' => TRUE,
      '#attributes' => ['id' => 'content-selection-wrapper'],
    ];
    $form['diba_carousel_settings']['slide_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Slide fields and styling'),
      '#description' => $this->t('Assign the fields used as image, title, description and link on slides.'),
      '#open' => TRUE,
      '#attributes' => ['id' => 'slide-fields-wrapper'],
    ];

    // Content selection.
    $form['diba_carousel_settings']['content_selection']['entity_selected'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#default_value' => $config['entity_selected'],
      '#options' => $this->getEntityTypes(),
      '#description' => $this->t('Check the entity that you want to use in the carousel. Default: node.'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'ajaxFormSettingsCallback'],
        'wrapper'  => 'diba-carousel-wrapper',
        'event'    => 'change',
      ],
    ];

    $bundles = $this->getEntityTypeBundles($config['entity_selected']);
    if (!empty($bundles)) {
      // Wrap field with a div form-type-radios class to correct inline display.
      // See: https://www.drupal.org/project/drupal/issues/2992299
      $form['diba_carousel_settings']['content_selection']['content_types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Entity type bundles'),
        '#default_value' => !empty($config['content_types']) ? $config['content_types'] : [],
        '#options' => $bundles,
        '#description' => $this->t('Check the node content types that you want to filter in the carousel. If you uncheck all options, all nodes will be displayed if "node" entity type is checked and none if "node" entity is unchecked.'),
        '#prefix' => '<div class="form-type-radios">',
        '#sufix' => '</div>',
        '#attributes' => ['class' => ['container-inline']],
        '#validated' => TRUE,
      ];
    }

    $options = [
      'status' => $this->t('Published'),
      'promote' => $this->t('Promoted to front page'),
      'sticky' => $this->t('Sticky at top of lists'),
    ];
    if ('node' === $config['entity_selected']) {
      // Add custom publishing options (custom_pub module integration).
      if ($this->moduleHandler->moduleExists('custom_pub')) {
        $publish_types = $this->entityTypeManager
          ->getStorage('custom_publishing_option')
          ->loadMultiple();
        foreach ($publish_types as $publish_type) {
          $options[$publish_type->id()] = $publish_type->label();
        }
      }
    }
    $form['diba_carousel_settings']['content_selection']['publishing_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Publishing options'),
      '#default_value' => !empty($config['publishing_options']) ? $config['publishing_options'] : [],
      '#options' => $options,
      '#description' => $this->t('Publishing options to filter content in the carousel.'),
      '#validated' => TRUE,
    ];

    $form['diba_carousel_settings']['content_selection']['skip_content_without_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip content without image'),
      '#default_value' => $config['skip_content_without_image'],
      '#description' => $this->t('Ensure that all carousel content has image.'),
    ];

    $form['diba_carousel_settings']['content_selection']['filter_by_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by field'),
      '#options' => $this->getFields($config['entity_selected'], FALSE),
      '#default_value' => $config['filter_by_field'],
      '#description' => $this->t('Select the field you want to use as filter.'),
      '#empty_option' => $this->t('- None -'),
    ];

    $form['diba_carousel_settings']['content_selection']['filter_by_field_operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by field operator'),
      '#options' => [
        '=' => $this->t('Equal'),
        '<>' => $this->t('Not equal'),
        'CONTAINS' => $this->t('Contains'),
        '>' => $this->t('Greater than'),
        '>=' => $this->t('Greater than or equal'),
        '<' => $this->t('Less than'),
        '<=' => $this->t('Less than or equal'),
      ],
      '#default_value' => $config['filter_by_field_operator'],
      '#description' => $this->t('Select the comparasion operator to use in filter field.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][content_selection][filter_by_field]"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['diba_carousel_settings']['content_selection']['filter_by_field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter by field value'),
      '#default_value' => $config['filter_by_field_value'],
      '#description' => $this->t('Select the value you want to use as field filter. If you filter by field that contains taxonomy terms or relational content you should us the tid or entity id as value.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][content_selection][filter_by_field]"]' => ['!value' => ''],
        ],
      ],
    ];

    $option_types = ['integer', 'created', 'changed', 'datetime'];
    $form['diba_carousel_settings']['content_selection']['order_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Order by'),
      '#options' => $this->getFieldsByType($option_types, $config['entity_selected']),
      '#default_value' => $config['order_field'],
      '#empty_option' => $this->t('- None -'),
      '#validated' => TRUE,
    ];

    $form['diba_carousel_settings']['content_selection']['order_direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Order direction'),
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
        'RANDOM' => $this->t('Random'),
      ],
      '#default_value' => $config['order_direction'],
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][content_selection][order_field]"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['diba_carousel_settings']['content_selection']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Max number of elements'),
      '#default_value' => $config['limit'],
      '#description' => $this->t('The maximum number of elements to show in the carousel.'),
    ];

    // Slide fields and styling.
    $form['diba_carousel_settings']['slide_fields']['carousel_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Carousel layout style'),
      '#options' => [
        'default' => $this->t('Bootstrap default'),
        'without-indicators' => $this->t('Bootstrap without indicators'),
        'controls' => $this->t('Bootstrap with controls'),
        'diba' => $this->t('Diba - left captations'),
      ],
      '#default_value' => $config['carousel_style'],
      '#description' => $this->t('The carousel style controls de visualization and carousel templating.'),
      '#required' => TRUE,
    ];

    $form['diba_carousel_settings']['slide_fields']['image'] = [
      '#type' => 'select',
      '#title' => $this->t('Image field'),
      '#options' => $this->getFieldsByType(['image'], $config['entity_selected']),
      '#default_value' => $config['image'],
      '#empty_option' => $this->t('- None -'),
      '#validated' => TRUE,
    ];

    $form['diba_carousel_settings']['slide_fields']['image_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Image style'),
      '#options' => $this->getImageStyles(),
      '#default_value' => $config['image_style'],
      '#empty_option' => $this->t('- None -'),
      '#description' => $this->t('Use an image style for scale, resize or crop images.'),
    ];

    $form['diba_carousel_settings']['slide_fields']['title'] = [
      '#type' => 'select',
      '#title' => $this->t('Title field'),
      '#options' => $this->getFieldsByType(['string'], $config['entity_selected']),
      '#default_value' => $config['title'],
      '#empty_option' => $this->t('- None -'),
      '#validated' => TRUE,
    ];

    $url_options = ['canonical' => $this->t('Link to entity content')];
    $url_options = array_merge($url_options, $this->getFieldsByType(['link'], $config['entity_selected']));
    $form['diba_carousel_settings']['slide_fields']['url'] = [
      '#type' => 'select',
      '#title' => $this->t('Link field'),
      '#options' => $url_options,
      '#default_value' => $config['url'],
      '#empty_option' => $this->t('- None -'),
      '#validated' => TRUE,
    ];

    $option_types = ['text_with_summary', 'text_long', 'string'];
    $form['diba_carousel_settings']['slide_fields']['description'] = [
      '#type' => 'select',
      '#title' => $this->t('Description field'),
      '#options' => $this->getFieldsByType($option_types, $config['entity_selected']),
      '#default_value' => $config['description'],
      '#empty_option' => $this->t('- None -'),
      '#validated' => TRUE,
    ];

    $form['diba_carousel_settings']['slide_fields']['description_allow_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow html description'),
      '#default_value' => $config['description_allow_html'],
      '#description' => $this->t('If you use html description and truncate, the carousel will attempt to close the open tags.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][slide_fields][description]"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['diba_carousel_settings']['slide_fields']['description_see_more_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add see more link'),
      '#default_value' => $config['description_see_more_link'],
      '#description' => $this->t('Add "See more" link at the end of description linking canonical entity.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][slide_fields][description]"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['diba_carousel_settings']['slide_fields']['description_truncate'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of characters in description field'),
      '#default_value' => $config['description_truncate'],
      '#description' => $this->t('Truncates the description to a maximum number of characters. Truncation attempts to truncate on a word boundary. Use 0 for unlimited.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][slide_fields][description]"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['diba_carousel_settings']['slide_fields']['more_link'] = [
      '#type' => 'url',
      '#title' => $this->t('More link'),
      '#default_value' => $config['more_link'],
      '#description' => $this->t('This will add a more link to the bottom of the carousel.'),
    ];

    $form['diba_carousel_settings']['slide_fields']['more_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('More link text'),
      '#default_value' => $config['more_link_text'],
      '#description' => $this->t('More link text.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[diba_carousel_settings][slide_fields][more_link]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $form['diba_carousel_settings']['slide_fields']['data_interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Slides interval'),
      '#default_value' => (int) $config['data_interval'],
      '#min' => 0,
      '#description' => $this->t('The amount of time (in ms) to delay between automatically cycling an item. If 0, carousel will not automatically cycle.'),
    ];

    return $form;
  }

  /**
   * Ajax callback to update diba carousel settings.
   */
  public function ajaxFormSettingsCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['settings']['diba_carousel_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Set the new configuration.
    $config = $form_state->getValues();
    if (isset($config['diba_carousel_settings'])) {

      // Update content selection settings.
      $content_selection = [
        'entity_selected',
        'content_types',
        'publishing_options',
        'skip_content_without_image',
        'order_field',
        'order_direction',
        'limit',
        'filter_by_field',
        'filter_by_field_operator',
        'filter_by_field_value',
      ];
      foreach ($content_selection as $config_field) {
        if (isset($config['diba_carousel_settings']['content_selection'][$config_field])) {
          $this->setConfigurationValue(
            $config_field,
            $config['diba_carousel_settings']['content_selection'][$config_field]
          );
        }
      }

      // Update slide fields settings.
      $slide_fields = [
        'carousel_style',
        'image',
        'image_style',
        'title',
        'url',
        'description',
        'description_allow_html',
        'description_see_more_link',
        'description_truncate',
        'more_link',
        'more_link_text',
        'data_interval',
      ];
      foreach ($slide_fields as $config_field) {
        if (isset($config['diba_carousel_settings']['slide_fields'][$config_field])) {
          $this->setConfigurationValue(
            $config_field,
            $config['diba_carousel_settings']['slide_fields'][$config_field]
          );
        }
      }

      // Force diba_carousel block cache reload and refresh block output.
      Cache::invalidateTags(['config:block.block.diba_carousel']);
    }
  }

  /**
   * Get entity types list.
   */
  private function getEntityTypes() {
    $entities = [];
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    if (!empty($entity_definitions)) {
      foreach ($entity_definitions as $definition) {
        // Ensure that the entity type is fieldable.
        if ($definition instanceof ContentEntityType && $definition->get('field_ui_base_route')) {
          $entities[$definition->id()] = $definition->id();
        }
      }
    }

    return $entities;
  }

  /**
   * Get fields grouped by node type list.
   */
  private function getFields($entity_type = 'node', $grouped = TRUE) {
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type);
    $options = [];
    foreach ($fields as $field) {
      $label = $field->getLabel();
      $name = $field->getName();
      $type = $field->getType();
      if ($grouped) {
        $options[$type][$name] = $label . ' (' . $name . ')';
      }
      else {
        $options[$name] = $label . ' (' . $name . ')';
      }
    }

    return $options;
  }

  /**
   * Get image styles list.
   */
  private function getImageStyles() {
    $styles = $this->entityTypeManager->getStorage('image_style')->loadMultiple();
    $options = [];
    foreach ($styles as $key => $style) {
      $options[$key] = $key;
    }

    return $options;
  }

  /**
   * Get fields filtered by type.
   */
  private function getFieldsByType($types, $entity = 'node') {
    $fields = $this->getFields($entity);

    $options = [];
    foreach ($types as $type) {
      if (isset($fields[$type])) {
        $options = array_merge($options, $fields[$type]);
      }
    }

    return $options;
  }

  /**
   * Get all entity type bundles.
   */
  private function getEntityTypeBundles($entity) {
    $options = [];

    $entity_type = $this->entityTypeManager->getDefinition($entity)->getBundleEntityType();
    if (!empty($entity_type)) {
      $entity_type_bundles = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
      if (!empty($entity_type_bundles)) {
        foreach ($entity_type_bundles as $entity_type_bundle) {
          $options[$entity_type_bundle->id()] = $entity_type_bundle->label();
        }
      }
    }

    return $options;
  }

  /**
   * Use the block settings to query entities.
   */
  private function getQueriedEntities($config) {
    $entities = [];

    $entity_type = !empty($config['entity_selected']) ? $config['entity_selected'] : 'node';
    // Get the storage and init the query builder.
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery();

    // Filter by content types in nodes.
    if (!empty($config['content_types'])) {
      // Validate that the entity type bundle exists to prevent crash the site
      // when user deletes a content types.
      $bundles = $this->getValidBundles($config['content_types'], $config['entity_selected']);
      $entity_keys = $this->entityTypeManager->getDefinition($entity_type)->get('entity_keys');
      if (!empty($bundles) && isset($entity_keys['bundle'])) {
        $query->condition($entity_keys['bundle'], array_values($bundles), 'IN');
      }
    }

    // Skip content without image.
    if (isset($config['skip_content_without_image']) && TRUE === $config['skip_content_without_image']) {
      if (!empty($config['image'])) {
        $query->condition($config['image'], NULL, 'IS NOT NULL');
      }
    }

    // Filter by publishing options.
    if (!empty($config['publishing_options'])) {
      foreach ($config['publishing_options'] as $key => $value) {
        if (!empty($value)) {
          // Add query condition and filter by publishing option.
          $query->condition($key, 1);
        }
      }
    }

    // Filter by field value.
    if (!empty($config['filter_by_field']) && isset($config['filter_by_field_value'])) {
      $operator = !empty($config['filter_by_field_operator']) ? $config['filter_by_field_operator'] : '=';
      $query->condition($config['filter_by_field'], $config['filter_by_field_value'], $operator);
    }

    // We don't need an order field to order by rand.
    if (isset($config['order_direction']) && 'RANDOM' === $config['order_direction']) {
      $query->addTag('random_order');
    }
    elseif (!empty($config['order_field'])) {
      if ('ASC' === $config['order_direction'] || 'DESC' === $config['order_direction']) {
        $query->sort($config['order_field'], $config['order_direction']);
      }
      else {
        $query->sort($config['order_field']);
      }
    }

    // Limit the query.
    if (!empty($config['limit'])) {
      $query->range(0, $config['limit']);
    }

    // Execute the query and get the IDs.
    $entity_ids = $query->execute();

    if (!empty($entity_ids)) {
      $entities = $storage->loadMultiple($entity_ids);
    }

    return $entities;
  }

  /**
   * Get valid $bundles to be used in a query.
   */
  private function getValidBundles($bundles, $entity) {
    $entityBundles = $this->getEntityTypeBundles($entity);
    $valid_bundles = [];
    foreach ($bundles as $key => $value) {
      if (!empty($value) && isset($entityBundles[$key])) {
        $valid_bundles[] = $key;
      }
    }

    return $valid_bundles;
  }

  /**
   * Return slide fields from entity.
   */
  private function composeSlide($config, $entity) {
    // Slide title.
    $title = '';
    if (!empty($config['title'])) {
      $title = strip_tags($entity->{$config['title']}->value);
    }

    // Slide captation/description.
    $description = '';
    if (!empty($config['description']) && isset($entity->{$config['description']})) {
      $description = $entity->{$config['description']}->value;

      // If not allow HTML strip tags and convert some html entities.
      if (!$config['description_allow_html']) {
        $description = str_replace('&nbsp;', ' ', strip_tags($description));
      }

      // Truncate coptation in a safe mode.
      if (!empty($config['description_truncate']) && $config['description_truncate'] > 0) {
        $description = Unicode::truncate($description, $config['description_truncate'], TRUE, TRUE);
        if ($config['description_allow_html']) {
          $description = Html::normalize($description);
        }
      }
      // Trim spaces, tabs and other special chars.
      $description = trim($description, " \t\n\r\0\x0B\xC2\xA0");

      // Add see more link to entity.
      if ($config['description_see_more_link']) {
        // Ensure that entity has a canonical link.
        if ($see_more_target = $entity->toUrl('canonical')) {
          $description .= new FormattableMarkup(' <a href="@link" class="see-more-description">@label.</a>', [
            '@link'  => $see_more_target->toString(),
            '@label' => $this->t('See more'),
          ]);
        }
      }
    }

    // Slide url.
    $url = '';
    if ('canonical' === $config['url'] || 'nid' === $config['url']) {
      $url = $entity->toUrl('canonical');
    }
    elseif (!empty($config['url']) && isset($entity->{$config['url']}) && !$entity->{$config['url']}->isEmpty()) {
      $url = $entity->{$config['url']}->first()->getUrl();
    }

    // Slide image.
    $image_width = $image_height = $image_uri = '';
    if (!empty($config['image']) && isset($entity->{$config['image']})) {
      $image_obj = $entity->{$config['image']}->entity;
      if (!empty($image_obj)) {
        $image_uri = $image_obj->getFileUri();
      }
      else {
        // Image not found, try the default image.
        $default_image = $entity->{$config['image']}->getSetting('default_image');
        if (!empty($default_image) && isset($default_image['uuid'])) {
          $default_entity = $this->entityRepository->loadEntityByUuid('file', $default_image['uuid']);
          if (!empty($default_entity)) {
            $image_uri = $default_entity->getFileUri();
          }
        }
      }
      if (!empty($image_uri)) {
        // Use an image style instead of the original file.
        if (!empty($config['image_style'])) {
          $style = ImageStyle::load($config['image_style']);
          $image_derivative = $style->buildUri($image_uri);
          // Create derivative if necessary.
          if (!file_exists($image_derivative)) {
            $style->createDerivative($image_uri, $image_derivative);
          }
          $image_uri = $image_derivative;
        }

        // Check if the image is valid.
        $image = $this->imageFactory->get($image_uri);
        if ($image->isValid()) {
          $image_width = $image->getWidth();
          $image_height = $image->getHeight();
        }
      }
    }

    return [
      'image'        => $image_uri,
      'image_width'  => $image_width,
      'image_height' => $image_height,
      'title'        => $title,
      'url'          => $url,
      'description'  => $description,
    ];
  }

  /**
   * Get the carousel slides.
   */
  private function getSlides($config) {
    $slides = [];

    $entities = $this->getQueriedEntities($config);
    if (!empty($entities)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      foreach ($entities as $entity) {
        if ($entity->access('view')) {
          if ($entity->hasTranslation($langcode)) {
            $entity = $entity->getTranslation($langcode);
          }
          $slides[] = $this->composeSlide($config, $entity);
        }
      }
    }

    return $slides;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_field.manager'),
      $container->get('image.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $container->get('language_manager')
    );
  }

  /**
   * Diba carousel constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The field manager.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, EntityFieldManagerInterface $entity_field_manager, ImageFactory $image_factory, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->entityFieldManager = $entity_field_manager;
    $this->imageFactory = $image_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
  }

}
