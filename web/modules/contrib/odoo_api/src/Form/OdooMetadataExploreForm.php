<?php

namespace Drupal\odoo_api\Form;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use fXmlRpc\Exception\ExceptionInterface as XmlRpcException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\odoo_api\FormStateCacheTrait;
use Drupal\odoo_api\OdooApi\Client;

/**
 * Class OdooMetadataExploreForm.
 */
class OdooMetadataExploreForm extends FormBase {

  use FormStateCacheTrait;

  /**
   * Odoo API client service.
   *
   * @var \Drupal\odoo_api\OdooApi\Client
   */
  protected $odooApiApiClient;

  /**
   * Image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $odoo_api_api_client, ImageFactory $image_factory) {
    $this->odooApiApiClient = $odoo_api_api_client;
    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('odoo_api.api_client'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'odoo_metadata_explore_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Initialize form state cache.
    $this->formState = $form_state;

    $form['model'] = [
      '#type' => 'select',
      '#options' => $this->getModelsOptions(),
      '#ajax' => [
        'callback' => get_class($this) . '::ajaxUpdate',
        'wrapper' => 'metadata-container',
      ],
      '#required' => TRUE,
    ];

    $form['metadata_container'] = [
      '#type' => 'container',
      '#id' => 'metadata-container',
    ];

    if ($model_name = $form_state->getValue('model')) {
      $models = $this->getCachedModelsList();
      if (isset($models[$model_name])) {
        $form['metadata_container']['metadata'] = $this->getModelMetadata($models[$model_name]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * AJAX metadata list update callback.
   */
  public static function ajaxUpdate(array &$form, FormStateInterface $form_state) {
    return $form['metadata_container'];
  }

  /**
   * Form submit callback for rebuilding the form.
   */
  public static function rebuildForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Gets metadata for models.
   *
   * @return array
   *   List of models.
   */
  protected function getCachedModelsList() {
    return $this->cacheResponse('models_list', function () {
      $models = [];
      foreach ($this->odooApiApiClient->searchRead('ir.model') as $model) {
        $models[$model['model']] = $model;
      }
      return $models;
    });

  }

  /**
   * Gets list of models for select element.
   *
   * @return array
   *   List of models.
   */
  protected function getModelsOptions() {
    $options = [];
    foreach ($this->getCachedModelsList() as $name => $model) {
      $options[$name] = '[' . $name . '] ' . $model['name'];
    }
    ksort($options);
    return $options;
  }

  /**
   * Gets model metadata render array.
   *
   * @param array $model
   *   Model definition.
   *
   * @return array
   *   Render array.
   */
  protected function getModelMetadata(array $model) {
    $element = [
      'info' => $this->getModelInfo($model),
      'search' => $this->searchForm($model),
      'fields' => $this->getModelFieldsTable($model),
    ];

    return $element;
  }

  /**
   * Gets Odoo model info table.
   *
   * @param array $model
   *   Model definition.
   *
   * @return array
   *   Render array.
   */
  protected function getModelInfo(array $model) {
    $table = [
      '#type' => 'fieldset',
      '#title' => $this->t('Model info'),
      'contents' => [
        '#type' => 'table',
        [
          'label' => ['#plain_text' => $this->t('Model machine name')],
          'value' => ['#plain_text' => $model['model']],
        ],
        [
          'label' => ['#plain_text' => $this->t('Model name')],
          'value' => ['#plain_text' => $model['name']],
        ],
        [
          'label' => ['#plain_text' => $this->t('Display name')],
          'value' => ['#plain_text' => $model['display_name']],
        ],
        [
          'label' => ['#plain_text' => $this->t('Info')],
          'value' => ['#markup' => nl2br(new HtmlEscapedText($model['info']))],
        ],
        [
          'label' => ['#plain_text' => $this->t('Model state')],
          'value' => ['#plain_text' => $model['state']],
        ],
        [
          'label' => ['#plain_text' => $this->t('Modules')],
          'value' => ['#plain_text' => $model['modules']],
        ],
      ],
    ];

    return $table;
  }

  /**
   * Gets Odoo model fields table.
   *
   * @param array $model
   *   Model definition.
   *
   * @return array
   *   Render array.
   */
  protected function getModelFieldsTable(array $model) {
    $table = [
      '#type' => 'fieldset',
      '#title' => $this->t('Model fields'),
      'contents' => [
        '#type' => 'table',
        '#header' => [
          'field' => $this->t('Name'),
          'name' => $this->t('Machine name'),
          'type' => $this->t('Type'),
          'help' => $this->t('Help'),
        ],
      ],
    ];

    if ($search_result = $this->getSearchResult()) {
      $table['count'] = [
        '#plain_text' => $this->formatPlural($search_result['count'], '1 search result.', '@count search results.'),
        '#weight' => -1,
      ];
      if ($search_result['count'] > 0) {
        $table['contents']['#header']['value'] = $this->t('Value');
      }
      if (!empty($search_result['error'])) {
        $table['error'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Odoo API error response'),
          '#weight' => -1,
          'message' => [
            '#markup' => '<pre>' . new HtmlEscapedText($search_result['error']) . '</pre>',
          ],
        ];
      }
    }

    foreach ($this->getModelFieldsData($model) as $field_name => $field) {
      $row = [
        'field' => ['#markup' => '<pre>' . new HtmlEscapedText($field_name) . '</pre>'],
        'name' => ['#plain_text' => $field['string'] ?: ''],
        'type' => ['#plain_text' => $field['type'] ?: ''],
        'help' => ['#plain_text' => $field['help'] ?: ''],
      ];
      if ($search_result['count'] > 0) {
        $row['value'] = $this->formatFieldValue($search_result, $field_name, $field);
      }
      $table['contents'][] = $row;
    }

    return $table;
  }

