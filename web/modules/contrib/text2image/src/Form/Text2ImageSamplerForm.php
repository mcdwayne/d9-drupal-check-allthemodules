<?php

namespace Drupal\text2image\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a form that enables generation of sample image.
 */
class Text2ImageSamplerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'text2image.sampler';
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
    if ($values = $config->get('sample')) {
      $values = unserialize($values);
    }
    else {
      $values = $config->getRawData();
      $values['sample_text'] = 'Sample text';
      $values['image_style'] = '';
      $values['sample_image'] = '';
    }
    $form['sample_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sample text'),
      '#default_value' => $values['sample_text'],
      '#required' => TRUE,
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
      '#title' => $this->t('Sample Font'),
      '#options' => $fonts_list,
      '#limit_validation_errors' => FALSE,
      '#default_value' => $values['font_file'],
      '#required' => FALSE,
      '#element_validate' => [[$this, 'validateFont']],
      '#description' => $description_link->toRenderable(),
    ];
    $form['font_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sample font size'),
      '#default_value' => $values['font_size'],
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sample width'),
      '#default_value' => $values['width'],
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sample height'),
      '#default_value' => $values['height'],
      '#size' => 4,
      '#required' => TRUE,
    ];
    $form['bg_color'] = [
      '#type' => 'color',
      '#title'   => $this->t('Sample background color'),
      '#default_value' => $values['bg_color'],
      '#field_suffix' => $values['bg_color'],
      '#maxlength' => 7,
      '#size' => 7,
      '#required' => FALSE,
    ];
    $form['fg_color'] = [
      '#type' => 'color',
      '#title'   => $this->t('Sample text color'),
      '#default_value' => $values['fg_color'],
      '#field_suffix' => $values['fg_color'],
      '#maxlength' => 7,
      '#size' => 7,
      '#required' => FALSE,
    ];
    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
        $this->t('Configure Image Styles'), Url::fromRoute('entity.image_style.collection')
    );
    $form['image_style'] = [
      '#title' => t('Sample image style'),
      '#type' => 'select',
      '#default_value' => $values['image_style'],
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable(),
    ];
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Generate sample');
    $form['sample_image'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $values['sample_image'] . '</p>',
    ];
    return $form;
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
    $values['sample_image'] = $this->generateSample($values);
    $config = $this->config('text2image.settings');
    $config->set('sample', serialize($values));
    $config->save();
  }

  /**
   * Generate a sample image.
   *
   * @param array $settings
   *   Array of image configuration values.
   *
   * @return string
   *   Return markup string.
   */
  public function generateSample(array $settings) {
    $this->generator = \Drupal::service('text2image.generator')->setImagePath('public://text2image/samples/')->init($settings);
    $image = $this->generator->getImage($settings['sample_text'], TRUE);
    ImageStyle::load($settings['image_style'])->flush($image->uri);
    $item = [
      '#theme' => 'image_formatter',
      '#item' => $image,
      '#image_style' => $settings['image_style'],
    ];
    $renderer = \Drupal::service('renderer');
    return $renderer->render($item);
  }

}
