<?php

namespace Drupal\media_elvis\Plugin\EntityBrowser\Widget;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\entity_browser\Element\EntityBrowserPagerElement;
use Drupal\media_elvis\MediaElvisServicesInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media_entity\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Integrates with Woodwing Media Elvis library.
 *
 * @EntityBrowserWidget(
 *   id = "media_elvis",
 *   label = @Translation("Media Elvis"),
 *   description = @Translation("Integrates with Woodwing Media Elvis library")
 * )
 */
class MediaElvis extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * @var string
   */
  const FOLDER_OPTION_NONE = '';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\media_elvis\MediaElvisServicesInterface
   */
  protected $elvisMediaServices;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'server_uri' => NULL,
      'username' => NULL,
      'password' => NULL,
      'per_page' => 15,
      'upload_location' => 'public://elvis-media/[date:custom:Y]-[date:custom:m]',
      'fetch_size' => 'previewUrl',
      'fetch_size_other' => '',
      'output' => '',
      'media_entity_bundle' => '',
    ) + parent::defaultConfiguration();
  }

  /**
   * Constructs a new View object.
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
   *   The entity type manager.
   * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
   *   The Widget Validation Manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\media_elvis\MediaElvisServicesInterface $media_elvis_services
   *   Media Elvis services service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_channel_factory
   *   Logger channel factory.
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, WidgetValidationManager $validation_manager, AccountInterface $current_user, MediaElvisServicesInterface $media_elvis_services, RequestStack $request_stack, FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger_channel_factory, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_type_manager, $validation_manager);
    $this->currentUser = $current_user;
    $this->elvisMediaServices = $media_elvis_services;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->fileSystem = $file_system;
    $this->logger = $logger_channel_factory;
    $this->token = $token;
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
      $container->get('current_user'),
      $container->get('media_elvis.services'),
      $container->get('request_stack'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('token')
    );
  }

  /**
   * Search default values.
   *
   * @return array
   *   Array of defaults.
   */
  public function getSearchDefaults() {
    return [
      'elvis_keyword' => '',
      'elvis_folder' => '',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);
    $this->elvisMediaServices->setBaseUri($this->configuration['server_uri']);
    $this->elvisMediaServices->setCredentialsData($this->configuration['username'], $this->configuration['password']);
    $form['#tree'] = TRUE;

    // @todo
    //   When the form is submitted we issue service calls too.
    //   We should probably avoid that somehow.
    $this->buildSearch($form, $form_state);
    $this->buildResults($form, $form_state);

    return $form;
  }

  /**
   * Build search form part of the browser form.
   *
   * @param array $form
   *   The widget form form.
   */
  protected function buildSearch(&$form, FormStateInterface $form_state) {
    $search_params = $this->getCurrentParams($form_state);

    $form['search'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Search'),
    ];

    // Keyword search.
    $form['search']['keyword'] = [
      '#type' => 'search',
      '#title' => $this->t('Keyword'),
      '#default_value' => $search_params['elvis_keyword'],
      '#description' => $this->t('A single word such as "hello" or a group of words surrounded by double quotes such as "hello world".'),
    ];

    // We get an error if we specify the / both as folder and root.
    $root = empty($search_params['elvis_folder']) ? '' : '/';
    $folders = $this->elvisMediaServices->browse($search_params['elvis_folder'], $root);

    if ($folders) {
      $form['search']['tree'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Folders'),
        '#suffix' => '<div class="clearfix"></div>',
        'folders' => $this->recursiveTreeBuilder($folders, $search_params['elvis_folder']),
      ];
    }

    $form['search']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#submit' => [[$this, 'searchSubmit']],
    ];

    $form['search']['clear'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear'),
      '#submit' => [[$this, 'clearSubmit']],
    ];
  }

  /**
   * Traverses tree and builds the render array.
   *
   * @param array $folders
   *   The folders branch.
   * @param string $current_folder
   *   The path currently used.
   * @param int $weight
   *   (optional) The wait for the select group.
   *
   * @return array
   *   The render array for this branch.
   */
  protected function recursiveTreeBuilder(array $folders, $current_folder, $weight = 0) {
    $tree = [];
    $options = [];
    $default_value = self::FOLDER_OPTION_NONE;

    foreach ($folders as $folder) {
      if (isset($folder->children)) {
        $weight--;
        $tree['children'] = $this->recursiveTreeBuilder($folder->children, $current_folder, $weight);
      }

      $options[$folder->assetPath] = $folder->name;

      if (strpos($current_folder, $folder->name) !== FALSE) {
        $default_value = $folder->assetPath;
      }
    }

    if ($options) {
      $tree['select'] = [
        '#type' => 'select',
        '#options' => [self::FOLDER_OPTION_NONE => $this->t('- None - ')] + $options,
        '#default_value' => $default_value,
        '#weight' => $weight,
        '#attributes' => ['onchange' => "jQuery('#edit-widget-search-submit').click()"],
      ];
    }

    return $tree;
  }

  /**
   * Build search form part of the browser form.
   *
   * @param array $form
   *   The widget form form.
   */
  protected function buildResults(&$form, FormStateInterface $form_state) {
    $search_params = $this->getCurrentParams($form_state);

    // Execute the search. Elvis pages start with 0 so we have to -1 here.
    $page = EntityBrowserPagerElement::getCurrentPage($form_state) - 1;
    $per_page = $this->configuration['per_page'];
    $offset = $page * $per_page;

    // @todo
    //   This is a very simple implementation and could be improved. Elvis
    //   uses lucene syntax so we might find an existing parser for that.
    $search_query_elements = [];

    if (!empty($search_params['elvis_keyword'])) {
      $search_query_elements[] = $search_params['elvis_keyword'];
    }

    if (!empty($search_params['elvis_folder'])) {
      $search_query_elements[] = 'ancestorPaths:"' . $search_params['elvis_folder'] . '"';
    }

    $search_query = !empty($search_query_elements) ? implode(' AND ', $search_query_elements) : '';
    $search_results = $this->elvisMediaServices->search($search_query, $offset, $per_page);

    $form['results'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['elvis-search-results']],
      '#attached' => [
        'library' => ['media_elvis/integration'],
      ],
    ];

    if (isset($search_results->totalHits) && $search_results->totalHits != 0) {
      foreach ($search_results->hits as $key => $result) {
        $form['results'][$key] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['elvis-grid-item']],
          'checkbox' => [
            '#type' => 'checkbox',
            '#return_value' => json_encode($result),
          ],
          'preview' => [
            '#theme' => 'elvis_search_result',
            '#src' => $result->thumbnailUrl,
            '#title' => isset($result->metadata->title) ? $result->metadata->title : '',
            '#dimension' => isset($result->metadata->dimension) ? $result->metadata->dimension : $this->t('n/a'),
          ],
        ];
      }

      $form['pager'] = [
        '#type' => 'entity_browser_pager',
        '#total_pages' => ceil($search_results->totalHits / $per_page),
      ];
    }
  }

  /**
   * Search form submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function searchSubmit(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['widget']['search'];

    $current_params = $this->getCurrentParams($form_state);
    $form_state->set('media_elvis', [
      'elvis_keyword' => $values['keyword'],
      'elvis_folder' => $this->getSubmittedFolder($values['tree']['folders'], $current_params['elvis_folder']),
    ]);

    EntityBrowserPagerElement::setCurrentPage($form_state);
    $form_state->setRebuild();
  }

  /**
   * Clear form submit callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function clearSubmit(array $form, FormStateInterface $form_state) {
    $defaults = $this->getSearchDefaults();
    $form_state->setUserInput(['widget', 'search', 'keyword'], $defaults['elvis_keyword']);
    $form_state->set('media_elvis', $this->getSearchDefaults());
    EntityBrowserPagerElement::setCurrentPage($form_state);
    $form_state->setRebuild();
  }

  /**
   * Recursive method to get at the bottom of the tree browser values.
   *
   * @param array $values
   *   The values array.
   *
   * @return string
   *   The selected value.
   */
  protected function getSubmittedFolder(array $values, $current_folder) {
    if (($values['select'] !== self::FOLDER_OPTION_NONE) && isset($values['children'])) {
      $changed = !empty($values['children']['select']) && strpos($current_folder, $values['children']['select']) === FALSE;
      if ($changed && $values['children']['select'] !== self::FOLDER_OPTION_NONE) {
        $selected = $values['children']['select'];
      }
      elseif ($values['children']['select'] === self::FOLDER_OPTION_NONE) {
        $selected = $values['select'];
      }
      else {
        $selected = $this->getSubmittedFolder($values['children'], $current_folder);
      }
    }
    else {
      $selected = $values['select'];
    }

    return $selected;
  }

  /**
   * Utility function that gets Elvis search params and entity browser params.
   *
   * @return array
   *   Array of search parameters.
   */
  protected function getCurrentParams(FormStateInterface $form_state) {
    $current = $form_state->get('media_elvis') ?: [];
    return array_merge($this->getSearchDefaults(), $current);
  }


  /**
   * {@inheritdoc}
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    $selected = [];
    if (isset($user_input['widget']['results'])) {
      foreach ($user_input['widget']['results'] as $result) {
        if (!empty($result['checkbox'])) {
          $selected[] = $result['checkbox'];
        }
      }
    }

    // If there weren't any errors set, run the normal validators.
    if (empty($form_state->getErrors())) {
      $form_state->set('elvis_selected', $selected);
      parent::validate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $entities = [];

    // Prepare destination.
    $destination = $this->getUploadLocation();
    if (!file_exists($destination)) {
      $this->fileSystem->mkdir($destination, NULL, TRUE);
    }

    if ($selected = $form_state->get('elvis_selected', [])) {
      foreach ($selected as $selected_item_json) {
        $selected_item = json_decode($selected_item_json);
        try {
          $fetch_size = ($this->configuration['fetch_size'] == '' && !empty($this->configuration['fetch_size_other'])) ? $this->configuration['fetch_size_other'] : $this->configuration['fetch_size'];
          /** @var FileInterface $file */
          // @todo Rewrite using something else instead of system_retrieve_file.
          $file_uri = system_retrieve_file($selected_item->{$fetch_size}, $destination);
          $file_name = $this->fileSystem->basename($file_uri);

          $file = File::create([
            'filename' => $file_name,
            'uri' => $file_uri,
            'uid' => $this->currentUser->id(),
            // This sets the file as permanent.
            'status' => TRUE,
          ]);

          // Provide a way for other fields to be mapped.
          \Drupal::moduleHandler()->alter('media_elvis_field_mapping', $file, $selected_item);

          // Create media entities if configured so.
          if ($this->configuration['output'] == 'media_entity') {
            $file->save();
            $bundle = $this->getBundle();
            $source_field_name = $bundle->getTypeConfiguration()['source_field'];

            $entity = $this->entityTypeManager->getStorage('media')->create([
              'bundle' => $bundle->id(),
              $source_field_name => [
                'target_id' => $file->id(),
                'alt' => !empty($selected_item->metadata->description) ? $selected_item->metadata->description : NULL,
                'title' => !empty($selected_item->metadata->title) ? $selected_item->metadata->title : NULL,
              ],
              'uid' => $this->currentUser->id(),
              'status' => TRUE,
              'original_data' => $selected_item,
            ]);


          }
          else {
            $entity = $file;
          }

          $entities[] = $entity;
        }
        catch (\Exception $e) {
          $this->logger->get('media_elvis')->error('Unable to generate entity due to: %e', ['%e' => $e->getMessage()]);
          drupal_set_message($this->t('Unable to generate media due to %e', ['%e' => $e->getMessage()]));
        }

      }
    }

    // Pass the prepared entities to submit.
    $form_state->set('elvis_prepared', $entities);
    return $entities;
  }

  /**
   * Gets upload location.
   *
   * @return string
   *   Destination folder URI.
   */
  protected function getUploadLocation() {
    return $this->token->replace($this->configuration['upload_location']);
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $prepared_entities = $form_state->get('elvis_prepared', []);

    foreach ($prepared_entities as $key => $prepared_entity) {
      $prepared_entities[$key]->save();
    }

    $this->selectEntities($prepared_entities, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config = $this->configuration;

    $form['server_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Server url'),
      '#description' => $this->t('Including trailing slash. Ie: http://www.example.org/'),
      '#default_value' => $config['server_uri'],
      '#required' => TRUE,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config['username'],
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => isset($config['password']) ? $config['password'] : '',
      '#required' => TRUE,
    ];

    $form['per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of results per page'),
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $config['per_page'],
    ];

    $form['upload_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload location'),
      '#default_value' => $config['upload_location'],
    ];

    $form['fetch_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image variant to fetch'),
      '#options' => [
        'previewUrl' => $this->t('Preview image'),
        'originalUrl' => $this->t('Original image'),
        '' => $this->t('- Other - '),
      ],
      '#description' => $this->t('Original images can be quite big.'),
      '#default_value' => $config['fetch_size'],
    ];

    $form['fetch_size_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image size name'),
      '#default_value' => $config['fetch_size_other'],
      '#description' => $this->t('Use the machine name. Ie: previewUrl, originalUrl,...'),
      '#states' => [
        'visible' => [
          ':input[name="table[' . $this->uuid() . '][form][fetch_size]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output entity type'),
      '#options' => ['file' => $this->t('File')],
      '#description' => $this->t('If used in field context this has to match the entity reference configuration.'),
      '#default_value' => $config['output'],
      '#required' => TRUE,
    ];

    $module_handler = \Drupal::moduleHandler();
    if ($module_handler->moduleExists('media_entity')) {
      $form['output']['#options']['media_entity'] = $this->t('Media entity');

      $form['media_entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Media type'),
        '#required' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="table[' . $this->uuid() . '][form][output]"]' => ['value' => 'media_entity'],
          ],
        ],
        '#description' => $this->t('The type of media entity to create from the uploaded file(s).'),
      ];

      $bundle = $this->getBundle();
      if ($bundle) {
        $form['media_entity_bundle']['#default_value'] = $bundle->id();
      }

      $bundles = $this->entityTypeManager->getStorage('media_bundle')->loadByProperties(['type' => 'media_elvis_image']);
      if (!empty($bundles)) {
        foreach ($bundles as $bundle) {
          $form['media_entity_bundle']['#options'][$bundle->id()] = $bundle->label();
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['table'][$this->uuid()]['form'];
    $this->configuration['submit_text'] = $values['submit_text'];
    $this->configuration['server_uri'] = $values['server_uri'];
    $this->configuration['username'] = $values['username'];
    $this->configuration['password'] = $values['password'];
    $this->configuration['per_page'] = $values['per_page'];
    $this->configuration['upload_location'] = $values['upload_location'];
    $this->configuration['fetch_size'] = $values['fetch_size'];
    $this->configuration['fetch_size_other'] = $values['fetch_size_other'];
    $this->configuration['output'] = $values['output'];

    if (isset($values['media_entity_bundle'])) {
      $this->configuration['media_entity_bundle'] = $values['media_entity_bundle'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    // Depend on the media bundle if this widget creates.
    if ($this->configuration['output'] == 'media_entity') {
      $bundle = $this->getBundle();
      $dependencies[$bundle->getConfigDependencyKey()][] = $bundle->getConfigDependencyName();
      $dependencies['module'][] = 'media_entity';
    }

    return $dependencies;
  }

  /**
   * Returns the media bundle that this widget creates.
   *
   * @return \Drupal\media_entity\MediaBundleInterface
   *   Media bundle.
   */
  protected function getBundle() {
    return $this->entityTypeManager
      ->getStorage('media_bundle')
      ->load($this->configuration['media_entity_bundle']);
  }
}
