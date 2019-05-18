<?php

/**
 * @file
 * Contains \Drupal\javascript_libraries\Form\JavascriptLibrariesCustomForm.
 */

namespace Drupal\javascript_libraries\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class JavascriptLibrariesCustomForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'javascript_libraries_custom_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $custom = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_custom_libraries');
    $form['addlink'] = array(
      '#title' => $this->t('Add Javascript'),
      '#type' => 'link',
      '#url' => Url::fromRoute('javascript_libraries.custom_add_form'),
      '#attributes' => array(
        'class' => array(
          'button',
          'button-action',
          'button--primary',
          'button--small'
        )
      ),
    );
    $url = Url::fromRoute('javascript_libraries.custom_add_form');
    $form['details'] = array(
      '#type' => 'html_tag',
      '#tag' => 'div  ',
      '#value' => $this->t('To load the JavaScript library on every page load, move it to the head or footer region. Not applicable to administrative pages.'),
    );
    $form['libraries_listing'] = array(
      '#type' => 'table',
      '#header' => array(
        t('Description'),
        t('Region'),
        t('Source'),
        t('Weight'),
        t('Operations')
      ),
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
    foreach ($custom as $key => $library) {
      // TableDrag: Mark the table row as draggable.
      $form['libraries_listing'][$key]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      $form['libraries_listing'][$key]['#weight'] = $library['weight'];

      // Some table columns containing raw markup.
      $form['libraries_listing'][$key]['name'] = array(
        '#plain_text' => $library['name'],
      );
      $form['libraries_listing'][$key]['region'] = array(
        '#plain_text' => $library['scope'],
      );
      $form['libraries_listing'][$key]['source'] = array(
        '#plain_text' => $library['uri'],
      );
      // TableDrag: Weight column element.
      $form['libraries_listing'][$key]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $library['name'])),
        '#title_display' => 'invisible',
        '#default_value' => $library['weight'],
        // Classify the weight element for #tabledrag.
        '#attributes' => array('class' => array('mytable-order-weight')),
      );
      // Operations (dropbutton) column.
      $form['libraries_listing'][$key]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
      );
      $form['libraries_listing'][$key]['operations']['#links']['edit'] = array(
        'title' => t('Edit'),
        'url' => Url::fromRoute('javascript_libraries.edit_form', array('library' => $key)),
      );
      $form['libraries_listing'][$key]['operations']['#links']['delete'] = array(
        'title' => t('Delete'),
        'url' => Url::fromRoute('javascript_libraries.delete_form', array('library' => $key)),
      );
    }
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $custom = \Drupal::config('javascript_libraries.settings')
      ->get('javascript_libraries_custom_libraries');

    foreach ($form_state->getValue(['libraries']) as $key => $library) {
      $custom[$key]['scope'] = $library['scope'];
      $custom[$key]['weight'] = $library['weight'];
    }
    \Drupal::configFactory()
      ->getEditable('javascript_libraries.settings')
      ->set('javascript_libraries_custom_libraries', $custom)
      ->save();
    drupal_set_message(t('The JavaScript library settings have been updated.'));
  }

}
