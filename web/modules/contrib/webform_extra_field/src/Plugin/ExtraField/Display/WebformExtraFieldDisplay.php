<?php

namespace Drupal\webform_extra_field\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field_plus\Plugin\ExtraFieldPlusDisplayBase;
use Drupal\webform\Entity\Webform;

/**
 * Class WebformExtraFieldDisplay.
 *
 * @ExtraFieldDisplay(
 *   id = "webform_extra_field_display",
 *   label = @Translation("Webform"),
 *   visible = false
 * )
 */
class WebformExtraFieldDisplay extends ExtraFieldPlusDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $webform_id = $this->getSetting('webform_id');
    $default_values = $this->defaultFormValues();
    if ($webform_id != $default_values['webform_id']) {
      $webform = Webform::load($webform_id);
      if ($webform) {
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform');
        return $view_builder->view($webform);
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $form = parent::settingsForm();

    $options = $this->getOptions();

    $form['webform_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform'),
      '#description' => $this->t('Select the webform.'),
      '#options' => $options,
      '#empty_option' => t('- None -'),
      '#empty_value' => '_none',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFormValues() {
    $values = parent::defaultFormValues();

    $values += [
      'webform_id' => '_none',
    ];

    return $values;
  }

  /**
   * Helper function to retrieve the webform list option set.
   *
   * @return array
   *   The webform array option list.
   */
  protected function getOptions() {
    $options = [];
    $webforms = Webform::loadMultiple();
    foreach ($webforms as $webform) {
      $options[$webform->id()] = $webform->label();
    }
    return $options;
  }

}