  /**
   * Gets list of model fields.
   *
   * @param array $model
   *   Model definition.
   *
   * @return array
   *   List of fields.
   */
  protected function getModelFieldsData(array $model) {
    $model_name = $model['model'];
    return $this->cacheResponse('models_' . $model_name, function () use ($model_name) {
      return $this->odooApiApiClient->fieldsGet($model_name);
    });
  }

  /**
   * Builds objects search form.
   *
   * @param array $model
   *   Model definition.
   *
   * @return array
   *   Search sub-form render array.
   */
  protected function searchForm(array $model) {
    $search = [
      '#type' => 'fieldset',
      '#title' => $this->t('Model search'),
      '#tree' => TRUE,
    ];

    $search['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $this->getFieldsOptions($model),
    ];

    $search['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
    ];

    $search['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#ajax' => [
        'callback' => get_class($this) . '::ajaxUpdate',
        'wrapper' => 'metadata-container',
      ],
      '#submit' => [get_class($this) . '::rebuildForm'],
    ];

    $search['search_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use search cache'),
      '#default_value' => TRUE,
    ];

    return $search;
  }

  /**
   * Gets options for model field select element.
   *
   * @param string $model
   *   Model name.
   *
   * @return array
   *   Array of fields options.
   */
  protected function getFieldsOptions($model) {
    $options = [];
    foreach ($this->getModelFieldsData($model) as $field_name => $field) {
      $options[$field_name] = '[' . $field_name . '] ' . $field['string'];
    }
    return $options;
  }

  /**
   * Gets Odoo search results.
   *
   * @return array|false
   *   An array with search results or FALSE if searched model is not set.
   *   The return array has the following fields:
   *     - count: number of records matching the filter,
   *     - item: searched item,
   *     - error: exception or NULL.
   */
  protected function getSearchResult() {
    $form_state = $this->formState;

    if (!($model_name = $form_state->getValue('model'))) {
      return FALSE;
    }

    $filter = [];
    $search_field = $form_state->getValue(['search', 'field']);
    $search_value = $form_state->getValue(['search', 'value']);
    if ($search_field && $search_value) {
      $filter[] = [$search_field, '=', $search_value];
    }

    $cache_key = 'model_search_' . $model_name . '_' . md5(serialize($filter));
    return $this->cacheResponse($cache_key, function () use ($model_name, $filter) {
      // @TODO: Implement pager.
      try {
        $count = $this->odooApiApiClient->count($model_name, $filter);
        return [
          'count' => $count,
          // D not run searchRead if we know there are no objects.
          'item' => $count > 0 ? $this->odooApiApiClient->searchRead($model_name, $filter, NULL, NULL, 1)[0] : [],
          'error' => NULL,
        ];
      }
      catch (XmlRpcException $e) {
        return [
          'count' => 0,
          'item' => [],
          'error' => $e->getMessage(),
        ];
      }
    }, $form_state->getValue(['search', 'search_cache'], TRUE));
  }

  /**
   * Formats Odoo field for output.
   *
   * @param array $search_result
   *   Search results.
   * @param string $field_name
   *   Field name.
   * @param array $field
   *   Field definition.
   *
   * @return array
   *   Field value render array.
   */
  protected function formatFieldValue(array $search_result, $field_name, array $field) {
    if ($field['type'] == 'binary'
      && !empty($search_result['item'][$field_name])) {
      $field_value = &$search_result['item'][$field_name];
      if ($binary_data = base64_decode($field_value)) {
        $uri = 'temporary://odoo_binary_file_' . md5($binary_data) . '.png';
        file_put_contents($uri, $binary_data);
        $image = $this->imageFactory->get($uri);

        if ($image->isValid()) {
          return [
            '#theme' => 'image',
            '#width' => $image->getWidth(),
            '#height' => $image->getHeight(),
            '#uri' => 'data:' . $image->getMimeType() . ';base64,' . $field_value,
          ];
        }
        else {
          return [
            '#markup' => '<pre>' . $this->formatPlural(strlen($field_value), 'Unknown base64 binary data, @length bytes', 'Unknown binary data, @length bytes.') . '</pre>',
          ];
        }
      }
      else {
        return [
          '#markup' => '<pre>' . $this->formatPlural(strlen($field_value), 'Unknown binary data, @length bytes', 'Unknown binary data, @length bytes.') . '</pre>',
        ];
      }
    }

    $value = isset($search_result['item'][$field_name]) ?
      var_export($search_result['item'][$field_name], TRUE) :
      $this->t('Not set');

    return ['#markup' => '<pre>' . new HtmlEscapedText($value) . '</pre>'];
  }

}
