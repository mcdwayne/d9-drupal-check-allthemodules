<?php

namespace Drupal\filefield_sources_jsonapi\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filefield_sources_jsonapi\Entity\FileFieldSourcesJSONAPI;
use GuzzleHttp\Client;
use Drupal\Core\Url;

/**
 * Implements the ModalBrowserForm form controller.
 */
class ModalBrowserForm extends FormBase {

  /**
   * Entity form display storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityFormDisplayStorage;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * BulkMediaUploadForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->entityFormDisplayStorage = $entityTypeManager->getStorage('entity_form_display');
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'filefield_sources_jsonapi_browser_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL, $form_mode = NULL, $field_name = NULL, $wrapper = '') {
    static $settings;

    $form['#attached']['library'][] = 'filefield_sources_jsonapi/modal';
    $form['#prefix'] = '<div id="filefield-sources-jsonapi-browser-form">';
    $form['#suffix'] = '</div>';

    if ($form_state->get('fetched_file') && $form_state->get('form_type') === 'insert') {
      return self::buildInsertForm($form, $form_state);
    }

    $user_input = $form_state->getUserInput();
    $field_widget_settings = $this->entityFormDisplayStorage->load($entity_type . '.' . $bundle . '.' . $form_mode)
      ->getComponent($field_name);

    if (!$settings) {
      $settings = $field_widget_settings['third_party_settings']['filefield_sources']['filefield_sources']['source_remote_jsonapi'];
    }
    $settings['sources'] = FileFieldSourcesJSONAPI::getSettingsOptionList($settings['sources']);
    $settings['field_name'] = $field_name;
    $settings['wrapper'] = $wrapper;

    $settings['type'] = $field_widget_settings['type'];
    // @todo, refactor.
    if ('image_widget_crop' === $settings['type']) {
      $settings['type'] = 'image_image';
    }

    $settings['cardinality'] = FieldStorageConfig::loadByName($entity_type, $field_name)
      ->getCardinality();
    $field_settings = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle)[$field_name]->getSettings();
    $settings['field_settings'] = $field_settings;

    $actual_config = isset($settings['actual_config']) ? $settings['actual_config'] : NULL;
    if (isset($user_input['type'])) {
      $actual_config = FileFieldSourcesJSONAPI::load($user_input['type']);
    }
    if (!$actual_config) {
      $actual_config_id = array_keys($settings['sources'])[0];
      $actual_config = FileFieldSourcesJSONAPI::load($actual_config_id);
    }

    if ($sort_option_list = $actual_config->getSortOptionList()) {
      foreach (explode("\n", $sort_option_list) as $sort_option) {
        list($key, $label) = explode('|', $sort_option);
        $settings['sort_options'][$key] = $label;
      }
    }

    $settings['actual_config'] = $actual_config;
    $form_state->set('jsonapi_settings', $settings);

    $rest_api_url = $actual_config->getApiUrl();
    $query = $this->bulidJsonApiQuery($actual_config);

    $page = $form_state->get('page');
    if ($page === NULL) {
      $form_state->set('page', 0);
      $page = 0;
    }
    $query['page[limit]'] = $actual_config->getItemsPerPage();
    $query['page[offset]'] = $page * $query['page[limit]'];

    // Add browser form data to JSON API query.
    if (!empty($actual_config->getSearchFilter()) && isset($user_input['name']) && !empty($user_input['name'])) {
      if ($field_paths = explode(',', $actual_config->getSearchFilter())) {
        $query['filter[or-group][group][conjunction]'] = 'OR';
        foreach ($field_paths as $delta => $field_path) {
          $filterName = 'filter-' . $delta;
          $query['filter[' . $filterName . '][condition][path]'] = $field_path;
          $query['filter[' . $filterName . '][condition][operator]'] = 'CONTAINS';
          $query['filter[' . $filterName . '][condition][value]'] = $user_input['name'];
          $query['filter[' . $filterName . '][condition][memberOf]'] = 'or-group';
        }
      }
      else {
        $query['filter[nameFilter][condition][path]'] = $actual_config->getSearchFilter();
        $query['filter[nameFilter][condition][operator]'] = 'CONTAINS';
        $query['filter[nameFilter][condition][value]'] = $user_input['name'];
      }
    }
    if (isset($user_input['sort']) && !empty($user_input['sort'])) {
      $query['sort'] = $user_input['sort'];
    }
    else {
      if (isset($settings['sort_options'])) {
        $sort = array_keys($settings['sort_options']);
        $query['sort'] = reset($sort);
      }
    }

    $query_str = UrlHelper::buildQuery($query);
    $rest_api_url = $rest_api_url . '?' . $query_str;

    $response = $this->getJsonApiCall($rest_api_url);
    if (200 === $response->getStatusCode()) {
      $response = json_decode($response->getBody());
      $form['filefield_filesources_jsonapi_form'] = $this->renderFormElements($response, $form_state);
    }

    return $form;
  }

  /**
   * Builds the insert form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildInsertForm(array &$form, FormStateInterface $form_state) {
    $file = $form_state->get('fetched_file');
    $settings = $form_state->get('jsonapi_settings');
    $actual_config = $settings['actual_config'];
    $basic_auth = $actual_config->getBasicAuthentication();

    $form['title'] = [
      '#type' => 'item',
      '#title' => $this->t('Insert selected'),
    ];

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['insert-wrapper']],
    ];
    if ($basic_auth) {
      $uri = Url::fromRoute('filefield_sources_jsonapi.get_remote_file', ['url' => $file['thumbnail_url']])->toString();
    }
    else {
      $uri = $file['thumbnail_url'];
    }
    $form['wrapper']['image'] = [
      '#theme' => 'image',
      '#uri' => $uri,
      '#width' => '400',
    ];
    $form['wrapper']['detail'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['details-wrapper']],
    ];
    if (isset($settings['field_settings']['title_field']) && $settings['field_settings']['title_field']) {
      $form['wrapper']['detail']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $file['title'],
        '#required' => $settings['field_settings']['title_field_required'] ? TRUE : FALSE,
      ];
    }
    if (isset($settings['field_settings']['alt_field']) && $settings['field_settings']['alt_field']) {
      $form['wrapper']['detail']['alt'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Alt'),
        '#default_value' => $file['alt'],
        '#required' => $settings['field_settings']['alt_field_required'] ? TRUE : FALSE,
      ];
    }
    if (isset($settings['field_settings']['description_field']) && $settings['field_settings']['description_field']) {
      $form['wrapper']['detail']['description'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description'),
        '#default_value' => $file['title'],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancelSelectedSubmit'],
      '#ajax' => [
        'callback' => '::ajaxInsertCallback',
        'wrapper' => 'filefield-sources-jsonapi-browser-form',
      ],
      '#attributes' => ['class' => ['cancel-button']],
      '#weight' => 1,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'insert',
      '#value' => $this->t('Insert'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'event' => 'click',
      ],
      '#attributes' => ['class' => ['insert-button']],
      '#weight' => 2,
    ];

    return $form;
  }

  /**
   * Provides custom submission handler for change form to insert.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function insertSelectedSubmit(array &$form, FormStateInterface $form_state) {
    $form_state
      ->set('form_type', 'insert')
      ->setRebuild(TRUE);
  }

  /**
   * Provides custom submission handler for change form to basic.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelSelectedSubmit(array &$form, FormStateInterface $form_state) {
    $form_state
      ->set('form_type', 'form')
      ->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] === 'insert_selected') {
      $selected_media = array_values(array_filter($form_state->getUserInput()['media_id_select']));

      $file = NULL;
      if ($media_id = $selected_media[0]) {
        $settings = $form_state->get('jsonapi_settings');

        $rest_api_url = $settings['actual_config']->getApiUrl() . '/' . $media_id;
        $query = $this->bulidJsonApiQuery($settings['actual_config']);
        $query_str = UrlHelper::buildQuery($query);
        $rest_api_url = $rest_api_url . '?' . $query_str;

        $response = $this->getJsonApiCall($rest_api_url);
        if (200 === $response->getStatusCode()) {
          $response = json_decode($response->getBody());
          $api_url_base = $this->getApiBaseUrl($settings['actual_config']->getApiUrl());
          $file['url'] = $this->getJsonApiDatabyPath($response, $settings['actual_config']->getUrlAttributePath());
          if (!UrlHelper::isExternal($file['url'])) {
            $file['url'] = $api_url_base . $file['url'];
          }
          if ('image_image' === $settings['type']) {
            $file['thumbnail_url'] = $file['url'];
          }
          else {
            $file['thumbnail_url'] = $this->getJsonApiDatabyPath($response, $settings['actual_config']->getThumbnailUrlAttributePath());
            if (!UrlHelper::isExternal($file['thumbnail_url'])) {
              $file['thumbnail_url'] = $api_url_base . $file['thumbnail_url'];
            }
          }
          if ($alt_attribute_path = $settings['actual_config']->getAltAttributePath()) {
            $file['alt'] = $this->getJsonApiDatabyPath($response, $alt_attribute_path);
          }
          if ($title_attribute_path = $settings['actual_config']->getTitleAttributePath()) {
            $file['title'] = $this->getJsonApiDatabyPath($response, $title_attribute_path);
          }
        }

        if (!isset($file['url']) || !curl_init($file['url'])) {
          $form_state->setErrorByName('', $this->t("Can't fetch file from remote server."));
          $this->getLogger('filefield_sources_jsonapi')->warning("Can't fetch file (@url) from remote server.", ['@url' => $file['url']]);
        }
        $form_state->set('fetched_file', $file);
      }
      else {
        $form_state->setErrorByName('', $this->t("No file was selected."));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements the filter submit handler for the ajax call.
   */
  public function ajaxSubmitFilterForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('page', 0);
    $form_state->setRebuild();
  }

  /**
   * Implements the pager submit handler for the ajax call.
   */
  public function ajaxSubmitPagerNext(array &$form, FormStateInterface $form_state) {
    $page = $form_state->get('page');
    $form_state->set('page', ($page + 1));
    $form_state->setRebuild();
  }

  /**
   * Implements the pager submit handler for the ajax call.
   */
  public function ajaxSubmitPagerPrev(array &$form, FormStateInterface $form_state) {
    $page = $form_state->get('page');
    $form_state->set('page', ($page - 1));
    $form_state->setRebuild();
  }

  /**
   * Implements the pager submit handler for the ajax call.
   */
  public function ajaxPagerCallback(array &$form, FormStateInterface $form_state) {
    return $form['filefield_filesources_jsonapi_form']['lister'];
  }

  /**
   * Implements the insert submit handler for the ajax call.
   */
  public function ajaxInsertCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Implements the submit handler for the ajax call.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Array of ajax commands to execute on submit of the modal form.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->get('jsonapi_settings');
    $actual_config = $settings['actual_config'];

    // We begin building a new ajax response.
    $response = new AjaxResponse();
    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#filefield-sources-jsonapi-browser-form', $form));
    }
    else {
      $user_input = $form_state->getUserInput();
      $settings = $form_state->get('jsonapi_settings');
      $wrapper = $settings['wrapper'] ? $settings['wrapper'] . '-' : '';
      $selector = '.field--name-' . Html::getClass($settings['field_name']) . "[data-drupal-selector='edit-" . $wrapper . Html::getClass($settings['field_name']) . "-wrapper'] .filefield-source-remote_jsonapi";
      $file = $form_state->get('fetched_file');
      $response->addCommand(new InvokeCommand($selector . " input[name$='[filefield_remote_jsonapi][url]']", 'val', [$file['url']]));
      if (isset($user_input['alt'])) {
        $response->addCommand(new InvokeCommand($selector . " input[name$='[filefield_remote_jsonapi][alt]']", 'val', [$user_input['alt']]));
      }
      if (isset($user_input['title'])) {
        $response->addCommand(new InvokeCommand($selector . " input[name$='[filefield_remote_jsonapi][title]']", 'val', [$user_input['title']]));
      }
      if (isset($user_input['description'])) {
        $response->addCommand(new InvokeCommand($selector . " input[name$='[filefield_remote_jsonapi][description]']", 'val', [$user_input['description']]));
      }
      $response->addCommand(new InvokeCommand($selector . " input[name$='[filefield_remote_jsonapi][source]']", 'val', [$actual_config->id()]));
      $response->addCommand(new InvokeCommand($selector . " input[type=submit]", 'mousedown'));
      $response->addCommand(new CloseModalDialogCommand());
    }
    return $response;
  }

  /**
   * Render form elements.
   */
  private function renderFormElements($response, FormStateInterface $form_state) {
    $settings = $form_state->get('jsonapi_settings');
    $actual_config = $settings['actual_config'];
    $api_url_base = $this->getApiBaseUrl($actual_config->getApiUrl());
    $basic_auth = $actual_config->getBasicAuthentication();

    $render = [];
    $render['top'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'filefield_filesources_jsonapi_top',
        'class' => ['browser-top'],
      ],
    ];
    if (count($settings['sources']) > 1 || (!empty($settings['sort_options']) && count($settings['sort_options']) > 1) || !empty($settings['search_filter'])) {
      $render['top']['filter'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'filefield_filesources_jsonapi_filter',
          'class' => ['browser-filter', 'inline'],
        ],
      ];
      if (count($settings['sources']) > 1) {
        $render['top']['filter']['type'] = [
          '#title' => $this->t('Type'),
          '#type' => 'select',
          '#options' => $settings['sources'],
          '#attributes' => ['class' => ['type', 'inline']],
          '#submit' => ['::ajaxSubmitFilterForm'],
          '#ajax' => [
            'callback' => '::ajaxPagerCallback',
            'wrapper' => 'filefield_filesources_jsonapi_lister',
          ],
        ];
      }
      if (!empty($settings['sort_options'])) {
        $render['top']['filter']['sort'] = [
          '#title' => $this->t('Sort'),
          '#type' => 'select',
          '#options' => $settings['sort_options'],
          '#attributes' => ['class' => ['sort-by', 'inline']],
          '#submit' => ['::ajaxSubmitFilterForm'],
          '#ajax' => [
            'callback' => '::ajaxPagerCallback',
            'wrapper' => 'filefield_filesources_jsonapi_lister',
          ],
        ];
        if (count($settings['sort_options']) < 2) {
          $render['top']['filter']['sort']['#printed'] = TRUE;
        }
      }
      if (!empty($settings['search_filter'])) {
        $render['top']['filter']['name'] = [
          '#type' => 'textfield',
          '#attributes' => [
            'class' => ['file-name'],
            'placeholder' => $this->t('Search'),
          ],
        ];
        $render['top']['filter']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Apply'),
          '#limit_validation_errors' => [],
          '#submit' => ['::ajaxSubmitFilterForm'],
          '#ajax' => [
            'callback' => '::ajaxPagerCallback',
            'wrapper' => 'filefield_filesources_jsonapi_lister',
          ],
          '#attributes' => ['class' => ['visually-hidden']],
        ];
      }
    }

    // If cardinality is 1, don't render submit button - autosubmit on select.
    $render['top']['action'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'filefield_filesources_jsonapi_action',
        'class' => ['browser-action'],
      ],
    ];
    $render['top']['action']['submit'] = [
      '#type' => 'submit',
      '#name' => 'insert_selected',
      '#value' => $this->t('Insert selected'),
      '#submit' => ['::insertSelectedSubmit'],
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'wrapper' => 'filefield-sources-jsonapi-browser-form',
      ],
      '#attributes' => ['class' => ['insert-button', 'visually-hidden']],
    ];
    // Navigate to 2nd step if there are alt/title/description field enabled.
    if (('image_image' === $settings['type'] && ($settings['field_settings']['alt_field'] || $settings['field_settings']['title_field']))
      || (isset($settings['field_settings']['description_field']) && $settings['field_settings']['description_field'])) {
      $render['top']['action']['submit']['#ajax']['callback'] = '::ajaxInsertCallback';
    }

    $render['lister'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['browser-lister']],
      '#prefix' => '<div id="filefield_filesources_jsonapi_lister">',
      '#suffix' => '</div>',
    ];

    $render['lister']['media'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['media-lister']],
    ];
    foreach ($response->data as $data) {
      $media_id = $data->id;
      $thumbnail_url_attribute_path = $actual_config->getThumbnailUrlAttributePath() ?: $actual_config->getUrlAttributePath();
      $thumbnail_url = $this->getJsonApiDatabyPath($response, $thumbnail_url_attribute_path, $data);
      if ($media_id && $thumbnail_url) {
        $render['lister']['media'][$media_id] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['media-row']],
        ];
        $checkbox = [
          '#type' => 'checkbox',
          '#title' => $this->t('Select this item'),
          '#title_display' => 'invisible',
          '#return_value' => $media_id,
          '#id' => $media_id,
          '#attributes' => ['name' => "media_id_select[$media_id]"],
          '#default_value' => NULL,
        ];
        // Auto trigger on cardinality 1 - doesn't work.
        if ($settings['cardinality'] === 1) {
          $checkbox['#ajax'] = [
            'trigger_as' => ['name' => 'insert_selected'],
            'callback' => '::ajaxInsertCallback',
            'wrapper' => 'filefield-sources-jsonapi-browser-form',
            'event' => 'click',
          ];
        }
        if ($basic_auth) {
          $url = UrlHelper::isExternal($thumbnail_url) ? $thumbnail_url : $api_url_base . $thumbnail_url;
          $uri = Url::fromRoute('filefield_sources_jsonapi.get_remote_file', ['url' => $url])->toString();
        }
        else {
          $uri = UrlHelper::isExternal($thumbnail_url) ? $thumbnail_url : $api_url_base . $thumbnail_url;
        }
        $img = [
          '#theme' => 'image',
          '#uri' => $uri,
          '#width' => '120',
        ];
        $render['lister']['media'][$media_id]['media_id'] = [
          '#theme' => 'browser_media_box',
          '#checkbox' => $checkbox,
          '#checkbox_id' => $media_id,
          '#img' => $img,
          '#title' => $this->getJsonApiDatabyPath($response, $actual_config->getTitleAttributePath(), $data),
        ];
      }
    }
    if (empty($response->data)) {
      $render['lister']['media']['empty'] = [
        '#markup' => $this->t('No results.'),
        '#attributes' => ['class' => ['no-result']],
      ];
    }

    // Add navigation buttons.
    if (isset($response->links->prev)) {
      $render['lister']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('« Prev'),
        '#limit_validation_errors' => [],
        '#submit' => ['::ajaxSubmitPagerPrev'],
        '#ajax' => [
          'callback' => '::ajaxPagerCallback',
          'wrapper' => 'filefield_filesources_jsonapi_lister',
        ],
      ];
    }
    if (isset($response->links->next)) {
      $render['lister']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next »'),
        '#limit_validation_errors' => [],
        '#submit' => ['::ajaxSubmitPagerNext'],
        '#ajax' => [
          'callback' => '::ajaxPagerCallback',
          'wrapper' => 'filefield_filesources_jsonapi_lister',
        ],
      ];
    }

    return $render;
  }

  /**
   * Helper function to get base url of api uri.
   */
  private function getApiBaseUrl($url) {
    $api_url_parsed = parse_url($url);

    $api_url_base = $api_url_parsed['scheme'] . '://' . $api_url_parsed['host'] . (isset($api_url_parsed['port']) ? ':' . $api_url_parsed['port'] : '');

    return $api_url_base;
  }

  /**
   * Build JSON API query based on settings.
   */
  private function bulidJsonApiQuery($config) {
    $query['format'] = 'api_json';

    foreach (explode("\n", $config->getParams()) as $param) {
      list($key, $value) = explode('|', $param);
      if (preg_match('/(.*)\[\]$/', $key, $matches)) {
        $key = $matches[1];
        $query[$key][] = trim($value);
      }
      else {
        $query[$key] = trim($value);
      }
    }
    return $query;
  }

  /**
   * Do JSON API call with $rest_api_url.
   *
   * @param string $rest_api_url
   *   Url with query params.
   *
   * @return mixed
   *   JSON API response.
   */
  private function getJsonApiCall($rest_api_url) {
    $client = new Client();
    $myConfig = $this->configFactory()->get('filefield_sources_jsonapi');
    $username = $myConfig->get('username');
    $password = $myConfig->get('password');

    $response = $client->get($rest_api_url, [
      'headers' => ['Authorization' => 'Basic ' . base64_encode("$username:$password")],
    ]);

    return $response;
  }

  /**
   * Get data from JSON API response by path.
   *
   * @param object $response
   *   Full JSON API response with data, included.
   * @param string $pathString
   *   Attribute's path string, e.g.:
   *   data->attributes->title
   *   data->attributes->field_image->attributes->data->url.
   * @param object $data
   *   Actual response data - optional.
   *
   * @return mixed
   *   Data from JSON API response.
   */
  public function getJsonApiDatabyPath($response, $pathString, $data = NULL) {
    if (!empty($data)) {
      $attribute_data = $data;
      $pathString = preg_replace('/^data->/', '', $pathString);
    }
    else {
      $attribute_data = $response;
    }
    $value = NULL;
    if (strstr($pathString, '->included->')) {
      list($data_path, $included_path) = explode('->included->', $pathString);
    }
    else {
      $data_path = $pathString;
      $included_path = NULL;
    }
    foreach (explode('->', $data_path) as $property) {
      if ($property) {
        $attribute_data = $attribute_data->{$property};
      }
      else {
        $attribute_data = NULL;
      }
    }
    if (!empty($included_path)) {
      foreach ($response->included as $included) {
        $included_data = $included;
        if ($attribute_data->data->type === $included->type && $attribute_data->data->id === $included->id) {
          foreach (explode('->', $included_path) as $property) {
            $included_data = $included_data->{$property};
          }
          $value = $included_data;
          break;
        }
      }
    }
    else {
      $value = $attribute_data;
    }

    return $value;
  }

}
