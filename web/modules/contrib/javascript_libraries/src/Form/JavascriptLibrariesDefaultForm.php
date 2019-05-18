<?php

/**
 * @file
 * Contains \Drupal\javascript_libraries\Form\JavascriptLibrariesDefaultForm.
 */

namespace Drupal\javascript_libraries\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class JavascriptLibrariesDefaultForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'javascript_libraries_default_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['desc'] = array(
      '#type' => 'fieldset',
      '#title' => 'Add core libraries to your site',
    );
    $form['desc']['lib'] = array(
      '#type' => 'textfield',
      '#title' => 'Add library',
      '#description' => t('The libraries have to be specified in core/jquery format'),
    );
    // The submit button.
    $form['desc']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    $form['libraries_listing'] = array(
      '#type' => 'table',
      '#header' => array(t('Library'), t('Weight'), t('Operations')),
      '#empty' => t('There are no items yet. Add an item.', array(//'@add-url' => Url::fromRoute('mymodule.manage_add'),
      )),
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'mytable-order-weight',
        ),
      ),
    );
    $core_lib = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_core_libraries');
    foreach ($core_lib as $key => $library) {
      // TableDrag: Mark the table row as draggable.
      //$form['libraries_listing'][$key]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      //$form['libraries_listing'][$key]['#weight'] = $library;

      // Some table columns containing raw markup.
      $form['libraries_listing'][$key]['lib'] = array(
        '#plain_text' => $library,
      );

      // TableDrag: Weight column element.
      $form['libraries_listing'][$key]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => 5,
        // Classify the weight element for #tabledrag.
        '#attributes' => array('class' => array('mytable-order-weight')),
      );
      // Operations (dropbutton) column.
      $form['libraries_listing'][$key]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
      );
      $form['libraries_listing'][$key]['operations']['#links']['delete'] = array(
        'title' => t('Delete'),
        'url' => Url::fromRoute('javascript_libraries.core_delete_form', array('library' => $key)),
      );
    }

    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if(empty($form_state->getValue('lib'))){
      $form_state->setErrorByName('lib', $this->t("Please enter a valid core library name."));
    }
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $core_lib = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_core_libraries');
    $core_lib[] = $form_state->getValue('lib');
    \Drupal::configFactory()->getEditable('javascript_libraries.settings')
      ->set('javascript_libraries_core_libraries', $core_lib)
      ->save();
    drupal_set_message('Core library dependency added succesfully');
  }

}
