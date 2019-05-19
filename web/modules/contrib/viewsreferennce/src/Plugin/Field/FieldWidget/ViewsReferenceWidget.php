<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "views_reference_autocomplete",
 *   label = @Translation("Views Reference Autocomplete"),
 *   description = @Translation("An autocomplete views reference field."),
 *   field_types = {
 *     "viewsreference"
 *   }
 * )
 */
class ViewsReferenceWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $field_name = $items->getName();
    $name = $field_name . '[' . $delta . '][target_id]';

    $element['target_id']['#target_type'] = 'view';

    $element['target_id']['#ajax'] = array(
      'callback' => array($this, 'getDisplayIds'),
      'event' => 'viewsreference-select',
      'progress' => array(
        'type' => 'throbber',
        'message' => t('Getting display Ids...'),
      ),
    );

    $options = $this->getAllViewsDisplayIds();
    $default_value = isset($items[$delta]->getValue()['display_id']) ? $items[$delta]->getValue()['display_id'] : '';

    $element['display_id'] = array(
      '#title' => 'Display Id',
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value,
      '#states' => array(
        'visible' => array(
          ':input[name="' . $name . '"]' => array('filled' => TRUE),
        ),
      ),
    );

    $element['#attached']['library'][] = 'viewsreference/viewsreference';

    return $element;
  }

  /**
   *  AJAX function to get display IDs for a particular View
   */
  public function getDisplayIds(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $delta = $trigger['#delta'];
    $id = $trigger['#id'];
    $field_name = $trigger['#parents'][0];
    $values = $form_state->getValues();
    $entity_id = $values[$field_name][$delta]['target_id'];
    $options = $this->getViewDisplayIds($entity_id);

    $element_id = '#edit-' . str_replace('_', '-', $field_name) . '-' . $delta . '-display-id';

    // Drupal adds a div when sending ungrouped elements to the DOM, so we group using optgroup
    $html = '<optgroup>';
    foreach ($options as $key => $option) {
      $html .= '<option value="' . $key . '">' . $option . '</option>';
    }
    $html .= '</optgroup>';
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand($element_id, render($html)));
    return $response;
  }

  /**
   * Helper function to get all display ids
   */
  protected function getAllViewsDisplayIds() {
    $views =  \Drupal\views\Views::getAllViews();
    $options = array();
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $options[$display['id']] = $display['display_title'];
      }
    }
    return $options;
  }

  /**
   * Helper to get display ids for a particular View
   */
  protected function getViewDisplayIds($entity_id) {
    $views =  \Drupal\views\Views::getAllViews();
    $options = array();
    foreach ($views as $view) {
      if ($view->get('id') == $entity_id) {
        foreach ($view->get('display') as $display) {
          $options[$display['id']] = $display['display_title'];
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['display_id']) ? $element['display_id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return parent::massageFormValues($values, $form, $form_state);
  }


}
