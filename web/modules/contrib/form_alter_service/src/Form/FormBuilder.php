<?php

namespace Drupal\form_alter_service\Form;

use Drupal\Core\Form\FormBuilder as FormBuilderBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Override of the form builder in order to provide OO form alters.
 */
class FormBuilder extends FormBuilderBase implements FormBuilderAlterInterface {

  /**
   * An instance of the form alter service.
   *
   * @var \Drupal\form_alter_service\Form\FormAlter
   */
  private $formAlter;

  /**
   * {@inheritdoc}
   */
  public function setFormAlter(FormAlter $form_alter) {
    $this->formAlter = $form_alter;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareForm($form_id, &$form, FormStateInterface &$form_state) {
    parent::prepareForm($form_id, $form, $form_state);

    $build_info = $form_state->getBuildInfo();
    $arguments = empty($build_info['args']) ? [] : $build_info['args'];
    $alters = $this->formAlter->getServices($form_id);
    $alters += $this->formAlter->getServices('match');

    if (isset($build_info['base_form_id'])) {
      $alters += $this->formAlter->getServices($build_info['base_form_id']);
    }

    foreach ($alters as $alter) {
      if ($alter->hasMatch($form, $form_state, $form_id)) {
        // Expand the arguments as they passing to the "buildForm()" method.
        $alter->alterForm($form, $form_state, ...$arguments);

        foreach ($alter->getHandlers() as $type => $handlers) {
          // Make sure array initialized to prevent cases like "array_unshift()
          // expects parameter 1 to be array, null given".
          $form += [$type => []];

          if (isset($handlers['prepend'])) {
            foreach ($handlers['prepend'] as list($priority, $handler)) {
              array_unshift($form[$type], [$alter, $handler]);
            }
          }

          if (isset($handlers['append'])) {
            foreach ($handlers['append'] as list($priority, $handler)) {
              $form[$type][] = [$alter, $handler];
            }
          }
        }
      }
    }
  }

}
