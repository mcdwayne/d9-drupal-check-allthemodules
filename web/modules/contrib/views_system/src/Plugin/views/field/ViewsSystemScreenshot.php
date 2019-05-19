<?php

/**
 * @file
 * Contains \Drupal\views_system\Plugin\views\field\ViewsSystemScreenshot.
 */


namespace Drupal\views_system\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;


/**
 * Field handler to display the thumbnail image of a theme.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("views_system_screenshot")
 */
class ViewsSystemScreenshot extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['image'] = array('default' => TRUE);
    $options['image_width'] = array('default' => '110');
    $options['image_height'] = array('default' => '');

    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['image'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Display as image'),
      '#description' => $this->t('If checked, the screenshot will be displayed as image.'),
      '#default_value' => $this->options['image'],
    );
    $form['image_width'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->options['image_width'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[image]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['image_height'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->options['image_height'],
      '#states' => array(
        'visible' => array(
          ':input[name="options[image]"]' => array('checked' => TRUE),
        ),
      ),
    );
  }

  public function validateOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->getValue(array('options', 'image_width')) != '' && !is_numeric($form_state->getValue(array('options', 'image_width')))) {
      $form_state->setError($form['image_width'], $this->t('You have to enter a numeric value for the image width.'));
    }
    if ($form_state->getValue(array('options', 'image_height')) != '' && !is_numeric($form_state->getValue(array('options', 'image_height')))) {
      $form_state->setError($form['image_height'], $this->t('You have to enter a numeric value for the image height.'));
    }
  }

  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};

    if (!$this->options['image']) {
      return $value;
    }

    if (!empty($value)) {
      $screenshot = array(
        '#theme' => 'image',
        '#uri' => $value,
        '#alt' => t('Screenshot'),
        '#title' => t('Screenshot'),
        '#width' => $this->options['image_width'],
        '#height' => $this->options['image_height'],
        '#attributes' => array('class' => array('screenshot')),
      );
    }
    else {
      $screenshot = array(
        '#theme' => 'image',
        '#uri' => drupal_get_path('module', 'system') . '/images/no_screenshot.png',
        '#alt' => t('No screenshot'),
        '#title' => t('No screenshot'),
        '#width' => $this->options['image_width'],
        '#height' => $this->options['image_height'],
        '#attributes' => array('class' => array('no-screenshot')),
      );
    }

    return \Drupal::service('renderer')->render($screenshot);
  }
}
