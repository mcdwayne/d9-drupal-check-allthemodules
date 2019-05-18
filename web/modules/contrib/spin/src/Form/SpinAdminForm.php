<?php

namespace Drupal\spin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spin\SpinHelper;
use Drupal\spin\SpinStorage;

/**
 * Spin admin form.
 */
class SpinAdminForm extends FormBase {
  const AUTOSPIN_INC = 5000;
  const AUTOSPIN_MAX = 70000;
  const AUTOSPIN_MIN = 5000;
  const EFFECT_INC = 50;
  const EFFECT_MAX = 1000;
  const EFFECT_MIN = 200;

  /**
   * The spin profile admin form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   * @param string $op
   *   The operation.
   * @param string $type
   *   The type of profile.
   * @param int $sid
   *   The spin ID.
   *
   * @return array
   *   A Drupal form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $op = 'add', $type = 'spin', $sid = 0) {
    $header = '<h2 style="background-color:#eeeeee; border:1px #aaaaaa solid; margin:1.0rem auto 0.1rem 0; padding:0.5rem; width:50%">@txt</h2>';
    $opts = [
      'true'  => $this->t('Yes'),
      'false' => $this->t('No'),
    ];
    $form['sid'] = [
      '#type'  => 'value',
      '#value' => $sid ? $sid : '',
    ];
    $form['label'] = [
      '#title'         => $this->t('Label'),
      '#type'          => 'textfield',
      '#default_value' => $sid ? SpinStorage::getLabel($sid) : '',
      '#size'          => 35,
      '#maxlength'     => 32,
      '#required'      => TRUE,
    ];
    $form['name'] = [
      '#title'         => $this->t('Machine Name'),
      '#type'          => 'machine_name',
      '#description'   => $this->t('Lowercase letters and underscore, (_) only.'),
      '#default_value' => $sid ? SpinStorage::getName($sid) : '',
      '#size'          => 35,
      '#maxlength'     => 32,
      '#required'      => TRUE,
      '#disabled'      => (bool) $sid,
      '#machine_name'  => [
        'replace_pattern' => '[^a-z0-9_]+',
        'replace'         => '_',
        'source'          => ['label'],
      ],
    ];
    switch ($type) {
      case 'slideshow':
        $this->addSlideshowElements($form, $header, $opts);
        break;

      case 'spin':
        $this->addSpinElements($form, $header, $opts);
        break;

      default:
        return [];
    }
    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * The form ID.
   *
   * @return string
   *   The form ID.
   */
  public function getFormId() {
    return 'spin_admin';
  }

