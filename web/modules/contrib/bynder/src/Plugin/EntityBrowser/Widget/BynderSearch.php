<?php

namespace Drupal\bynder\Plugin\EntityBrowser\Widget;

use Drupal\bynder\BynderApiInterface;
use Drupal\bynder\Exception\UnableToConnectException;
use Drupal\bynder\Plugin\media\Source\Bynder;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_browser\Element\EntityBrowserPagerElement;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Uses a Bynder API to search and provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "bynder_search",
 *   label = @Translation("Bynder search"),
 *   description = @Translation("Adds an Bynder search field browser's widget.")
 * )
 */
class BynderSearch extends BynderWidgetBase {

  /**
   * Limits the amount of tags returned in the Media browser filter.
   */
  const TAG_LIST_LIMIT = 25;

  /**
   * Account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * BynderSearch constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\bynder\BynderApiInterface $bynder_api
   *   Bynder API service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   Account proxy.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Url generator.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, BynderApiInterface $bynder_api, AccountProxyInterface $account_proxy, UrlGeneratorInterface $url_generator, LoggerChannelFactoryInterface $logger_factory, LanguageManagerInterface $language_manager, RequestStack $request_stack, QueryFactory $entity_query, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, TimeInterface $time, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager, $bynder_api, $logger_factory, $language_manager, $request_stack, $config_factory);
    $this->accountProxy = $account_proxy;
    $this->urlGenerator = $url_generator;
    $this->entityQuery = $entity_query;
    $this->cache = $cache;
    $this->time = $time;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_browser.widget_validation'),
      $container->get('bynder_api'),
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('logger.factory'),
      $container->get('language_manager'),
      $container->get('request_stack'),
      $container->get('entity.query'),
      $container->get('config.factory'),
      $container->get('cache.data'),
      $container->get('datetime.time'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items_per_page' => 15,
      'tags_filter' => TRUE,
      'allowed_properties' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['items_per_page'] = [
      '#type' => 'select',
      '#title' => $this->t('Items per page'),
      '#default_value' => $this->configuration['items_per_page'],
      '#options' => ['10' => 10, '15' => 15, '25' => 25, '50' => 50],
    ];

    $form['tags_filter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable tags filter'),
      '#default_value' => $this->configuration['tags_filter'],
    ];

    foreach ($this->entityTypeManager->getStorage('media_type')->loadMultiple() as $type) {
      /** @var \Drupal\media\MediaTypeInterface $type */
      if ($type->getSource() instanceof Bynder) {
        $form['media_type']['#options'][$type->id()] = $type->label();
      }
    }

    if (empty($form['media_type']['#options'])) {
      $form['media_type']['#disabled'] = TRUE;
      $form['items_per_page']['#disabled'] = TRUE;
      $form['media_type']['#description'] = $this->t('You must @create_type before using this widget.', [
        '@create_type' => Link::createFromRoute($this->t('create a Bynder media type'), 'entity.media_type.add_form')
          ->toString(),
      ]);
    }

    try {
      $options = [];
      foreach ($this->bynderApi->getMetaproperties() as $key => $meta_property) {
        if ($meta_property['isFilterable']) {
          $options[$key] = bynder_get_applicable_label_translation($meta_property);
        }
      }

      $form['allowed_properties'] = [
        '#type' => 'select',
        '#title' => $this->t('Allowed metadata properties'),
        '#description' => $this->t('Select filters that should be available in the Entity Browser widget.'),
        '#multiple' => TRUE,
        '#default_value' => $this->configuration['allowed_properties'],
        '#options' => $options,
      ];
    }
    catch (\Exception $e) {
      (new UnableToConnectException())->logException()->displayMessage();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    if (!$this->checkType()) {
      return [];
    }
    $media = [];
    $selected_ids = array_keys(array_filter($form_state->getValue('selection', [])));
    /** @var \Drupal\media\MediaTypeInterface $type */
    $type = $this->entityTypeManager->getStorage('media_type')
      ->load($this->configuration['media_type']);
    $plugin = $type->getSource();
    $source_field = $plugin->getConfiguration()['source_field'];
    foreach ($selected_ids as $bynder_id) {
      $mid = $this->entityQuery->get('media')
        ->condition($source_field, $bynder_id)
        ->range(0, 1)
        ->execute();
      if ($mid) {
        $media[] = $this->entityTypeManager->getStorage('media')
          ->load(reset($mid));
      }
      else {
        $media[] = Media::create([
          'bundle' => $type->id(),
          $source_field => $bynder_id,
        ]);
      }
    }
    return $media;
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    if ($form_state->getValue('errors')) {
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    $form['#attached']['library'][] = 'bynder/search_view';

    $form['filters'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => ['class' => 'bynder-filters'],
    ];

    $form['filters']['search_bynder'] = [
      '#type' => 'textfield',
      '#weight' => -1,
      '#title' => $this->t('Search keyword'),
      '#attributes' => [
        'size' => 30,
      ],
    ];

    if ($this->configuration['tags_filter']) {
      try {
        /*
         * Warning: update caching config if changing this API call.
         *
         * @see \Drupal\bynder\BynderApi::AUTO_UPDATED_TAGS_QUERIES
         * @see \Drupal\bynder\BynderApi::getTags().
         */
        $all_tags = array_map(
          function ($item) { return $item['tag']; },
          $this->bynderApi->getTags([
            'limit' => 200,
            'orderBy' => 'mediaCount desc',
            'minCount' => 1,
          ]
        ));

        $tags = array_combine($all_tags, $all_tags);
        asort($tags);
      }
      catch (\Exception $e) {
        (new UnableToConnectException())->logException()->displayMessage();
        $form['actions']['submit']['#access'] = FALSE;
        return $form;
      }

      if (\Drupal::service('module_handler')->moduleExists('bynder_select2')) {
        $form['filters']['tags'] = [
          '#type' => 'bynder_select2_simple_element',
          '#multiple' => TRUE,
          '#title' => $this->t('Tags'),
          '#options' => $tags,
          '#placeholder_text' => t('Search for a tag'),
        ];
      }
      else {
        $form['filters']['tags'] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#title' => $this->t('Tags'),
          '#options' => $tags,
        ];
      }
    }

    $max_option_weight = 0;
    try {
      $meta_properties = $this->bynderApi->getMetaproperties();
    }
    catch (\Exception $e) {
      (new UnableToConnectException())->logException()->displayMessage();
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    foreach ($this->configuration['allowed_properties'] as $key) {
      // Don't display filter that is not filterable and has no options.
      if (in_array($key, array_keys($meta_properties)) && ($option = $meta_properties[$key]) && $option['isFilterable']) {
        $form['filters'][$option['name']] = [
          '#title' => bynder_get_applicable_label_translation($option),
          '#type' => 'select',
          '#multiple' => $option['isMultiselect'] ? TRUE : FALSE,
          '#required' => $option['isRequired'] ? TRUE : FALSE,
          '#weight' => $option['zindex'],
          '#empty_option' => $this->t('- None -'),
          '#parents' => ['filters', 'meta_properties', $option['name']],
        ];
        foreach ($option['options'] as $value) {
          $form['filters'][$option['name']]['#options'][$value['id']] = bynder_get_applicable_label_translation($value);
        }
        // Get the biggest weight from our filters so we position the other
        // elements after them.
        $max_option_weight = max($max_option_weight, $option['zindex']);
      }
    }

    $form['search_button'] = [
      '#type' => 'button',
      '#weight' => $max_option_weight + 10,
      '#value' => $this->t('Search'),
      '#name' => 'search_submit',
    ];

    $form['thumbnails'] = [
      '#type' => 'container',
      '#weight' => $max_option_weight + 15,
      '#attributes' => ['id' => 'thumbnails', 'class' => 'grid'],
    ];

    if ($form_state->getTriggeringElement()['#name'] == 'search_submit') {
      EntityBrowserPagerElement::setCurrentPage($form_state);
    }
    $page = EntityBrowserPagerElement::getCurrentPage($form_state);

    $query = [
      'limit' => $this->configuration['items_per_page'],
      'page' => $page,
      'type' => 'image',
      'total' => 1,
    ];

    if ($form_state->getValue(['filters', 'search_bynder'])) {
      $query['keyword'] = $form_state->getValue(['filters', 'search_bynder']);
    }

    foreach ($form_state->getValue(['filters', 'meta_properties'], []) as $key => $option_id) {
      if (is_array($option_id) && $option_id) {
        $property_ids = implode(',', $option_id);
        $query['property_' . $key] = $property_ids;
      }
      elseif (is_string($option_id) && $option_id) {
        $property_ids = $option_id;
        $query['property_' . $key] = $property_ids;
      }
    }

    if ($selected_tags = $form_state->getValue(['filters', 'tags'])) {
      $selected_tags = array_values($selected_tags);
      $query['tags'] = implode(',', $selected_tags);
    }

    try {
      // We store last result into the form state to prevent same requests
      // happening multiple times if not necessary.
      if (!empty($form_state->get('bynder_media_list_hash')) && ($form_state->get('bynder_media_list_hash') == md5(implode('', $query)))) {
        $media_list = $form_state->get('bynder_media_list');
      }
      else {
        $media_list = $this->bynderApi->getMediaList($query);
        $form_state->set('bynder_media_list', $media_list);
        $form_state->set('bynder_media_list_hash', md5(implode('', $query)));
      }
    }
    catch (\Exception $e) {
      (new UnableToConnectException())->logException()->displayMessage();
      $form['actions']['submit']['#access'] = FALSE;
      return $form;
    }

    if (!empty($media_list['media'])) {
      foreach ($media_list['media'] as $media) {
        $form['thumbnails']['thumbnail-' . $media['id']] = [
          '#type' => 'container',
          '#attributes' => ['id' => $media['id'], 'class' => ['grid-item']],
        ];
        $form['thumbnails']['thumbnail-' . $media['id']]['check_' . $media['id']] = [
          '#type' => 'checkbox',
          '#parents' => ['selection', $media['id']],
          '#attributes' => ['class' => ['item-selector']],
        ];
        $form['thumbnails']['thumbnail-' . $media['id']]['image'] = [
          '#theme' => 'bynder_search_item',
          '#thumbnail_uri' => $media['thumbnails']['thul'],
          '#name' => $media['name'],
          '#type' => $media['type'],
        ];
      }

      $form['pager_eb'] = [
        '#type' => 'entity_browser_pager',
        '#total_pages' => (int) ceil($media_list['total']['count'] / $this->configuration['items_per_page']),
        '#weight' => $max_option_weight + 15,
      ];

      // Set validation errors limit to prevent validation of filters on select.
      // We also need to set #submit to the default submit callback otherwise
      // limit won't take effect. Thank you Form API, you are very kind...
      // @see \Drupal\Core\Form\FormValidator::determineLimitValidationErrors()
      $form['actions']['submit']['#limit_validation_errors'] = [['selection']];
      $form['actions']['submit']['#submit'] = ['::submitForm'];
    }
    else {
      $form['empty_message'] = [
        '#prefix' => '<div class="empty-message">',
        '#markup' => $this->t('Not assets found for current search criteria.'),
        '#suffix' => '</div>',
        '#weight' => $max_option_weight + 20,
      ];
      $form['actions']['submit']['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      try {
        /*
         * We store info about media assets that we already fetched so the media
         * entity plugin can use them and avoid doing more API requests.
         *
         * @see \Drupal\bynder\Plugin\MediaEntity\Type\Bynder::getField()
         */
        $selected_ids = array_keys(array_filter($form_state->getValue('selection', [])));
        if ($api_list = $form_state->get('bynder_media_list')) {
          foreach ($api_list['media'] as $api_item) {
            foreach ($selected_ids as $selected_id) {
              if ($api_item['id'] == $selected_id) {
                $this->cache->set('bynder_item_' . $selected_id, $api_item, ($this->time->getRequestTime() + 120));
              }
            }
          }
        }

        $media = $this->prepareEntities($form, $form_state);
        array_walk($media, function (MediaInterface $media_item) {
          $media_item->save();
        });
        $this->selectEntities($media, $form_state);
      }
      catch (\UnexpectedValueException $e) {
        drupal_set_message($this->t('Bynder integration is not configured correctly. Please contact the site administrator.'), 'error');
      }
    }
  }

}
