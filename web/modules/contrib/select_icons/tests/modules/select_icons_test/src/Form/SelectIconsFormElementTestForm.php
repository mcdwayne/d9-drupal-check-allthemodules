<?php

namespace Drupal\select_icons_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'select_icons' element.
 */
class SelectIconsFormElementTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'select_icons_form_element_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select_icons_select'] = [
      '#type' => 'select_icons',
      '#attributes' => [ 'id' => 'select-icons-test-element' ],
      '#title' => t('Color'),
      '#options' => [ 'r' => t('Red'), 'g' => t('Green'), 'b' => t('Blue') ],
      '#options_attributes' => [
        'r' => new Attribute([ 'data-class' => ['colour', 'red'] ]),
        'g' => new Attribute([ 'data-class' => ['colour', 'green'] ]),
        'b' => new Attribute([ 'data-class' => ['colour', 'blue'] ]),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
