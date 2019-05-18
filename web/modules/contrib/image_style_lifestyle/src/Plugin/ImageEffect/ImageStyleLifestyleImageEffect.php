<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\RotateImageEffect.
 */

namespace Drupal\image_style_lifestyle\Plugin\ImageEffect;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ConfigurableImageEffectBase;
use Drupal\image\Entity\ImageStyle;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Allows you to apply different image styles based on the image.
 *
 * @ImageEffect(
 *   id = "image_style_lifestyle",
 *   label = @Translation("Image Style Lifestyle"),
 *   description = @Translation("Allows you to apply different image styles based on the image.")
 * )
 */
class ImageStyleLifestyleImageEffect extends ConfigurableImageEffectBase implements ConfigurableImageEffectInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
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
      $container->get('logger.factory')->get('image'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {

    $style_name = $this->isRender($image) ? $this->configuration['render_image_style'] : $this->configuration['lifestyle_image_style'];

    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = ImageStyle::load($style_name);

    if (empty($style)) {
      // Required preset has gone missing?
      $this->logger->error("When running 'lifestyle or render switcher' action, I was unable to load sub-action %style_name. Either it's been deleted or the DB needs an update", array('%style_name' => $style_name));
      return FALSE;
    }

    // Run the preset actions.
    foreach ($style->getEffects() as $effect) {
      $effect->applyEffect($image);
    }

    return TRUE;
  }

  /**
   * Attempt to detect a render using the corner pixel or the image.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object returned by image_load().
   *
   * @return bool
   *   TRUE if it's a render image, FALSE otherwise.
   */
  public function isRender(ImageInterface $image) {
    $callback = $this->getIsRenderCallback();

    if (function_exists($callback)) {
      call_user_func(array($this, $callback), array($image));
    }
    return $this->cornerIsWhite($image);
  }

  /**
   * Checks if the corner of the image is white.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object returned by image_load().
   *
   * @return bool
   *   TRUE if the 0,0 pixel is #FFF.
   */
  public function cornerIsWhite(ImageInterface $image) {
    if ($this->isTransparent($image)) {
      return TRUE;
    }

    // Get the colour of the top level pixel.
    $rgb = imagecolorat($this->getResource($image), 0, 0);

    return $this->withinTolerance($rgb, $this->configuration['tolerance']);
  }

  /**
   * Samples the image for an average colour.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object returned by image_load().
   *
   * @return bool
   *   TRUE if the average colour is #FFF or transparent.
   */
  public function averageColourIsWhite(ImageInterface $image) {
    if ($this->isTransparent($image)) {
      return TRUE;
    }

    // Create a new image resource 1x1.
    $new_image = imagecreatetruecolor(1, 1);

    // Get the x and y of the image we're testing.
    $width = imagesx($this->getResource($image));
    $height = imagesy($this->getResource($image));

    // Resize the image into the 1x1 canvas.
    imagecopyresampled($new_image, $this->getResource($image), 0, 0, 0, 0, 1, 1, $width, $height);

    // Get the colour of the new image.
    $rgb = imagecolorat($new_image, 0, 0);

    return $this->withinTolerance($rgb, $this->configuration['tolerance']);
  }

  /**
   * Get the image resource.
   *
   * We abstract this out because it's specific to the GDToolkit. Most of this
   * class is but in the future we may support other toolkits.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   * @return null|resource
   */
  protected function getResource(ImageInterface $image) {
    /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $toolkit */
    $toolkit = $image->getToolkit();
    return $toolkit->getResource();
  }

  /**
   * Check if the image has a transparent background.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   An image object returned by image_load().
   *
   * @return bool
   *   TRUE if the background is transparent.
   */
  public function isTransparent(ImageInterface $image) {
    $rgb = imagecolorat($this->getResource($image), 0, 0);
    $a = $rgb >> 24;

    return $a == 127;
  }

  /**
   * Check if the colour is within the tolerance setting.
   *
   * @param int $rgb
   *   The rgb colour.
   * @param int $tolerance
   *   The tolerance as a percentage.
   *
   * @return bool
   *   TRUE if the colour is within our tolerance level.
   */
  public function withinTolerance($rgb, $tolerance) {
    // 16777215 is white.
    $percentile = 16777215 - (16777215 * ($tolerance / 100));
    return $rgb > $percentile;
  }


  /**
   * Get the callback for determining if it's a render image.
   *
   * @return string
   *   The callback function.
   */
  public function getIsRenderCallback() {
    $callback = 'cornerIsWhite';
    $this->moduleHandler->alter('image_style_lifestyle_is_render', $callback);

    return $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#markup' => $this->t('Apply different image styles based on the type of image.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'render_image_style' => '',
      'lifestyle_image_style' => '',
      'tolerance' => 30,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['help']['#markup'] = $this->t('You must create the two presets to use <em>before</em> enabling this process.');

    $styles = image_style_options(TRUE);
    $form['render_image_style'] = array(
      '#type' => 'select',
      '#title' => t('Style to use if the image is a render'),
      '#default_value' => $this->configuration['render_image_style'],
      '#options' => $styles,
      '#description' => t('If you choose none nothing will happen.'),
    );
    $form['lifestyle_image_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Style to use if the image is a lifestyle image.'),
      '#default_value' => $this->configuration['lifestyle_image_style'],
      '#options' => $styles,
      '#description' => $this->t('If you choose none nothing will happen.'),
    );
    $form['tolerance'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('The tolerance to use when detecting the image colour.'),
      '#default_value' => $this->configuration['tolerance'],
      '#description' => $this->t('The higher the tolerance the more likely to be selected as a render. Default 30%'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['render_image_style'] = $form_state->getValue('render_image_style');
    $this->configuration['lifestyle_image_style'] = $form_state->getValue('lifestyle_image_style');
    $this->configuration['tolerance'] = $form_state->getValue('tolerance');
  }

}
