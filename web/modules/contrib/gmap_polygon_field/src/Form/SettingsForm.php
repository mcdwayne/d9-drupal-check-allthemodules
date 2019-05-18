<?php

/**
 * @file
 * Contains Drupal\gmap_polygon_field\Form\SettingsForm.
 */

namespace Drupal\gmap_polygon_field\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\gmap_polygon_field\Services\ConfigService;

class SettingsForm extends ConfigFormBase {
  /**
   * The config service
   *
   * @var \Drupal\gmap_polygon_field\Services\ConfigService
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigService $config) {
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gmap_polygon_field.config')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'gmap_polygon_field.settings',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gmap_polygon_field_settings_form';
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['gmap_polygon_field_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $this->config->get('gmap_polygon_field_api_key'),
      '#size' => 180,
      '#maxlength' => 256,
      '#description' => $this->t('Enter API key from Google API Console.'),
      '#required' => TRUE,
    );

    $form['gmap_polygon_field_stroke_color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Stroke color'),
      '#default_value' => $this->config->get('gmap_polygon_field_stroke_color'),
      '#size' => 50,
      '#maxlength' => 256,
      '#description' => $this->t('Enter a hexadecimal HTML color of the format, for example \'#ff0000\'. Named colors aren\'t supported. This field supports tokens.'),
      '#required' => TRUE,
      '#element_validate' => array('::validate_color'),
    );

    $form['gmap_polygon_field_stroke_opacity'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Stroke opacity'),
      '#default_value' => $this->config->get('gmap_polygon_field_stroke_opacity'),
      '#size' => 50,
      '#maxlength' => 256,
      '#description' => $this->t('Enter a numerical value between 0.0 and 1.0 to determine the opacity of the line\'s color. The default is 1.0. This field supports tokens.'),
      '#required' => TRUE,
      '#element_validate' => array('::validate_stroke_opacity'),
    );

    $form['gmap_polygon_field_stroke_weight'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Stroke weight'),
      '#default_value' => $this->config->get('gmap_polygon_field_stroke_weight'),
      '#size' => 50,
      '#maxlength' => 256,
      '#description' => $this->t('Enter the width of the line in pixels. This field supports tokens.'),
      '#required' => TRUE,
      '#element_validate' => array('::validate_stroke_weight'),
    );

    $form['gmap_polygon_field_fill_color'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Fill color'),
      '#default_value' => $this->config->get('gmap_polygon_field_fill_color'),
      '#size' => 50,
      '#maxlength' => 256,
      '#description' => $this->t("Enter a hexadecimal HTML color of the format, for example '#000000'. Named colors aren't supported. This field supports tokens."),
      '#required' => FALSE,
      '#element_validate' => array('::validate_color'),
    );

    $form['tokens'] = array(
      '#theme' => 'token_tree_link',
      '#token_types' => array('node'),
      '#global_types' => TRUE,
      '#click_insert' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit function of the configuration form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config
      ->set('gmap_polygon_field_api_key', $form_state->getValue('gmap_polygon_field_api_key'))
      ->set('gmap_polygon_field_stroke_color', $form_state->getValue('gmap_polygon_field_stroke_color'))
      ->set('gmap_polygon_field_stroke_opacity', $form_state->getValue('gmap_polygon_field_stroke_opacity'))
      ->set('gmap_polygon_field_stroke_weight', $form_state->getValue('gmap_polygon_field_stroke_weight'))
      ->set('gmap_polygon_field_fill_color', $form_state->getValue('gmap_polygon_field_fill_color'))
      ->save();
  }

  /**
   * Form element validation handler for stroke and fill color.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validate_color(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    $colorCode = ltrim($value, '#');
    if ($value !== '' && (!ctype_xdigit($colorCode) || !(strlen($colorCode) == 6 || strlen($colorCode) == 3) || $value[0] != '#') && (!preg_match('/^\[(.)+\]$/', $value))) {
      $form_state->setError($element, t('%name must be a hexadecimal HTML color code or token.', array('%name' => $element['#title']->render())));
    }
  }

  /**
   * Form element validation handler for stroke opacity.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validate_stroke_opacity(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || $value > 1 || $value < 0) && (!preg_match('/^\[(.)+\]$/', $value))) {
      $form_state->setError($element, t('%name must be between 0 and 1 or token.', array('%name' => $element['#title']->render())));
    }
  }

  /**
   * Form element validation handler for stroke weight.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   */
  public static function validate_stroke_weight(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    if ($value !== '' && (!is_numeric($value) || $value < 0) && (!preg_match('/^\[(.)+\]$/', $value))) {
      $form_state->setError($element, t('%name must be number greater or equal 0 or it must be token.', array('%name' => $element['#title']->render())));
    }
  }
}
