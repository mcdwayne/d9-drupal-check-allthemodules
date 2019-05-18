<?php

namespace Drupal\loading_bar\Form;

use Drupal\Component\Utility\Color as ColorUtility;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the loading bar configuration form.
 */
class LoadingBarConfigurationForm extends FormBase {

  /**
   * The configuration array.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * LoadingBarConfigurationForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param array $configuration
   *   The configuration array.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, array $configuration = []) {
    $this->setConfiguration($configuration);
    $this->configFactory = $config_factory;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration = []) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity.repository'),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultConfiguration() {
    return [
      'preset' => NULL,
      'type' => 'stroke',
      'fill-dir' => 'ltr',
      'stroke-dir' => 'normal',
      'img' => NULL,
      'path' => NULL,
      'fill' => '#2255BB',
      'fill-background' => '#DDDDDD',
      'fill-background-extrude' => 3,
      'stroke' => '#2255BB',
      'stroke-width' => 3,
      'stroke-linecap' => 'butt',
      'stroke-trail' => '#DDDDDD',
      'stroke-trail-width' => 0.5,
      'pattern-size' => NULL,
      'img-size' => NULL,
      'bbox' => NULL,
      'min' => 0,
      'max' => 100,
      'label' => 'center',
      'width' => NULL,
      'height' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += $this->defaultConfiguration();

    foreach ($configuration as $key => $value) {
      if (array_key_exists($key, $this->defaultConfiguration())) {
        $this->configuration[$key] = $value;
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loading_bar_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, bool $ajax = TRUE) {
    $this->applyFormStateToConfiguration($form, $form_state);

    $config = $this->config('loading_bar.settings');
    $upload_location = $config->get('file_scheme') . '://' . $config->get('upload_directory') . '/';
    // We always need ajax in the preset setting.
    $ajax_settings = $ajax_preset_settings = [
      'callback' => [$this, 'updateConfigurationForm'],
      'wrapper' => 'loading-bar-configuration-from-wrapper',
      'effect' => 'fade',
    ];
    if ($ajax !== TRUE) {
      $ajax_settings = NULL;
    }

    $form['#attached']['library'][] = 'loading_bar/loading_bar.form.loading_bar';
    $form['#type'] = 'container';
    $form['#attributes']['id'] = ['loading-bar-configuration-from-wrapper'];
    $form['loading_bar_preview'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Preview'),
      '#attributes' => [
        'id' => ['loading-bar-preview-wrapper'],
        'class' => ['loading-bar-preview'],
      ],
    ];
    $form['loading_bar_preview']['loading_bar'] = [
      '#type' => 'loading_bar',
      '#configuration' => $this->getConfiguration(),
      '#value' => 50,
      '#attributes' => [
        'class' => ['loading-bar-demo'],
      ],
    ];
    $form['loading_bar_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];
    $preset_options = [];
    foreach (loading_bar_get_preset() as $name => $preset) {
      $preset_options[$name] = $preset['title'];
    }
    $form['loading_bar_settings']['preset'] = [
      '#type' => 'select',
      '#title' => $this->t('Preset'),
      '#options' => $preset_options,
      '#default_value' => $this->getConfiguration()['preset'],
      '#empty_option' => $this->t('- None -'),
      '#parents' => array_merge($form['#parents'], ['preset']),
      '#ajax' => $ajax_preset_settings,
    ];
    $form['loading_bar_settings']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Progress type'),
      '#description' => $this->t("Set the progress type. can be 'stroke' or 'fill'."),
      '#options' => [
        'stroke' => $this->t('Stroke'),
        'fill' => $this->t('Fill'),
      ],
      '#default_value' => $this->getConfiguration()['type'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => array_merge($form['#parents'], ['type']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['fill-dir'] = [
      '#type' => 'select',
      '#title' => $this->t('Fill type direction'),
      '#description' => $this->t('Growth direction of fill type progress bar.'),
      '#options' => [
        'ttb' => $this->t('Top to bottom'),
        'btt' => $this->t('Bottom to top'),
        'ltr' => $this->t('Left to right'),
        'rtl' => $this->t('Right to left'),
      ],
      '#default_value' => $this->getConfiguration()['fill-dir'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => array_merge($form['#parents'], ['fill-dir']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['stroke-dir'] = [
      '#type' => 'select',
      '#title' => $this->t('Stroke type direction'),
      '#description' => $this->t('Growth direction of stroke type progress bar.'),
      '#options' => [
        'normal' => $this->t('Normal'),
        'reverse' => $this->t('Reverse'),
      ],
      '#default_value' => $this->getConfiguration()['stroke-dir'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => array_merge($form['#parents'], ['stroke-dir']),
      '#ajax' => $ajax_settings,
    ];
    $img = $this->getConfiguration()['img'];
    $img_option = NULL;
    if (is_string($img) && strpos($img, 'data:image') === 0) {
      $img_option = 'img_data';
    }
    elseif (!empty($img)) {
      $img_option = 'img_image';
    }
    $form['loading_bar_settings']['img'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image of fill type'),
    ];
    $form['loading_bar_settings']['img']['img_options'] = [
      '#type' => 'radios',
      '#options' => [
        'img_data' => $this->t('Data URI'),
        'img_image' => $this->t('Image'),
      ],
      '#default_value' => $img_option,
      '#options_display' => 'side_by_side',
      '#parents' => array_merge($form['#parents'], ['img']),
      '#validated' => TRUE,
    ];
    $img_options_parents = array_merge($form['#parents'], ['img']);
    $img_options_name = array_shift($img_options_parents);
    foreach ($img_options_parents as $img_options_parent) {
      $img_options_name .= '[' . $img_options_parent . ']';
    }
    $form['loading_bar_settings']['img']['img_data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data URI of fill type progress bar'),
      '#description' => $this->t("Such as 'data:image/svg+xml,&lt;svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;70&quot; height=&quot;20&quot; viewBox=&quot;0 0 70 20&quot;&gt;&lt;text x=&quot;35&quot; y=&quot;10&quot; text-anchor=&quot;middle&quot; dominant-baseline=&quot;central&quot; font-family=&quot;arial&quot;&gt;LOADING&lt;/text&gt;&lt;/svg&gt;'."),
      '#default_value' => ($img_option == 'img_data') ? $img : NULL,
      '#parents' => array_merge($form['#parents'], ['img_data']),
      '#states' => [
        'visible' => [
          ':input[name="' . $img_options_name . '"]' => ['value' => 'img_data'],
        ],
      ],
      '#ajax' => $ajax_settings,
    ];
    // #states not affecting visibility/requirement of managed_file.
    // @see https://www.drupal.org/node/2847425.
    $form['loading_bar_settings']['img']['image'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . $img_options_name . '"]' => ['value' => 'img_image'],
        ],
      ],
    ];
    $form['loading_bar_settings']['img']['image']['img_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#description' => $this->t('Image of fill type progress bar.'),
      '#upload_location' => $upload_location,
      '#parents' => array_merge($form['#parents'], ['img_image']),
      '#states' => [
        'visible' => [
          ':input[name="' . $img_options_name . '"]' => ['value' => 'img_image'],
        ],
      ],
    ];
    if ($img_option == 'img_image' && is_string($img) && Uuid::isValid($img)) {
      $file = $this->entityRepository->loadEntityByUuid('file', $img);
      if ($file !== NULL) {
        $form['loading_bar_settings']['img']['image']['img_image']['#default_value'] = ['target_id' => $file->id()];
      }
    }
    elseif ($img_option == 'img_image' && is_array($img)) {
      $form['loading_bar_settings']['img']['image']['img_image']['#default_value'] = ['target_id' => reset($img)];
    }
    $form['loading_bar_settings']['path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SVG Path command'),
      '#description' => $this->t("Such as 'M10 10L90 10', used both in stroke and fill type progress bar."),
      '#default_value' => $this->getConfiguration()['path'],
      '#parents' => array_merge($form['#parents'], ['path']),
      '#ajax' => $ajax_settings,
    ];
    $fill = $this->getConfiguration()['fill'];
    $fill_option = NULL;
    if (ColorUtility::validateHex($fill)) {
      $fill_option = 'fill_color';
    }
    elseif (Uuid::isValid($fill) || is_array($fill)) {
      $fill_option = 'fill_image';
    }
    elseif (!empty($fill)) {
      $fill_option = 'fill_pattern';
    }
    $form['loading_bar_settings']['fill'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fill type'),
    ];
    $form['loading_bar_settings']['fill']['fill_options'] = [
      '#type' => 'radios',
      '#options' => [
        'fill_color' => $this->t('Color'),
        'fill_pattern' => $this->t('Pattern'),
        'fill_image' => $this->t('Image'),
      ],
      '#default_value' => $fill_option,
      '#options_display' => 'side_by_side',
      '#parents' => array_merge($form['#parents'], ['fill']),
      '#validated' => TRUE,
    ];
    $fill_options_parents = array_merge($form['#parents'], ['fill']);
    $fill_options_name = array_shift($fill_options_parents);
    foreach ($fill_options_parents as $fill_options_parent) {
      $fill_options_name .= '[' . $fill_options_parent . ']';
    }
    $form['loading_bar_settings']['fill']['fill_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Fill color'),
      '#description' => $this->t('Color when using a fill type progress bar with custom SVG path.'),
      '#default_value' => ($fill_option == 'fill_color' && !empty($fill)) ? $fill : '#000000',
      '#parents' => array_merge($form['#parents'], ['fill_color']),
      '#states' => [
        'visible' => [
          ':input[name="' . $fill_options_name . '"]' => ['value' => 'fill_color'],
        ],
      ],
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['fill']['fill_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fill pattern'),
      '#description' => $this->t('Pattern when using a fill type progress bar with custom SVG path. E.g., data:ldbar/res,gradient(0,1,#f99,#ff9).'),
      '#default_value' => ($fill_option == 'fill_pattern') ? $fill : NULL,
      '#parents' => array_merge($form['#parents'], ['fill_pattern']),
      '#states' => [
        'visible' => [
          ':input[name="' . $fill_options_name . '"]' => ['value' => 'fill_pattern'],
        ],
      ],
      '#ajax' => $ajax_settings,
    ];
    // #states not affecting visibility/requirement of managed_file.
    // @see https://www.drupal.org/node/2847425.
    $form['loading_bar_settings']['fill']['image'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="' . $fill_options_name . '"]' => ['value' => 'fill_image'],
        ],
      ],
    ];
    $form['loading_bar_settings']['fill']['image']['fill_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Fill image'),
      '#description' => $this->t('Image when using a fill type progress bar with custom SVG path.'),
      '#upload_location' => $upload_location,
      '#parents' => array_merge($form['#parents'], ['fill_image']),
      '#states' => [
        'visible' => [
          ':input[name="' . $fill_options_name . '"]' => ['value' => 'fill_image'],
        ],
      ],
    ];
    if ($fill_option == 'fill_image' && is_string($fill) && Uuid::isValid($fill)) {
      $file = $this->entityRepository->loadEntityByUuid('file', $fill);
      if ($file !== NULL) {
        $form['loading_bar_settings']['fill']['image']['fill_image']['#default_value'] = ['target_id' => $file->id()];
      }
    }
    elseif ($fill_option == 'fill_image' && is_array($fill)) {
      $form['loading_bar_settings']['fill']['image']['fill_image']['#default_value'] = ['target_id' => reset($fill)];
    }
    $form['loading_bar_settings']['fill-background'] = [
      '#type' => 'color',
      '#title' => $this->t('Fill color of the background shape'),
      '#description' => $this->t('Image when using a fill type progress bar with custom SVG path.'),
      '#default_value' => !empty($this->getConfiguration()['fill-background']) ? $this->getConfiguration()['fill-background'] : '#000000',
      '#parents' => array_merge($form['#parents'], ['fill-background']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['fill-background-extrude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Size of the background shape'),
      '#description' => $this->t('Size of the background shape in fill type progress bar.'),
      '#default_value' => $this->getConfiguration()['fill-background-extrude'],
      '#parents' => array_merge($form['#parents'], ['fill-background-extrude']),
      '#ajax' => $ajax_settings,
    ];
    $stroke = $this->getConfiguration()['stroke'];
    $stroke_option = NULL;
    if (ColorUtility::validateHex($stroke)) {
      $stroke_option = 'stroke_color';
    }
    elseif (!empty($stroke)) {
      $stroke_option = 'stroke_pattern';
    }
    $form['loading_bar_settings']['stroke'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Stroke type'),
    ];
    $form['loading_bar_settings']['stroke']['stroke_options'] = [
      '#type' => 'radios',
      '#options' => [
        'stroke_color' => $this->t('Color'),
        'stroke_pattern' => $this->t('Pattern'),
      ],
      '#default_value' => $stroke_option,
      '#options_display' => 'side_by_side',
      '#parents' => array_merge($form['#parents'], ['stroke']),
      '#validated' => TRUE,
    ];
    $stroke_options_parents = array_merge($form['#parents'], ['stroke']);
    $stroke_options_name = array_shift($stroke_options_parents);
    foreach ($stroke_options_parents as $stroke_options_parent) {
      $stroke_options_name .= '[' . $stroke_options_parent . ']';
    }
    $form['loading_bar_settings']['stroke']['stroke_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Stroke color'),
      '#default_value' => ($stroke_option == 'stroke_color' && !empty($stroke)) ? $stroke : '#000000',
      '#parents' => array_merge($form['#parents'], ['stroke_color']),
      '#states' => [
        'visible' => [
          ':input[name="' . $stroke_options_name . '"]' => ['value' => 'stroke_color'],
        ],
      ],
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['stroke']['stroke_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stroke pattern'),
      '#description' => $this->t('E.g., data:ldbar/res,gradient(0,1,#f99,#ff9).'),
      '#default_value' => ($stroke_option == 'stroke_pattern') ? $stroke : NULL,
      '#parents' => array_merge($form['#parents'], ['stroke_pattern']),
      '#states' => [
        'visible' => [
          ':input[name="' . $stroke_options_name . '"]' => ['value' => 'stroke_pattern'],
        ],
      ],
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['stroke-width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stroke width'),
      '#description' => $this->t('Stroke width of the progress bar.'),
      '#default_value' => $this->getConfiguration()['stroke-width'],
      '#parents' => array_merge($form['#parents'], ['stroke-width']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['stroke-linecap'] = [
      '#type' => 'select',
      '#title' => $this->t('Stroke linecap'),
      '#description' => $this->t('The starting and ending points of a border on SVG shapes.'),
      '#options' => [
        'butt' => $this->t('Butt'),
        'square' => $this->t('Square'),
        'round' => $this->t('Round'),
      ],
      '#default_value' => $this->getConfiguration()['stroke-linecap'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => array_merge($form['#parents'], ['stroke-linecap']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['stroke-trail'] = [
      '#type' => 'color',
      '#title' => $this->t('Trail color'),
      '#default_value' => !empty($this->getConfiguration()['stroke-trail']) ? $this->getConfiguration()['stroke-trail'] : '#000000',
      '#parents' => array_merge($form['#parents'], ['stroke-trail']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['stroke-trail-width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trail width'),
      '#default_value' => $this->getConfiguration()['stroke-trail-width'],
      '#parents' => array_merge($form['#parents'], ['stroke-trail-width']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['pattern-size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern size'),
      '#description' => $this->t("Specify pattern size; e.g., '100'."),
      '#default_value' => $this->getConfiguration()['pattern-size'],
      '#parents' => array_merge($form['#parents'], ['pattern-size']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['img-size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image size'),
      '#description' => $this->t("Specify image size; e.g., '200,100'."),
      '#default_value' => $this->getConfiguration()['img-size'],
      '#parents' => array_merge($form['#parents'], ['img-size']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['bbox'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bounding box'),
      '#description' => $this->t("Bounding box of an element; e.g. '10 10 80 10'."),
      '#default_value' => $this->getConfiguration()['bbox'],
      '#parents' => array_merge($form['#parents'], ['bbox']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum value'),
      '#step' => 0.01,
      '#default_value' => $this->getConfiguration()['min'],
      '#parents' => array_merge($form['#parents'], ['min']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['max'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum value'),
      '#step' => 0.01,
      '#default_value' => $this->getConfiguration()['max'],
      '#parents' => array_merge($form['#parents'], ['max']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['label'] = [
      '#type' => 'select',
      '#title' => $this->t('Label'),
      '#options' => [
        'center' => $this->t('Center'),
        'middle' => $this->t('Middle'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $this->getConfiguration()['label'],
      '#empty_option' => $this->t('- Select -'),
      '#parents' => array_merge($form['#parents'], ['label']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Loading bar width'),
      '#default_value' => $this->getConfiguration()['width'],
      '#parents' => array_merge($form['#parents'], ['width']),
      '#ajax' => $ajax_settings,
    ];
    $form['loading_bar_settings']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Loading bar height'),
      '#default_value' => $this->getConfiguration()['height'],
      '#parents' => array_merge($form['#parents'], ['height']),
      '#ajax' => $ajax_settings,
    ];

    return $form;
  }

  /**
   * Ajax callback function for when the `preset` element is changed.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The updated element.
   */
  public static function updateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $configuration_form = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -(count($triggering_element['#array_parents']) - array_search('loading_bar_settings', $triggering_element['#array_parents']))));

    return $configuration_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do not validate if ajax reload.
    $submit_element = $form_state->getTriggeringElement();
    if (!empty($submit_element) &&
      ((!empty($submit_element['#ajax']['wrapper']) && $submit_element['#ajax']['wrapper'] == 'loading-bar-configuration-from-wrapper') ||
        substr_compare($submit_element['#name'], '_upload_button', strlen($submit_element['#name']) - strlen('_upload_button'), strlen('_upload_button')) === 0 ||
        substr_compare($submit_element['#name'], '_remove_button', strlen($submit_element['#name']) - strlen('_remove_button'), strlen('_remove_button')) === 0)) {
      return;
    }

    $this->applyFormStateToConfiguration($form, $form_state);
    // @TODO: Validate configuration, e.g., img need fill progress type.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form, $form_state);

    $configuration = $this->getConfiguration();

    foreach (['fill', 'img'] as $image) {
      if (!empty($configuration[$image]) && is_array($configuration[$image])) {
        // If a file was uploaded, get its UUID to be stored.
        $file = $this->entityTypeManager->getStorage('file')->load(reset($configuration[$image]));
        $configuration[$image] = ($file !== NULL) ? $file->uuid() : NULL;
      }
    }
    $this->setConfiguration($configuration);

    $form_state->setValues($this->getConfiguration());
  }

  /**
   * Apply submitted form state to configuration.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function applyFormStateToConfiguration(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    // This method receives a sub form state instead of the full form state.
    // @See https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $values = NestedArray::getValue($form_state->getCompleteFormState()->getValues(), $form['#parents']);
    }
    else {
      $values = $form_state->getValues();
    }
    $values = empty($values) ? $this->getConfiguration() : $values;

    // When images are uploaded, only update this value.
    if (count($values) == 1) {
      // @TODO Add ajax refresh after upload a file.
      $values += $this->getConfiguration();
    }

    // Get preset configuration.
    if (!empty($values) && $values['preset'] != $configuration['preset']) {
      $values = loading_bar_get_preset($values['preset']);
    }

    // Convert file id arrays to uuids.
    foreach (['img' => 'img_image', 'fill' => 'fill_image'] as $setting => $value) {
      if (!empty($values[$value]) && is_array($values[$value])) {
        // If a file was uploaded, get its UUID to be stored.
        $file = $this->entityTypeManager->getStorage('file')->load(reset($values[$value]));
        $values[$value] = ($file !== NULL) ? $file->uuid() : NULL;
        $values[$setting] = $value;
      }
    }

    // Replace multi-field values with the selected.
    if (!empty($values['img']) && array_key_exists($values['img'], $values)) {
      $values['img'] = $values[$values['img']];
    }
    if (!empty($values['fill']) && array_key_exists($values['fill'], $values)) {
      $values['fill'] = $values[$values['fill']];
    }
    if (!empty($values['stroke']) && array_key_exists($values['stroke'], $values)) {
      $values['stroke'] = $values[$values['stroke']];
    }

    // Update the configuration.
    $new_configuration = [];
    foreach ($configuration as $key => $value) {
      if (array_key_exists($key, $values)) {
        $new_configuration[$key] = $values[$key];
      }
      else {
        $new_configuration[$key] = NULL;
      }
    }

    $this->setConfiguration($new_configuration);

    // During ajax callbacks default values are not refreshed, just update user
    // input values.
    if (!empty($new_configuration['img']) && is_string($new_configuration['img']) && strpos($new_configuration['img'], 'data:image') === 0) {
      $new_configuration['img_data'] = $new_configuration['img'];
      $new_configuration['img'] = 'img_data';
    }
    elseif (!empty($new_configuration['img'])) {
      $new_configuration['img_image'] = $new_configuration['img'];
      $new_configuration['img'] = 'img_image';

      $file = $this->entityRepository->loadEntityByUuid('file', $new_configuration['img_image']);
      if ($file !== NULL) {
        $new_configuration['img_image'] = ['fids' => $file->id()];
      }
    }

    if (!empty($new_configuration['fill']) && is_string($new_configuration['fill']) && ColorUtility::validateHex($new_configuration['fill'])) {
      $new_configuration['fill_color'] = $new_configuration['fill'];
      $new_configuration['fill'] = 'fill_color';
    }
    elseif (!empty($new_configuration['fill']) && is_string($new_configuration['fill']) && Uuid::isValid($new_configuration['fill'])) {
      $new_configuration['fill_image'] = $new_configuration['fill'];
      $new_configuration['fill'] = 'fill_image';

      $file = $this->entityRepository->loadEntityByUuid('file', $new_configuration['fill_image']);
      if ($file !== NULL) {
        $new_configuration['fill_image'] = ['fids' => $file->id()];
      }
    }
    elseif (!empty($new_configuration['fill'])) {
      $new_configuration['fill_pattern'] = $new_configuration['fill'];
      $new_configuration['fill'] = 'fill_pattern';
    }

    if (!empty($new_configuration['stroke']) && is_string($new_configuration['stroke']) && ColorUtility::validateHex($new_configuration['stroke'])) {
      $new_configuration['stroke_color'] = $new_configuration['stroke'];
      $new_configuration['stroke'] = 'stroke_color';
    }
    elseif (!empty($new_configuration['stroke'])) {
      $new_configuration['stroke_pattern'] = $new_configuration['stroke'];
      $new_configuration['stroke'] = 'stroke_pattern';
    }

    if ($form_state instanceof SubformStateInterface) {
      $values = $form_state->getCompleteFormState()->getUserInput();
      NestedArray::setValue($values, $form['#parents'], $new_configuration);
      $form_state->getCompleteFormState()->setValues($values);
      $form_state->getCompleteFormState()->setUserInput($values);
    }
    else {
      $values = $form_state->getUserInput();
      NestedArray::setValue($values, $form['#parents'], $new_configuration);
      $form_state->setValues($values);
      $form_state->setUserInput($values);
    }
  }

}
