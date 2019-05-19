<?php

namespace Drupal\text2image\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a form that configures text2image module settings.
 */
class Text2ImageConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'text2image.settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'text2image.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('text2image.settings');
    $form['font_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to TrueType fonts'),
      '#default_value' => $config->get('font_path'),
      '#required' => TRUE,
      '#element_validate' => [[$this, 'validatePath']],
    ];
    $fonts_list = text2image_get_selected_fonts();
    if (empty($fonts_list)) {
      drupal_set_message('No fonts available. Use the fonts page to scan for fonts.', 'warning');
    }
    $description_link = Link::fromTextAndUrl(
        $this->t('Configure Fonts'), Url::fromRoute('text2image.config_fonts')
    );
    $form['font_file'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Font'),
      '#options' => $fonts_list,
      '#limit_validation_errors' => FALSE,
      '#default_value' => $config->get('font_file'),
      '#required' => FALSE,
      '#element_validate' => [[$this, 'validateFont']],
      '#description' => $description_link->toRenderable(),
    ];
    $form['font_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default font size'),
      '#default_value' => $config->get('font_size'),
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default original width'),
      '#default_value' => $config->get('width'),
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default original height'),
      '#default_value' => $config->get('height'),
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default background color'),
      '#default_value' => $config->get('bg_color'),
      '#size' => 8,
      '#maxlength' => 7,
      '#required' => FALSE,
      '#description' => 'Hex code e.g. #000000, leave empty to generate random color',
    ];
    $form['fg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default text color'),
      '#default_value' => $config->get('fg_color'),
      '#size' => 8,
      '#maxlength' => 7,
      '#required' => FALSE,
      '#description' => 'Hex code e.g. #000000, leave empty to generate random color',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate value of element: directory exists.
   *
   * @param array $element
   *   Element array.
   * @param \Drupal\Core\Form\FormStateInterface\FormStateInterface $form_state
   *   Form state object.
   * @param array $form
   *   Form array.
   */
  public function validatePath(array $element, FormStateInterface $form_state, array $form) {
    if (!is_dir($element['#value'])) {
      $form_state->setErrorByName(implode('][', $element['#parents']), $this->t('Invalid font path.'));
    }
  }

  /**
   * Validate value of element: font file exists.
   *
   * @param array $element
   *   Element array.
   * @param \Drupal\Core\Form\FormStateInterface\FormStateInterface $form_state
   *   Form state object.
   * @param array $form
   *   Form array.
   */
  public function validateFont(array $element, FormStateInterface $form_state, array $form) {
    if (!file_exists($element['#value'])) {
      $form_state->setErrorByName(implode('][', $element['#parents']), $this->t('Invalid font file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('text2image.settings');
    $config->set('font_path', $values['font_path']);
    $config->set('font_file', $values['font_file']);
    $config->set('font_size', $values['font_size']);
    $config->set('width', $values['width']);
    $config->set('height', $values['height']);
    $config->set('fg_color', $values['fg_color']);
    $config->set('bg_color', $values['bg_color']);
    $config->save();
  }

}
