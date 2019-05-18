<?php

namespace Drupal\micro_theme\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_theme\MicroAssetFileStorage;
use Drupal\micro_theme\MicroLibrariesServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Image\ImageFactory;

/**
 * Class MicroThemeFormSettings.
 */
class MicroThemeFormSettings extends ConfigFormBase {

  /**
   * The settings of theme form configuration.
   *
   * @var array
   *
   * @see \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * File usage interface to configurate an file object.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File|null
   */
  protected $file = NULL;

  /**
   * Drupal\Core\Image\ImageFactory definition.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Drupal\Core\Image\ImageFactory definition.
   *
   * @var \Drupal\usine_theme\LibrariesServiceInterface
   */
  protected $librariesService;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The default colors.
   *
   * @var array
   */
  protected $colorsDefault;

  /**
   * The micro the settigns key.
   *
   * @var string
   */
  protected $microThemeKey;


  /**
   * ThemeForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   The image factory.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   File usage service.
   * @param \Drupal\micro_theme\MicroLibrariesServiceInterface $libraries_service
   *   The libraries service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The micro site entity.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ImageFactory $image_factory, FileUsageInterface $file_usage, MicroLibrariesServiceInterface $libraries_service, StateInterface $state) {
    parent::__construct($config_factory);
    $this->fileUsage = $file_usage;
    $this->imageFactory = $image_factory;
    $this->librariesService = $libraries_service;
    $this->state = $state;
    $this->colorsDefault = $this->librariesService->getDefaultColors();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('image.factory'),
      $container->get('file.usage'),
      $container->get('micro_theme.libraries'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'micro_theme.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micro_theme_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $site = NULL) {
    if (!$site instanceof SiteInterface) {
      return parent::buildForm($form, $form_state);
    }
    $this->microThemeKey = 'micro_theme:' . $site->id();
    $this->settings = $this->getSettingsWithDefault($this->microThemeKey);

    $form['micro_theme_key'] = [
      '#type' => 'hidden',
      '#value' => $this->microThemeKey,
    ];

    $form['site_id'] = [
      '#type' => 'hidden',
      '#value' => $site->id(),
    ];

    $form['image'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image'),
      '#tree' => TRUE,
    ];

    $form['image']['default_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Default image'),
      '#description' => $this->t('A default image that can be used by the micro site'),
      '#default_value' => $this->settings['image']['default_image'] ?: NULL,
      '#upload_validators' => [
          'file_validate_extensions' => [implode(' ', $this->imageFactory->getSupportedExtensions())],
          'file_validate_size' => [25600000],
        ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'thumbnail',
      '#upload_location' => 'public://images/default/',
      '#required' => FALSE,
    ];

    $form['font'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fonts'),
      '#tree' => TRUE,
    ];

    $form['font']['override_font'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the default fonts'),
      '#default_value' => $this->settings['font']['override_font'],
      '#description' => $this->t('You could select below some fonts available on <a href="@url" target="_blank">Google Font</a>', ['@url' => 'https://fonts.google.com/'])
    ];

    $example_file_fonts = drupal_get_path('module', 'micro_theme') . '/css/example_fonts.css';
    $form['font']['file_font'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fonts file css'),
      '#maxlength' => 254,
      '#required' => TRUE,
      '#size' => 128,
      '#description' => $this->t('Set the relative path from the Drupal root for the css file to use. Example: modules/contrib/micro_theme/css/example_fonts.css.'),
      '#default_value' => ($this->settings['font']['file_font']) ?: $example_file_fonts,
      '#states' => [
        'visible' => [
          ':input[name="font[override_font]"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['font']['base_font'] = [
      '#type' => 'select',
      '#title' => $this->t('Base font'),
      '#description' => $this->t('Select the font used as the base font.'),
      '#options' => $this->librariesService->getFonts(),
      '#size' => 1,
      '#multiple' => FALSE,
      '#default_value' => $this->settings['font']['base_font'],
      '#states' => [
        'visible' => [
          ':input[name="font[override_font]"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['font']['title_font'] = [
      '#type' => 'select',
      '#title' => $this->t('Title font'),
      '#description' => $this->t('Select the font used for the title.'),
      '#options' => $this->librariesService->getFonts(),
      '#size' => 1,
      '#multiple' => FALSE,
      '#default_value' => $this->settings['font']['title_font'],
      '#states' => [
        'visible' => [
          ':input[name="font[override_font]"]' => array('checked' => TRUE),
        ],
      ],
    ];


    $form['color'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Colors'),
      '#tree' => TRUE,
    ];

    $form['color']['override_color'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the default colors used on the site'),
      '#default_value' => $this->settings['color']['override_color'],
    ];

    $example_file_colors = drupal_get_path('module', 'micro_theme') . '/css/example_colors.css';
    $form['color']['file_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Colors file css'),
      '#maxlength' => 254,
      '#size' => 128,
      '#required' => TRUE,
      '#description' => $this->t('Set the relative path from the Drupal root for the css file to use. Example: modules/contrib/micro_theme/css/example_colors.css'),
      '#default_value' => ($this->settings['color']['file_color']) ?: $example_file_colors,
      '#states' => [
        'visible' => [
          ':input[name="color[override_color]"]' => array('checked' => TRUE),
        ],
      ],
    ];

    $form['color']['palette'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Palette'),
      '#tree' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="color[override_color]"]' => array('checked' => TRUE),
        ],
      ],
    ];


    foreach ($this->colorsDefault as $color_key => $values) {
      $form['color']['palette'][$color_key] = [
        '#type' => 'textfield',
        '#title' => $this->t($values['name']),
        '#maxlength' => 64,
        '#size' => 64,
        '#default_value' => (!empty($this->settings['color']['palette'][$color_key]) && $this->settings['color']['override_color']) ? $this->settings['color']['palette'][$color_key] : $values['value'],
        '#states' => [
          'visible' => [
            ':input[name="color[override_color]"]' => array('checked' => TRUE),
          ],
        ],
      ];
    }

    $form['#attached']['library'][] = 'micro_theme/form';
    $form['#attached']['library'][] = 'micro_theme/jquery_minicolors';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    if ($values['font']['override_font']) {
      if (empty($values['font']['file_font'])) {
        $form_state->setError($form['font']['file_font'], $this->t('The font file css file is mandatory.'));
      }
      if (!is_file($values['font']['file_font'])) {
        $form_state->setError($form['font']['file_font'], $this->t('The font file css file has not been found. Please check the file path relative to the Drupal root directory.'));
      }
    }

    if ($values['color']['override_color']) {
      if (empty($values['color']['file_color'])) {
        $form_state->setError($form['color']['file_color'], $this->t('The color file css file is mandatory.'));
      }
      if (!is_file($values['color']['file_color'])) {
        $form_state->setError($form['color']['file_color'], $this->t('The font file css file has not been found. Please check the file path relative to the Drupal root directory.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();
    $micro_theme_key = $values['micro_theme_key'];
    $site_id = $values['site_id'];
    if ($micro_theme_key) {
      foreach ($values as $key => $value) {
        if (!in_array($key, ['image', 'font', 'color'])) {
          unset($values[$key]);
        }
      }
      $this->state->set($micro_theme_key, $values);
      MicroAssetFileStorage::deleteAllSiteFiles($site_id);
      Cache::invalidateTags([$micro_theme_key]);
    }
  }

  /**
   * Get the default theme logo.
   *
   * @return string
   *   the logo path.
   */
  protected function getLogo() {
    $config = $this->config('system.theme');
    $default_theme = $this->config('system.theme')->get('default');
    $path = theme_get_setting('logo.path', $default_theme);
    $logo_path = file_create_url(theme_get_setting('logo.path', $default_theme));
    $relative_logo_path = file_url_transform_relative($logo_path);
    return  $relative_logo_path;
  }

  protected function getSettingsWithDefault($key) {
    $settings = $this->state->get($key) ?: [];
    $defaults = [
      'image' => [
        'default_image' => '',
      ],
      'font' => [
        'override_font' => '',
        'file_font' => '',
        'base_font' => '',
        'title_font' => '',
      ],
      'color' => [
        'override_color' => '',
        'file_color' => '',
        'palette' => [],
      ],
    ];
    $settings = $settings + $defaults;
    return $settings;
  }

}
