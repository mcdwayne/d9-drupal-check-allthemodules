<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\WidgetOnDemandTrait.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trait for the common behaviour of the on demand widgets.
 */
trait WidgetOnDemandTrait {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = parent::defaultSettings();
    $default_settings['widget_on_demand']['empty_element_on_form_init'] = TRUE;
    $default_settings['widget_on_demand']['empty_element_on_form_init_placeholder'] = t('New element - click to edit.');
    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['widget_on_demand'] = [
      '#type' => 'details',
      '#title' => $this->t('Widget On Demand Settings'),
      '#description' => $this->t('Settings for the preview of the elements yet not replaced by the real form elements.'),
      '#tree' => TRUE,
    ];

    $element['widget_on_demand']['empty_element_on_form_init'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Empty elements on form initialization - on demand'),
      '#default_value' => $this->getSetting('widget_on_demand')['empty_element_on_form_init'],
      '#description' => $this->t('If checked empty elements on form initialization will be loaded as well on demand.'),
    ];

    $input_path = 'fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][widget_on_demand][empty_element_on_form_init]';

    $element['widget_on_demand']['empty_element_on_form_init_placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder for empty elements on form initialization - on demand'),
      '#default_value' => $this->getSetting('widget_on_demand')['empty_element_on_form_init_placeholder'],
      '#description' => $this->t('Text that will be shown for empty elements which have to be loaded on demand.'),
      '#states' => [
        'visible' => [
          ':input[name="' . $input_path . '"]' => ['checked' => TRUE],
        ],
        'invisible' => [
          ':input[name="' . $input_path . '"]' => ['checked' => FALSE],
        ],
      ]
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Use a separate storage for the widget on demand data.
    $wod_storage = &$form_state->get('widget_on_demand_storage');
    if (is_null($wod_storage)) {
      $form_state->set('widget_on_demand_storage', []);
      $wod_storage = &$form_state->get('widget_on_demand_storage');
    }

    $wod_key = array_merge($element['#field_parents'], [$items->getName(), $delta]);
    $key_exists = NestedArray::keyExists($wod_storage, $wod_key);

    if ($key_exists || $form_state->get('new_item_' . $delta) || ($items[$delta]->isEmpty() && !$this->getSetting('widget_on_demand')['empty_element_on_form_init'])) {
      // Ensure the form state will contain also the element ids for new
      // elements in order for the form to be rebuild properly only based on
      // the form state, otherwise new elements will be not interpreted here as
      // new.
      if (!$key_exists) {
        NestedArray::setValue($wod_storage, $wod_key, TRUE);
      }
      $element = parent::formElement($items, $delta, $element, $form, $form_state);
    }
    else {
      // Using the field parents as part of the unique id so that in cases
      // where the same field is being shown twice in the form it can be
      // located uniquely.
      $element_id = implode('-', $element['#field_parents']) . "-form-element-on-demand-" . $items->getName() . '-' . $delta;
      // Strip the leading - if there were no field parents defined.
      $element_id = ltrim($element_id, '-');

      // Keep the original element as provided by ::formSingleElement.
      $element = array_merge($element, [
        '#theme' => 'widget_on_demand_form_element',
        '#element_id' => $element_id,
      ]);

      $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
      if ($items[$delta]->isEmpty()) {
        $content = $this->t($this->getSetting('widget_on_demand')['empty_element_on_form_init_placeholder']);
      }
      else {
        // Generating the view of a single element is more complex than
        // generating the view of the whole item list, because when generating
        // the view of only a single field item the parent entity will be
        // cloned in order to remove the other items from the list.
        //
        // @see \Drupal\Core\Entity\EntityViewBuilder::viewFieldItem
        $content = $is_multiple ? $items[$delta]->view() : $items->view();
      }

      $element['view'] = [
        '#theme' => 'widget_on_demand_field',
        '#title' => $this->fieldDefinition->getLabel(),
        '#field_name' => $this->fieldDefinition->getName(),
        '#field_type' => $this->fieldDefinition->getType(),
        '#content' => $content,
        '#is_multiple' => $is_multiple,
        '#is_empty' => $items[$delta]->isEmpty(),
      ];

      // Add hidden value used to populate correctly the form state values in
      // ::massageFormValues if the real form element was not requested.
      foreach ($items[$delta]->getValue() as $name => $value) {
        $element['on_demand'][$name] = [
          '#type' => 'hidden',
          '#value' => $value,
        ];
      }

      // A hidden ajax submit button to replace the view element with the real
      // form element. It is used by the form_element_on_demand library
      // provided by this module.
      $wrapper_id = $element_id . '-get-form-element-wrapper';
      $element['form_element_on_demand'] = [
        '#type' => 'submit',
        '#name' => $element_id,
        '#value' => $element_id,
        '#attributes' => ['class' => ['visually-hidden']],
        '#limit_validation_errors' => [array_merge($form['#parents'], [$this->fieldDefinition->getName(), $delta])],
        '#submit' => [[get_class($this), 'getFormElementSubmit']],
        '#ajax' => [
          'callback' => [get_class($this), 'getFormElementAjax'],
          'wrapper' => $wrapper_id,
          'effect' => 'slide',
        ],
      ];

      // Add the widget_form_element_on_demand js library, which will listen
      // for clicks on the view elements and on click exchange the view element
      // with its corresponding form element.
      $element['#attached']['library'][] = 'widget_on_demand/widget_form_element_on_demand';
    }