  /**
   * AJAX callback for the indexing form.
   *
   * @param array $form
   *   A drupal form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = SpinHelper::extractOptions($form_state->getValues());
    SpinHelper::mergeSpin($form_state->getValues(), $options);
    drupal_flush_all_caches();
  }

  /**
   * Validation function for the suggestion edit form.
   *
   * @param array $form
   *   A drupal form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A Drupal FormStateInterface object.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!preg_match('/^[a-zA-z][\w -]*[a-zA-z0-9]$/', $form_state->getValue('label'))) {
      $form_state->setErrorByName('label', $this->t('Illegal character in label.'));
    }
    if (!$form_state->getValue('sid') && SpinStorage::nameExists($form_state->getValue('name'), $form_state->getValue('type'))) {
      $form_state->setErrorByName('name', $this->t('The machine name already exists.'));
    }
  }

  /**
   * Add the slideshow elements to the form.
   *
   * @param array $form
   *   A drupal form array.
   * @param string $header
   *   A section header HTML string.
   * @param array $opts
   *   An array of common options.
   */
  protected function addSlideshowElements(array &$form, $header, array $opts) {
    $data = !empty($form['sid']['#value']) ? SpinHelper::getData($form['sid']['#value'], 'slideshow') : SpinHelper::getDefaultData('slideshow');

    $form['name']['#machine_name']['exists'] = '\Drupal\spin\Form\SpinAdminForm::slideshowNameExists';

    $form['type'] = [
      '#type'  => 'value',
      '#value' => 'slideshow',
    ];
    $form['head_common'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Common'), $header),
    ];
    $form['spin_orientation'] = [
      '#title'         => $this->t('Orientation'),
      '#description'   => $this->t('Direction for slides (left/right or up/down).'),
      '#type'          => 'select',
      '#default_value' => isset($data['orientation']) ? $data['orientation'] : 'horizontal',
      '#options'       => [
        'horizontal' => $this->t('Horizontal'),
        'vertical'   => $this->t('Vertical'),
      ],
    ];
    $form['spin_arrows'] = [
      '#title'         => $this->t('Arrows'),
      '#description'   => $this->t('Show navigation arrows on hover.'),
      '#type'          => 'select',
      '#default_value' => isset($data['arrows']) ? $data['arrows'] : 'true',
      '#options'       => $opts,
    ];
    $form['spin_loop'] = [
      '#title'         => $this->t('Loop'),
      '#description'   => $this->t('Continue slideshow after last slide.'),
      '#type'          => 'select',
      '#default_value' => isset($data['loop']) ? $data['loop'] : 'false',
      '#options'       => $opts,
    ];
    $form['spin_effect'] = [
      '#title'         => $this->t('Effect'),
      '#description'   => $this->t('Slide transition effect.'),
      '#type'          => 'select',
      '#default_value' => isset($data['effect']) ? $data['effect'] : 'slide',
      '#options'       => [
        'bars3d'       => $this->t('Bars3d'),
        'blinds3d'     => $this->t('Blinds3d'),
        'blocks'       => $this->t('Blocks'),
        'cube'         => $this->t('Cube'),
        'diffusion'    => $this->t('Diffusion'),
        'dissolve'     => $this->t('Dissolve'),
        'fade'         => $this->t('Fade'),
        'fade-down'    => $this->t('Down'),
        'fade-up'      => $this->t('Up'),
        'flip'         => $this->t('Flip'),
        'random'       => $this->t('Random'),
        'slide'        => $this->t('Slide'),
        'slide-change' => $this->t('Change'),
        'slide-in'     => $this->t('In'),
        'slide-out'    => $this->t('Out'),
      ],
    ];
    $form['spin_effect-speed'] = [
      '#title'         => $this->t('Effect Speed'),
      '#description'   => $this->t('Speed of slide transition effect.'),
      '#type'          => 'select',
      '#default_value' => isset($data['effect-speed']) ? $data['effect-speed'] : 600,
      '#options'       => array_combine(range(self::EFFECT_MIN, self::EFFECT_MAX, self::EFFECT_INC), range(self::EFFECT_MIN, self::EFFECT_MAX, self::EFFECT_INC)),
    ];
    $form['head_autoplay'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Autoplay'), $header),
    ];
    $form['spin_autoplay'] = [
      '#title'         => $this->t('Autoplay'),
      '#description'   => $this->t('Autoplay slideshow after initialization.'),
      '#type'          => 'select',
      '#default_value' => isset($data['autoplay']) ? $data['autoplay'] : 'false',
      '#options'       => $opts,
    ];
    $form['spin_shuffle'] = [
      '#title'         => $this->t('Shuffle'),
      '#description'   => $this->t('Shuffle slide order.'),
      '#type'          => 'select',
      '#default_value' => isset($data['shuffle']) ? $data['shuffle'] : 'false',
      '#options'       => $opts,
    ];
    $form['spin_pause'] = [
      '#title'         => $this->t('Pause'),
      '#description'   => $this->t('Click to pause slideshow.'),
      '#type'          => 'select',
      '#default_value' => isset($data['pause']) ? $data['pause'] : 'false',
      '#options'       => $opts,
    ];
    $form['head_selectors'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Selectors'), $header),
    ];
    $form['spin_selectors'] = [
      '#title'         => $this->t('Selectors'),
      '#description'   => $this->t('Where to show selectors.'),
      '#type'          => 'select',
      '#default_value' => isset($data['selectors']) ? $data['selectors'] : 'bottom',
      '#options'       => [
        'left'   => $this->t('Left'),
        'top'    => $this->t('Top'),
        'right'  => $this->t('Right'),
        'bottom' => $this->t('Bottom'),
        'none'   => $this->t('None'),
      ],
    ];
    $form['spin_selectors-style'] = [
      '#title'         => $this->t('Selectors Style'),
      '#description'   => $this->t('Style of selectors.'),
      '#type'          => 'select',
      '#default_value' => isset($data['selectors-style']) ? $data['selectors-style'] : 'thumbnails',
      '#options'       => [
        'bullets'    => $this->t('Bullets'),
        'thumbnails' => $this->t('Thumbnails'),
      ],
    ];
    $form['spin_selectors-eye'] = [
      '#title'         => $this->t('Selectors Eye'),
      '#description'   => $this->t('Highlighter for active thumbnail.'),
      '#type'          => 'select',
      '#default_value' => isset($data['selectors-eye']) ? $data['selectors-eye'] : 'false',
      '#options'       => $opts,
    ];
    $form['head_caption'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Caption'), $header),
    ];
    $form['spin_caption'] = [
      '#title'         => $this->t('Caption'),
      '#description'   => $this->t('Display text on a slide.'),
      '#type'          => 'select',
      '#default_value' => isset($data['caption']) ? $data['caption'] : 'false',
      '#options'       => $opts,
    ];
    $form['spin_caption-effect'] = [
      '#title'         => $this->t('Caption Effect'),
      '#description'   => $this->t('Caption change with effect.'),
      '#type'          => 'select',
      '#default_value' => isset($data['caption-effect']) ? $data['caption-effect'] : 'fade',
      '#options'       => [
        'fade'     => $this->t('Fade'),
        'dissolve' => $this->t('Dissolve'),
        'fixed'    => $this->t('Fixed'),
      ],
    ];
    $form['head_other'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Other'), $header),
    ];
    $form['spin_fullscreen'] = [
      '#title'         => $this->t('Fullscreen'),
      '#description'   => $this->t('Full screen.'),
      '#type'          => 'select',
      '#default_value' => isset($data['fullscreen']) ? $data['fullscreen'] : 'true',
      '#options'       => $opts,
    ];
    $form['spin_keyboard'] = [
      '#title'         => $this->t('Keyboard'),
      '#description'   => $this->t('Keyboard navigation.'),
      '#type'          => 'select',
      '#default_value' => isset($data['keyboard']) ? $data['keyboard'] : 'false',
      '#options'       => $opts,
    ];
    $form['spin_preload'] = [
      '#title'         => $this->t('Preload'),
      '#description'   => $this->t('Load images on demand.'),
      '#type'          => 'select',
      '#default_value' => isset($data['preload']) ? $data['preload'] : 'true',
      '#options'       => $opts,
    ];
    $form['spin_loader'] = [
      '#title'         => $this->t('Loader'),
      '#description'   => $this->t('Show loading icon.'),
      '#type'          => 'select',
      '#default_value' => isset($data['loader']) ? $data['loader'] : 'true',
      '#options'       => $opts,
    ];
    $form['spin_links'] = [
      '#title'         => $this->t('Links'),
      '#description'   => $this->t('Open links associated with image slides.'),
      '#type'          => 'select',
      '#default_value' => isset($data['links']) ? $data['links'] : '',
      '#options'       => [
        'false'  => $this->t('No'),
        '_self'  => $this->t('In the same page'),
        '_blank' => $this->t('In a new tab/window'),
      ],
    ];
  }

