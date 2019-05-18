<?php

namespace Drupal\cloudinary\Plugin\ImageEffect;

use Drupal\Component\Utility\Rectangle;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\image\ConfigurableImageEffectBase;
use Drupal\image\ImageEffectBase;

/**
 * Provides a 'CloudinaryCrop' image effect.
 *
 * @ImageEffect(
 *  id = "cloudinary_crop",
 *  label = @Translation("Cloudinary crop"),
 *  description = @Translation("Apply effects, resizing, cropping, face detection and tons of image processing capabilities.")
 * )
 */
class CloudinaryCrop extends ConfigurableImageEffectBase {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (!empty($this->configuration['random'])) {
      $degrees = abs((float) $this->configuration['degrees']);
      $this->configuration['degrees'] = rand(-$degrees, $degrees);
    }

    if (!$image->rotate($this->configuration['degrees'], $this->configuration['bgcolor'])) {
      $this->logger->error('Image rotate failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    // If the rotate is not random and current dimensions are set,
    // then the new dimensions can be determined.
    if (empty($this->configuration['random']) && $this->configuration['width'] && $this->configuration['height']) {
      $rect = new Rectangle($this->configuration['width'], $this->configuration['height']);
      $rect = $rect->rotate($this->configuration['degrees']);
      $dimensions['width'] = $rect->getBoundingWidth();
      $dimensions['height'] = $rect->getBoundingHeight();
    }
    else {
      $dimensions['width'] = $dimensions['height'] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = array(
      '#theme' => 'image_rotate_summary',
      '#data' => $this->configuration,
    );
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'width' => NULL,
      'height' => NULL,
      'crop' => NULL,
      'gravity' => NULL,
      'x' => NULL,
      'y' => NULL,
      'radius' => NULL,
      'angle' => NULL,
      'automatic_rotation' => NULL,
      'angles' => [],
      'effect' => NULL,
      'effects_param' => NULL,
      'opacity' => NULL,
      'border_width' => NULL,
      'border_color' => NULL,
      'background' => NULL,
      'random' => FALSE,
      'degrees' => NULL,
      'default_image' => NULL,
      'quality' => NULL,
      'fetch_format' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $container = array(
      '#prefix' => '<div class="container-inline clearfix">',
      '#suffix' => '</div>',
    );

    $form['#attached']['library'] = [
      'core/ui.slider',
      'core/jquery.farbtastic',
      'cloudinary/cloudinary-lib',
    ];

    $form['cloudinary'] = array();

    // Show the thumbnail preview.
    $form['cloudinary']['preview'] = array(
      '#prefix' => '<div class="clearfix">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    );

    $preview = $this->cloudinaryCropFormPreview();
    $form['cloudinary']['preview']['thumbnail'] = array(
      '#prefix' => '<div id="cloudinary_transformation_preview">',
      '#suffix' => '</div>',
      '#type' => 'item',
      '#title' => $this->t('Preview'),
      '#markup' => render($preview),
    );

    $form['cloudinary']['preview']['reset'] = array(
      '#value' => $this->t('Reset'),
      '#type' => 'button',
    );

    $form['cloudinary']['preview']['preview'] = array(
      '#value' => $this->t('Preview'),
      '#type' => 'button',
      '#ajax' => array(
        'callback' => 'cloudinary_crop_form_preview_callback',
        'wrapper' => 'cloudinary_transformation_preview',
      ),
    );

    $form['cloudinary']['resize_crop'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Resize & Crop'),
    );

    $form['cloudinary']['resize_crop']['one'] = $container;

    $form['cloudinary']['resize_crop']['one']['width'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'width'),
      '#title' => $this->t('Width'),
      '#default_value' => $this->configuration['width'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider')),
    );

    $form['cloudinary']['resize_crop']['one']['height'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'height'),
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['height'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider')),
    );

    $form['cloudinary']['resize_crop']['one']['crop'] = array(
      '#type' => 'select',
      '#parents' => array('data', 'crop'),
      '#title' => $this->t('Mode'),
      '#default_value' => $this->configuration['crop'],
      '#options' => _cloudinary_options_crop(),
    );

    $form['cloudinary']['resize_crop']['two'] = $container;

    $form['cloudinary']['resize_crop']['two']['gravity'] = array(
      '#type' => 'select',
      '#parents' => array('data', 'gravity'),
      '#title' => $this->t('Gravity'),
      '#default_value' => $this->configuration['gravity'],
      '#options' => $this->getGravityOptions(),
      '#states' => array(
        'visible' => array(
          ':input[name="data[crop]"]' => $this->buildVisibleStates(CLOUDINARY_VISIBLE_STATES_CROP),
        ),
      ),
    );

    $x_y_states = array('visible' => array(':input[name="data[crop]"]' => array(array('value' => 'crop'))));
    $form['cloudinary']['resize_crop']['two']['x'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'x'),
      '#title' => $this->t('X'),
      '#default_value' => $this->configuration['x'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider')),
      '#states' => $x_y_states,
    );

    $form['cloudinary']['resize_crop']['two']['y'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'y'),
      '#title' => $this->t('Y'),
      '#default_value' => $this->configuration['y'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider')),
      '#states' => $x_y_states,
    );

    $form['cloudinary']['shape'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Shape'),
    );

    $form['cloudinary']['shape']['one'] = $container;

    $form['cloudinary']['shape']['one']['radius'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'radius'),
      '#title' => $this->t('Corner Radius'),
      '#default_value' => $this->configuration['radius'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider'), 'data' => 'dynamic_0_100_slider-small'),
    );

    $form['cloudinary']['shape']['one']['angle'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'angle'),
      '#title' => $this->t('Rotation Angle'),
      '#default_value' => $this->configuration['angle'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider'), 'data' => 'fixed_0_360_slider-small'),
    );

    $form['cloudinary']['shape']['one']['automatic_rotation'] = array(
      '#type' => 'checkbox',
      '#parents' => array('data', 'automatic_rotation'),
      '#title' => $this->t('Automatic rotation'),
      '#default_value' => $this->configuration['automatic_rotation'],
    );

    $form['cloudinary']['shape']['two'] = $container;

    $form['cloudinary']['shape']['two']['angles'] = array(
      '#type' => 'checkboxes',
      '#parents' => array('data', 'angles'),
      '#title' => $this->t('Angles'),
      '#title_display' => 'invisible',
      '#default_value' => $this->configuration['angles'],
      '#options' => _cloudinary_options_angles(),
      '#states' => array(
        'visible' => array(
          ':input[name="data[automatic_rotation]"]' => array('checked' => TRUE),
        ),
      ),
    );

    $form['cloudinary']['look_feel'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Look & Feel'),
    );

    $form['cloudinary']['look_feel']['one'] = $container;

    $form['cloudinary']['look_feel']['one']['effect'] = array(
      '#type' => 'select',
      '#parents' => array('data', 'effect'),
      '#title' => $this->t('Effect'),
      '#default_value' => $this->configuration['effect'],
      '#options' => _cloudinary_options_effect(),
    );

    $form['cloudinary']['look_feel']['one']['effects_param'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'effects_param'),
      '#title_display' => 'invisible',
      '#title' => $this->t('Effects Param'),
      '#default_value' => $this->configuration['effects_param'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider'), 'data' => 'fixed_0_100_slider-small'),
      '#states' => array(
        'visible' => array(
          ':input[name="data[effect]"]' => $this->buildVisibleStates(CLOUDINARY_VISIBLE_STATES_EFFECT),
        ),
      ),
    );

    $form['cloudinary']['more'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('More Options'),
    );

    $form['cloudinary']['more']['one'] = array(
      '#prefix' => '<div id="farbtastic-color"></div><div class="container-inline clearfix">',
      '#suffix' => '</div>',
    );

    $form['cloudinary']['more']['one']['opacity'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'opacity'),
      '#title' => $this->t('Opacity'),
      '#default_value' => $this->configuration['opacity'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider'), 'data' => 'fixed_0_100_slider-small'),
    );

    $form['cloudinary']['more']['one']['border_width'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'border_width'),
      '#title' => $this->t('Border'),
      '#default_value' => $this->configuration['border_width'],
      '#size' => 4,
      '#attributes' => array('class' => array('input_slider'), 'data' => 'dynamic_0_100_slider-small'),
    );

    $form['cloudinary']['more']['one']['border_color'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'border_color'),
      '#title' => $this->t('Border color'),
      '#default_value' => $this->configuration['border_color'],
      '#size' => 8,
      '#maxlength' => 7,
      '#attributes' => array('class' => array('input_color')),
    );

    $form['cloudinary']['more']['one']['background'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'background'),
      '#title' => $this->t('Background'),
      '#default_value' => $this->configuration['background'],
      '#size' => 8,
      '#maxlength' => 7,
      '#attributes' => array('class' => array('input_color')),
    );

    $form['cloudinary']['more']['one']['default_image'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'default_image'),
      '#title' => t('Default Image'),
      '#default_value' => $this->configuration['default_image'],
      '#size' => 15,
      '#maxlength' => 15,
      '#attributes' => array('class' => array('default_image')),
    );