    return $element;
  }

  /**
   * Submission handler for generating the form element on demand.
   */
  public static function getFormElementSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Retrieve the parents from the button and remove its name in order to
    // address the field only.
    $parents = $button['#parents'];
    array_pop($parents);

    // Flag the field in the widget on demand storage as initialized.
    $wod_storage = &$form_state->get('widget_on_demand_storage');
    if (is_null($wod_storage)) {
      $form_state->set('widget_on_demand_storage', []);
      $wod_storage = &$form_state->get('widget_on_demand_storage');
    }
    $wod_key = $parents;
    NestedArray::setValue($wod_storage, $wod_key, TRUE);

    // Now we have to rebuild the form, so that the real form element is being
    // generated and processed by the form builder.
    // @see \Drupal\Core\Form\FormBuilder::doBuildForm
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for  for generating the form element on demand..
   *
   * This returns the real form element to replace the view element made
   * obsolete by the form submission.
   */
  public static function getFormElementAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If any errors occurred return them without replacing the element as it
    // might have not been properly initialized.
    if (static::addFormErrorsToAjaxResponse($response, $form_state)) {
      return $response;
    }

    $button = $form_state->getTriggeringElement();
    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    // The weight element is already set during the initialisation of the form
    // element. Remove it so that we do not get a second table element for the
    // weight.
    unset($element['_weight']);

    // We use the button name to uniquely identify the element, which should be
    // replaced.
    $response->addCommand(new ReplaceCommand('#' . $button['#name'], $element));

    return $response;
  }

  /**
   * Adds status messages to the ajax response if any errors occurred.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The ajax response.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   Returns TRUE if the form state contains errors and they have been added
   *   to the ajax response, FALSE otherwise.
   */
  protected static function addFormErrorsToAjaxResponse(AjaxResponse $response, FormStateInterface $form_state) {
    if ($errors = $form_state->getErrors()) {
      $display = '';
      $status_messages = array('#type' => 'status_messages');
      if ($messages = \Drupal::service('renderer')->renderRoot($status_messages)) {
        $display = '<div class="views-messages">' . $messages . '</div>';
      }
      $options = array(
        'dialogClass' => 'views-ui-dialog',
        'width' => '50%',
      );

      // Attach the library necessary for using the OpenModalDialogCommand and
      // set the attachments for this Ajax response.
      $status_messages['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($status_messages['#attached']);

      $response->addCommand(new OpenModalDialogCommand(t('Error Messages'), $display, $options));

      return TRUE;
    }
    return FALSE;
  }

  /**
   * Submission handler for the "Add another item" button.
   *
   * Overrides the submission handler of the base class to mark new elements
   * added by ajax.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state) {
    parent::addMoreSubmit($form, $form_state);
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $form_state->set('new_item_' . $field_state['items_count'] - 1, TRUE);

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Populate correctly the form state values if the real form element was
    // not requested.
    foreach ($values as &$value) {
      if (is_array($value) && isset($value['on_demand'])) {
        $on_demand = $value['on_demand'];
        unset($value['on_demand']);
        $value = array_merge($value, $on_demand);
      }
    }

    return $values;
  }

}
