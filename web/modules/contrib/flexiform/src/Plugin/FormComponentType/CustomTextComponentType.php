<?php

namespace Drupal\flexiform\Plugin\FormComponentType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\flexiform\FormComponent\FormComponentTypeCreateableBase;

/**
 * Plugin for field widget component types.
 *
 * @FormComponentType(
 *   id = "custom_text",
 *   label = @Translation("Custom Text"),
 *   component_class = "Drupal\flexiform\Plugin\FormComponentType\CustomTextComponent",
 * )
 */
class CustomTextComponentType extends FormComponentTypeCreateableBase {

  /**
   * {@inheritdoc}
   */
  public function componentRows(EntityDisplayFormBase $form_object, array $form, FormStateInterface $form_state) {
    $rows = [];
    foreach ($this->getFormDisplay()->getComponents() as $component_name => $options) {
      if (isset($options['component_type']) && $options['component_type'] == $this->getPluginId()) {
        $rows[$component_name] = $this->buildComponentRow($form_object, $component_name, $form, $form_state);
      }
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function addComponentForm(array $form, FormStateInterface $form_state) {
    $form['content'] = [
      '#title' => t('Content'),
      '#type' => 'text_format',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addComponentFormSubmit(array $form, FormStateInterface $form_state) {
    $options = $form_state->getValue($form['#parents']);
    $options['format'] = $options['content']['format'];
    $options['content'] = $options['content']['value'];
    $form_state->setValue($form['#parents'], $options);
  }

}