    $form['cloudinary']['more']['one']['quality'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'quality'),
      '#title' => t('Image Optimization'),
      '#default_value' => $this->configuration['quality'],
      '#size' => 15,
      '#maxlength' => 15,
      '#attributes' => array('class' => array('quality')),
    );

    $form['cloudinary']['more']['one']['fetch_format'] = array(
      '#type' => 'textfield',
      '#parents' => array('data', 'fetch_format'),
      '#title' => t('Fetch format'),
      '#default_value' => $this->configuration['fetch_format'],
      '#size' => 15,
      '#maxlength' => 15,
      '#attributes' => array('class' => array('fetch_format')),
    );

    return $form;
  }

  /**
   * Build options for gravity.
   */
  protected function getGravityOptions($key = NULL) {
    $data = array(
      '' => t('None'),
      'face' => t('Face'),
      'faces' => t('Faces'),
      'north_west' => t('North West'),
      'north' => t('North'),
      'north_east' => t('North East'),
      'east' => t('East'),
      'center' => t('Center'),
      'west' => t('West'),
      'south_west' => t('South West'),
      'south' => t('South'),
      'south_east' => t('South East'),
      'face:center' => t('Face (Center)'),
      'faces:center' => t('Faces (Center)'),
      'custom' => t('Custom'),
      'xy_center' => t('XY Center'),
      'rek_face' => t('ReKognition: Face'),
      'rek_faces' => t('ReKognition: Faces'),
      'rek_eyes' => t('ReKognition: Eyes'),
    );

    if (!is_null($key) && isset($data[$key])) {
      return $data[$key];
    }

    return $data;
  }

  /**
   * Build group conditions of visible states.
   */
  protected function buildVisibleStates($visible) {
    $data = [];
    $datas = explode(',', $visible);
    foreach ($datas as $value) {
      $data[] = ['value' => $value];
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
//    if (!$form_state->isValueEmpty('bgcolor') && !Color::validateHex($form_state->getValue('bgcolor'))) {
//      $form_state->setErrorByName('bgcolor', $this->t('Background color must be a hexadecimal color value.'));
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['width'] = $form_state->getValue('width');
    $this->configuration['height'] = $form_state->getValue('height');
    $this->configuration['crop'] = $form_state->getValue('crop');
    $this->configuration['gravity'] = $form_state->getValue('gravity');
    $this->configuration['x'] = $form_state->getValue('x');
    $this->configuration['y'] = $form_state->getValue('y');
    $this->configuration['radius'] = $form_state->getValue('radius');
    $this->configuration['angle'] = $form_state->getValue('angle');
    $this->configuration['automatic_rotation'] = $form_state->getValue('automatic_rotation');
    $this->configuration['angles'] = $form_state->getValue('angles');
    $this->configuration['effect'] = $form_state->getValue('effect');
    $this->configuration['effects_param'] = $form_state->getValue('effects_param');
    $this->configuration['opacity'] = $form_state->getValue('opacity');
    $this->configuration['border_width'] = $form_state->getValue('border_width');
    $this->configuration['border_color'] = $form_state->getValue('border_color');
    $this->configuration['background'] = $form_state->getValue('background');
    $this->configuration['default_image'] = $form_state->getValue('default_image');
    $this->configuration['quality'] = $form_state->getValue('quality');
    $this->configuration['fetch_format'] = $form_state->getValue('fetch_format');
    /* Maybe put ignore proportion in here as a Y/N or boolean */
  }

  /**
   * Generate cloudinary image preview for effect edit form.
   */
  function cloudinaryCropFormPreview() {
    $filename = 'sample.jpg';

    if (isset($this->configuration['gravity'])) {
      switch ($this->configuration['gravity']) {
        case 'face':
        case 'face:center':
        case 'rek_face':
          $filename = 'bike.jpg';
          break;

        case 'faces':
        case 'faces:center':
        case 'rek_faces':
          $filename = 'couple.jpg';
          break;
      }
    }

    if (isset($this->configuration['effect'])) {
      switch ($this->configuration['effect']) {
        case 'redeye':
        case 'rek_redeye':
          $filename = 'itaib_redeye_msjmif.jpg';
          break;

        case 'pixelate_faces':
        case 'blur_faces':
          $filename = 'couple.jpg';
          break;
      }
    }

    $original = CLOUDINARY_PREVIEW_IMAGE_PREFIX . $filename;
    $preview = $original;

    $data = cloudinary_prepare_transformation($this->configuration);
    $trans = \Cloudinary::generate_transformation_string($data);
    if ($trans) {
      $preview = CLOUDINARY_PREVIEW_IMAGE_PREFIX . trim($trans, '/') . '/' . $filename;
    }

//    $styles = array('original' => $original, 'preview' => $preview);
    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
//
//
// @see https://www.drupal.org/node/2195739
    return [
      '#theme' => 'cloudinary_image_style_preview',
      '#original' => $original,
      '#preview' => $preview,
    ];

  }
}
