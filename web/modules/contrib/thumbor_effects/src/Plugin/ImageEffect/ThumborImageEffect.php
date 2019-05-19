<?php

namespace Drupal\thumbor_effects\Plugin\ImageEffect;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\ConfigurableImageEffectBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides Thumbor Smart Imaging effects.
 *
 * @ImageEffect(
 *   id = "thumbor_effects",
 *   label = @Translation("Thumbor Effects"),
 *   description = @Translation("Use Thumbor Smart Imaging effects.")
 * )
 */
class ThumborImageEffect extends ConfigurableImageEffectBase {

  /**
   * The minimum tolerance allowed for trimming.
   *
   * @see \Drupal\thumbor_effects\Plugin\ImageEffect\ThumborImageEffect::validateTolerance().
   */
  private const TOLERANCE_MIN = 0;

  /**
   * The maximum tolerance allowed for trimming.
   *
   * @see \Drupal\thumbor_effects\Plugin\ImageEffect\ThumborImageEffect::validateTolerance().
   */
  private const TOLERANCE_MAX = 422;

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ClientInterface $http_client, ImageFactory $image_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);

    $this->httpClient = $http_client;
    $this->imageFactory = $image_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('http_client'),
      $container->get('image.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image): bool {
    $thumbor_url = static::getUrl($this->configuration, $image);

    try {
      $response = $this->httpClient->request('GET', $thumbor_url, ['headers' => ['Accept' => 'text/plain']]);
      $data = $response->getBody()->getContents();
    }
    catch (GuzzleException $exception) {
      $this->logger->error('Thumbor effect failed trying to process image ( Code : %status_code , URL : %url) with message: %message', [
        '%status_code' => $exception->getCode(),
        '%url' => $thumbor_url,
        '%message' => $exception->getMessage(),
      ]);
      return FALSE;
    }

    if (empty($data) || $response->getStatusCode() !== Response::HTTP_OK) {
      $this->logger->error('Thumbor effect failed trying proccess image ( Code : %status_code , URL : %url)', [
        '%status_code' => $response->getStatusCode(),
        '%url' => $thumbor_url,
      ]);
      return FALSE;
    }

    return $image->apply('create_from_string', ['string' => $data]);
  }

  /**
   * {@inheritdoc}
   *
   * The Responsive images element needs the dimensions of the image before the
   * image is modified.
   */
  public function transformDimensions(array &$dimensions, $uri) {
    if (!$this->configuration['fit_in'] && $this->configuration['image_size_enable'] && $this->configuration['image_size']['width']) {
      $dimensions['width'] = $this->configuration['image_size']['width'];

      // The height is not really important for responsive images.
      if ($this->configuration['image_size']['height']) {
        $dimensions['height'] = $this->configuration['image_size']['height'];
      }
      return;
    }

    $image = $this->imageFactory->get($uri);
    $this->applyEffect($image);

    $dimensions = [
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'smart' => 1,
      'image_size_enable' => 1,
      'image_size' =>
        [
          'width' => '',
          'height' => '',
        ],
      'fit_in' => '',
      'trim_enable' => 0,
      'trim' =>
        [
          'orientation' => 'trim',
          'tolerance' => '0',
        ],
      'manual_crop_enable' => 0,
      'manual_crop' =>
        [
          'top_left' => [
            'x' => '0',
            'y' => '0',
          ],
          'bottom_right' => [
            'x' => '0',
            'y' => '0',
          ],
        ],
      'crop_align_enable' => 0,
      'crop_align' =>
        [
          'horizontal' => '',
          'vertical' => '',
        ],
      'filters' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['help'] = [
      '#markup' => '<p>' . $this->t('Elaborate information about the usage of this effect can be found on the <a href=":url" target="_blank">Thumbor Wiki.</a>', [':url' => 'https://github.com/thumbor/thumbor/wiki/Usage']) . '</p>',
    ];

    $settings = [
      '#tree' => TRUE,
    ];

    $settings['smart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Smart'),
      '#required' => FALSE,
      '#description' => $this->t('Smart means using smart detection of focal points.'),
      '#default_value' => $this->configuration['smart'] ?? 1,
    ];

    $settings['image_size_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Set image size'),
      '#description' => $this->t('The image size argument specifies the size of the image that will be returned by the service. Thumbor uses smart Crop and resize algorithms.'),
      '#return_value' => 1,
      '#default_value' => $this->configuration['image_size_enable'] ?? 0,
    ];

    $settings['image_size'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Image size settings'),
      '#states' => [
        'visible' => [
          ':input[name="data[settings][image_size_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // @todo support 'orig' size.
    $settings['image_size']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#min' => 0,
      '#description' => $this->t('The default value (in case it is omitted) for this option is to use proportional size (0) to the original image.'),
      '#default_value' => $this->configuration['image_size']['width'] ?? 0,
    ];

    $settings['image_size']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#min' => 0,
      '#description' => $this->t('The default value (in case it is omitted) for this option is to use proportional size (0) to the original image.'),
      '#default_value' => $this->configuration['image_size']['height'] ?? 0,
    ];

    $settings['fit_in'] = [
      '#type' => 'select',
      '#title' => $this->t('Fit-in'),
      '#description' => $this->t('The image should not be auto-cropped and auto-resized to be EXACTLY the specified size.'),
      '#options' => [
        'fit-in' => $this->t('Fit-in'),
        'full-fit-in' => $this->t('Full fit-in'),
        'adaptive-fit-in' => $this->t('Adaptive fit-in'),
        'adaptive-full-fit-in' => $this->t('Adaptive full fit-in'),
      ],
      '#empty_option' => $this->t('- Disabled -'),
      '#default_value' => $this->configuration['fit_in'] ?? '',
      '#element_validate' => [[\get_class($this), 'validateFitIn']],
    ];

    $settings['trim_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trim'),
      '#description' => $this->t('Removing surrounding space in images can be done using the trim option.'),
      '#return_value' => 1,
      '#default_value' => $this->configuration['trim_enable'] ?? 0,
    ];

    $settings['trim'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trim settings'),
      '#states' => [
        'visible' => [
          ':input[name="data[settings][trim_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $settings['trim']['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Orientation'),
      '#options' => [
        'trim' => $this->t('Default'),
        'trim:top-left' => 'Trim: top-left',
        'trim:bottom-right' => 'Trim: bottom-right',
      ],
      '#description' => $this->t('Specify the orientation from where to get the pixel color.'),
      '#default_value' => $this->configuration['trim']['orientation'] ?? 'trim',
    ];

    $settings['trim']['tolerance'] = [
      '#type' => 'number',
      '#title' => $this->t('Tolerance'),
      '#min' => self::TOLERANCE_MIN,
      '#max' => self::TOLERANCE_MAX,
      '#required' => FALSE,
      '#description' => $this->t('For a RGB image the tolerance would be within the range 0-442.'),
      '#default_value' => $this->configuration['trim']['tolerance'] ?? 0,
      '#element_validate' => [[\get_class($this), 'validateTolerance']],
    ];

    $settings['manual_crop_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Manual crop'),
      '#description' => $this->t('Manually crop the image from the left-top and right-bottom.'),
      '#return_value' => 1,
      '#default_value' => $this->configuration['manual_crop_enable'] ?? 0,
    ];

    $settings['manual_crop'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Manual crop settings'),
      '#states' => [
        'visible' => [
          ':input[name="data[settings][manual_crop_enable]"]' => ['checked' => TRUE],
        ],
      ],
      '#element_validate' => [[\get_class($this), 'validateManualCrop']],
    ];

    $settings['manual_crop']['top_left'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Top left point'),
      '#error_no_message' => TRUE,
    ];

    $settings['manual_crop']['top_left']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('X-Coordinate'),
      '#min' => 0,
      '#default_value' => $this->configuration['manual_crop']['top_left']['x'] ?? 0,
      '#error_no_message' => TRUE,
    ];

    $settings['manual_crop']['top_left']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Y-Coordinate'),
      '#min' => 0,
      '#default_value' => $this->configuration['manual_crop']['top_left']['y'] ?? 0,
      '#error_no_message' => TRUE,
    ];

    $settings['manual_crop']['bottom_right'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bottom right point'),
      '#error_no_message' => TRUE,
    ];

    $settings['manual_crop']['bottom_right']['x'] = [
      '#type' => 'number',
      '#title' => $this->t('X-Coordinate'),
      '#min' => 0,
      '#default_value' => $this->configuration['manual_crop']['bottom_right']['x'] ?? 0,
      '#error_no_message' => TRUE,
    ];

    $settings['manual_crop']['bottom_right']['y'] = [
      '#type' => 'number',
      '#title' => $this->t('Y-Coordinate'),
      '#min' => 0,
      '#default_value' => $this->configuration['manual_crop']['bottom_right']['y'] ?? 0,
      '#error_no_message' => TRUE,
    ];

    $settings['crop_align_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Crop alignment'),
      '#description' => $this->t('Horizontal and vertical alignment of non-smart cropping.'),
      '#return_value' => 1,
      '#default_value' => $this->configuration['crop_align_enable'] ?? 0,
    ];

    $settings['crop_align'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Crop alignment settings'),
      '#states' => [
        'visible' => [
          ':input[name="data[settings][crop_align_enable]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $settings['crop_align']['horizontal'] = [
      '#type' => 'select',
      '#title' => $this->t('Horizontal'),
      '#description' => $this->t('The default value (in case it is omitted) for this option is "center".'),
      '#options' => [
        'left' => 'Left',
        'right' => 'Right',
      ],
      '#empty_option' => $this->t('- Default: Center -'),
      '#default_value' => $this->configuration['crop_align']['horizontal'] ?? '',
    ];

    $settings['crop_align']['vertical'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical'),
      '#description' => $this->t('The default value (in case it is omitted) for this option is "middle".'),
      '#options' => [
        'top' => 'Top',
        'bottom' => 'Bottom',
      ],
      '#empty_option' => $this->t('- Default: Middle -'),
      '#default_value' => $this->configuration['crop_align']['vertical'] ?? '',
    ];

    $settings['filters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filters'),
      '#size' => 120,
      '#maxlength' => 255,
      '#description' => $this->t('Filters can be applied sequentially to the image before returning. Ex. FILTERNAME(ARGUMENT):FILTERNAME(ARGUMENT)'),
      '#default_value' => $this->configuration['filters'] ?? 0,
    ];

    $form['settings'] = $settings;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration = $form_state->getValue('settings');
  }

  /**
   * Validates the fit-in setting.
   *
   * @param mixed[] $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFitIn(array $element, FormStateInterface $form_state): void {
    $settings = $form_state->getValue(['data', 'settings']);

    if (!empty($settings['fit_in']) && empty($settings['image_size']['width']) && empty($settings['image_size']['height'])) {
      $form_state->setError($element, new TranslatableMarkup('When using fit-in or full-fit-in, you must specify a width and/or height.'));
    }
  }

  /**
   * Validates the manual crop setting.
   *
   * @param mixed[] $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateManualCrop(array $element, FormStateInterface $form_state): void {
    $settings = $form_state->getValue(['data', 'settings']);

    if (empty($settings['manual_crop_enable'])) {
      return;
    }

    $crop = $settings['manual_crop'];

    if ($crop['top_left']['x'] >= $crop['bottom_right']['x'] || $crop['top_left']['y'] >= $crop['bottom_right']['y']) {
      $form_state->setError($element, new TranslatableMarkup('The points overlap.'));
    }
  }

  /**
   * Validates the tolerance setting.
   *
   * @param mixed[] $element
   *   The form element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateTolerance(array $element, FormStateInterface $form_state): void {
    $tolerance = $element['#value'];

    if (empty($tolerance)) {
      return;
    }

    if ($tolerance < static::TOLERANCE_MIN || $tolerance > static::TOLERANCE_MAX) {
      $form_state->setError($element, new TranslatableMarkup('For a RGB image the tolerance has to be within the range 0-442.'));
    }
  }

  /**
   * Replaces the public file base URL of a public image with the overwrite.
   *
   * This may have unintended effects when the 'file_public_base_url' setting
   * doesn't contain the global 'base_url'.
   *
   * @param string $image_url
   *   The URL of the public image.
   *
   * @return string
   *   The image url with the base url replaced.
   */
  public static function overwriteBaseUrl($image_url): string {
    $url_overwrite = \Drupal::config('thumbor_effects.settings')
      ->get('base_url_overwrite');

    if (empty($url_overwrite)) {
      return $image_url;
    }

    return \str_replace($GLOBALS['base_url'], $url_overwrite, $image_url);
  }

  /**
   * Get the Thumbor URL parameters for the specified transformations and image.
   *
   * @param array $configuration
   *   The Thumbor Effects configuration.
   * @param string $image_url
   *   The original public URL of the image that needs to be transformed.
   *
   * @return array
   *   The URL parameters.
   */
  protected static function getUrlParameters(array $configuration, $image_url): array {
    $thumbor_params = [];

    if (!empty($configuration['trim_enable'])) {
      if (!empty($configuration['trim']['tolerance'])) {
        $thumbor_params[] = $configuration['trim']['orientation'] . ':' . $configuration['trim']['tolerance'];
      }
      else {
        $thumbor_params[] = $configuration['trim']['orientation'];
      }
    }

    if (!empty($configuration['manual_crop_enable'])) {
      $top_left = \array_filter($configuration['manual_crop']['top_left']) + [
        'x' => 0,
        'y' => 0,
      ];

      $bottom_right = \array_filter($configuration['manual_crop']['bottom_right']) + [
        'x' => 0,
        'y' => 0,
      ];

      $thumbor_params[] = $top_left['x'] . 'x' . $top_left['y'] . ':' . $bottom_right['x'] . 'x' . $bottom_right['y'];
    }

    if (!empty($configuration['fit_in'])) {
      $thumbor_params[] = $configuration['fit_in'];
    }

    if (!empty($configuration['image_size_enable'])) {
      $dimensions = \array_filter($configuration['image_size']) + [
        'width' => 0,
        'height' => 0,
      ];

      if (\array_filter($dimensions)) {
        $thumbor_params[] = $dimensions['width'] . 'x' . $dimensions['height'];
      }
    }

    if (!empty($configuration['crop_align_enable'])) {
      if ($configuration['crop_align']['horizontal']) {
        $thumbor_params[] = $configuration['crop_align']['horizontal'];
      }
      if ($configuration['crop_align']['vertical']) {
        $thumbor_params[] = $configuration['crop_align']['vertical'];
      }
    }

    if (!empty($configuration['smart'])) {
      $thumbor_params[] = 'smart';
    }

    if (!empty($configuration['filters'])) {
      $thumbor_params[] = 'filters:' . $configuration['filters'];
    }

    $thumbor_params[] = static::overwriteBaseUrl($image_url);

    return $thumbor_params;
  }

  /**
   * Create the full Thumbor URL from URL parameters.
   *
   * @param array $url_params
   *   Array of URL parameters.
   *
   * @return string
   *   The formatted Thumbor URL.
   */
  protected static function getUrlFromParameters(array $url_params): string {
    $url_params_string = \implode('/', $url_params);

    $settings = \Drupal::config('thumbor_effects.settings');
    $thumbor_server = $settings->get('server');
    $thumbor_unsafe = $settings->get('unsafe');

    if ($thumbor_unsafe) {
      return $thumbor_server . '/unsafe/' . $url_params_string;
    }

    $thumbor_security_key = $settings->get('security_key');
    $thumbor_hmac = \base64_encode(hash_hmac('sha1', $url_params_string, $thumbor_security_key, TRUE));
    $thumbor_hmac = \str_replace(['+', '/'], ['-', '_'], $thumbor_hmac);

    return $thumbor_server . '/' . $thumbor_hmac . '/' . $url_params_string;
  }

  /**
   * Get the Thumbor URL for the specified transformations and image.
   *
   * @param array $configuration
   *   The Thumbor Effects configuration.
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image that needs to be transformed.
   *
   * @return string
   *   The formatted Thumbor URL.
   */
  public static function getUrl(array $configuration, ImageInterface $image): string {
    $image_url = \file_create_url($image->getSource());
    return static::getUrlFromParameters(static::getUrlParameters($configuration, $image_url));
  }

}
