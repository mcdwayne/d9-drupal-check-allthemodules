<?php

/**
 * @file
 * Contains \Drupal\imagecache_reflect\Plugin\ImageEffect\ReflectImageEffect.
 */

namespace Drupal\imagecache_reflect\Plugin\ImageEffect;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Image\ImageInterface;
use Drupal\image\Annotation\ImageEffect;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageEffectBase;
use Drupal\imagecache_reflect\Plugin\ImageToolkit\GDToolkitReflect;

/**
 * Creates a reflection-like effect on an image resource.
 *
 * @ImageEffect(
 *   id = "imagecache reflect",
 *   label = @Translation("Reflect"),
 *   description = @Translation("Creates a reflection-like image effect.")
 * )
 */
class ReflectImageEffect extends ImageEffectBase implements ConfigurableImageEffectInterface {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // Apply operation.
    // @todo: Convert this operation to a toolkit plugin.
    // @todo: Add support for imagemagick.
    if (!GDToolkitReflect::reflect($image, $this->configuration)) {
      watchdog('image', 'Image reflect failed using the %toolkit toolkit on %path (%mimetype, %configuration)', array(
        '%toolkit' => $image->getToolkitId(),
        '%path' => $image->getSource(),
        '%mimetype' => $image->getMimeType(),
        '%configuration' => 'Size: ' . $this->configuration['size'] . ', Position: ' . $this->configuration['position']),
        WATCHDOG_ERROR
      );
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#theme' => 'imagecache_reflect_summary',
      '#data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'bgcolor' => '#FFFFFF',
      'position' => 'bottom',
      'transparency' => FALSE,
      'size' => '50%',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form['bgcolor'] = array(
      '#type' => 'textfield',
      '#title' => t('Color'),
      '#description' => t('The color to use for the reflection background. Use web-style hex colors. e.g.) #FF6633. May be blank.'),
      '#default_value' => $this->configuration['bgcolor'],
      '#size' => 7,
      '#max_length' => 7,
      '#element_validate' => array(array($this, 'validateColor')),
    );
    $form['transparency'] = array(
      '#type' => 'checkbox',
      '#title' => t('transparency source image'),
      '#default_value' => $this->configuration['transparency'],
      '#description' => t('If the image that you are reflecting uses alpha transparency, optionally use a much slower algorithm for creating the images, but one that will preserve the transparency.'),
    );
    $form['position'] = array(
      '#type' => 'radios',
      '#title' => t('Position'),
      '#default_value' => $this->configuration['position'],
      '#options' => array(
        'top' => t('Top'),
        'right' => t('Right'),
        'bottom' => t('Bottom'),
        'left' => t('Left'),
      ),
      '#description' => t('The position of the image reflection. Default is bottom.'),
      '#required' => TRUE,
    );
    $form['size'] = array(
      '#type' => 'textfield',
      '#title' => t('Size'),
      '#default_value' => $this->configuration['size'],
      '#description' => t('The size of the reflection in pixels. You may append % to the integer to represent percentages.'),
      '#required' => TRUE,
      '#element_validate' => array(array($this, 'validateSize')),
    );
    return $form;
  }

  /**
   * Validates to ensure a hexadecimal color value or empty.
   * 
   * @todo Use drupal 8 validation API? Symfony has regex validator.
   */
  public static function validateColor(array $element, array &$form_state) {
    if (!preg_match('/^#[0-9A-F]{3}([0-9A-F]{3})?$|^$/', $element['#value'])) {
      \Drupal::formBuilder()->setError($element, \t('!name must be a hexadecimal color value or empty.', array('!name' => $element['#title'])));
    }
  }
  
  /**
   * Validates to ensure a percentage or positive integer.
   * 
   * @todo Use drupal 8 validation API? Symfony has regex validator.
   */
  public static function validateSize(array $element, array &$form_state) {
    if (!preg_match('/^([0-9]{1,2}|100)%$|^([0-9]{1,3})$/', $element['#value'])) {
      \Drupal::formBuilder()->setError($element, \t('!name must be a percentage from 1 to 100 or a positive integer 3 digits or less.', array('!name' => $element['#title'])));
    }
  }
}