  /**
   * Add the spin elements to the form.
   *
   * @param array $form
   *   A drupal form array.
   * @param string $header
   *   A section header HTML string.
   * @param array $opts
   *   An array of common options.
   */
  protected function addSpinElements(array &$form, $header, array $opts) {
    $data = !empty($form['sid']['#value']) ? SpinHelper::getData($form['sid']['#value'], 'spin') : SpinHelper::getDefaultData('spin');

    $form['name']['#machine_name']['exists'] = '\Drupal\spin\Form\SpinAdminForm::spinNameExists';

    $form['type'] = [
      '#type'  => 'value',
      '#value' => 'spin',
    ];
    $form['head_autospin'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Autospin'), $header),
    ];
    $form['spin_autospin'] = [
      '#title'         => $this->t('Autospin'),
      '#description'   => $this->t('Automatically spin the image.'),
      '#type'          => 'select',
      '#default_value' => isset($data['autospin']) ? $data['autospin'] : 'once',
      '#options'       => [
        'off'      => $this->t('Off'),
        'once'     => $this->t('Once'),
        'twice'    => $this->t('Twice'),
        'infinite' => $this->t('infinite'),
      ],
    ];
    $form['spin_autospinSpeed'] = [
      '#title'         => $this->t('Autospin Speed'),
      '#description'   => $this->t('The time (ms) taken to complete 1 rotation.'),
      '#type'          => 'select',
      '#default_value' => isset($data['autospinSpeed']) ? $data['autospinSpeed'] : 3500,
      '#options'       => array_combine(range(self::AUTOSPIN_MIN, self::AUTOSPIN_MAX, self::AUTOSPIN_INC), range(self::AUTOSPIN_MIN, self::AUTOSPIN_MAX, self::AUTOSPIN_INC)),
    ];
    $form['spin_autospinStart'] = [
      '#title'         => $this->t('Autospin Start'),
      '#description'   => $this->t('Start autospin on page load, click or hover.'),
      '#type'          => 'select',
      '#default_value' => isset($data['autospinStart']) ? $data['autospinStart'] : 'load',
      '#options'       => [
        'click' => $this->t('Click'),
        'hover' => $this->t('Hover'),
        'load'  => $this->t('Load'),
      ],
    ];
    $form['spin_autospinStop'] = [
      '#title'         => $this->t('Autospin Stop'),
      '#description'   => $this->t('Stop autospin on click, hover or never.'),
      '#type'          => 'select',
      '#default_value' => isset($data['autospinStop']) ? $data['autospinStop'] : 'click',
      '#options'       => [
        'click' => $this->t('Click'),
        'hover' => $this->t('Hover'),
        'never' => $this->t('Never'),
      ],
    ];
    $form['spin_autospinDirection'] = [
      '#title'         => $this->t('Autospin Direction'),
      '#description'   => $this->t('Direction of spin.'),
      '#type'          => 'select',
      '#default_value' => isset($data['autospinDirection']) ? $data['autospinDirection'] : 'clockwise',
      '#options'       => [
        'clockwise'     => $this->t('Clockwise'),
        'anticlockwise' => $this->t('Anticlockwise'),
      ],
    ];
    $form['head_mouse'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Mouse'), $header),
    ];
    $form['spin_spin'] = [
      '#title'         => $this->t('Spin Method'),
      '#description'   => $this->t('Method for spinning the image.'),
      '#type'          => 'select',
      '#default_value' => isset($data['spin']) ? $data['spin'] : 'drag',
      '#options'       => [
        'drag'  => $this->t('Drag'),
        'hover' => $this->t('Hover'),
      ],
    ];
    $form['spin_speed'] = [
      '#title'         => $this->t('Drag Speed'),
      '#description'   => $this->t('Speed of spin while dragging (100 = fast).'),
      '#type'          => 'select',
      '#default_value' => isset($data['speed']) ? $data['speed'] : 50,
      '#options'       => array_combine(range(0, 100, 5), range(0, 100, 5)),
    ];
    $form['spin_zoom'] = [
      '#title'         => $this->t('Zoom'),
      '#description'   => $this->t('Zoom level on click (0 = disabled).'),
      '#type'          => 'select',
      '#default_value' => isset($data['zoom']) ? $data['zoom'] : 3,
      '#options'       => array_combine(range(0, 10), range(0, 10)),
    ];
    $form['spin_rightClick'] = [
      '#title'         => $this->t('Right Click'),
      '#description'   => $this->t('Show right-click menu on the image.'),
      '#type'          => 'select',
      '#default_value' => isset($data['rightClick']) ? $data['rightClick'] : 'false',
      '#options'       => $opts,
    ];
    $form['head_misc'] = [
      '#type'   => 'markup',
      '#markup' => str_replace('@txt', $this->t('Misc'), $header),
    ];
    $form['spin_fullscreen'] = [
      '#title'         => $this->t('Fullscreen'),
      '#description'   => $this->t('Enable full-screen spin if large images exist.'),
      '#type'          => 'select',
      '#default_value' => isset($data['fullscreen']) ? $data['fullscreen'] : 'true',
      '#options'       => $opts,
    ];
    $form['spin_initializeOn'] = [
      '#title'         => $this->t('Image Download'),
      '#description'   => $this->t('When to download the images.'),
      '#type'          => 'select',
      '#default_value' => isset($data['initializeOn']) ? $data['initializeOn'] : 'load',
      '#options'       => [
        'click' => $this->t('Click'),
        'hover' => $this->t('Hover'),
        'load'  => $this->t('Load'),
      ],
    ];
  }

  /**
   * Determine if the slideshow profile name exists.
   *
   * @param string $name
   *   The profile name.
   *
   * @return bool
   *   True if the name exists.
   */
  protected static function slideshowNameExists($name) {
    return SpinStorage::nameExists($name, 'slideshow');
  }

  /**
   * Determine if the spin profile name exists.
   *
   * @param string $name
   *   The profile name.
   *
   * @return bool
   *   True if the name exists.
   */
  protected static function spinNameExists($name) {
    return SpinStorage::nameExists($name, 'spin');
  }

}
