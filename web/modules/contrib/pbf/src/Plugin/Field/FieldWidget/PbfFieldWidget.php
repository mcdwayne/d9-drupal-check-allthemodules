<?php

namespace Drupal\pbf\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\user\UserInterface;

/**
 * Plugin implementation of the 'pbf_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "pbf_widget",
 *   label = @Translation("Permissions by field widget"),
 *   field_types = {
 *     "pbf"
 *   }
 * )
 */
class PbfFieldWidget extends EntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();

    if ($entity instanceof UserInterface) {
      return $element;
    }

    $item = $items[$delta];
    $operations = $item->getOperations();

    /** @var \Drupal\field\FieldConfigInterface $field_definition */
    $field_definition = $item->getFieldDefinition();
    $field_name = $field_definition->getName();

    $grant_global = $this->getSettings();

    foreach ($operations as $key => $label) {
      // If a value has not been yet set, we fetch the default grant access
      // settings definied in the form settings widget.
      $default_value_key = isset($item->{$key}) ? $item->{$key} : $this->getSetting($key);
      // If grant access are set generally, we override the default value.
      if ($this->getSetting('grant_global')) {
        $default_value_key = $this->getSetting($key);
      }
      $element[$key] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $default_value_key,
        '#return_value' => 1,
        '#empty' => 0,
        '#weight' => $delta + 1,
        '#access' => $this->getSetting('grant_global') ? FALSE : TRUE,
      ];

      // We hide other $key than grant_public because these keys are not used
      // if grant_public is checked. With grant_public, we let standard
      // permissions apply on the node.
      if ($key != 'grant_public') {
        $element[$key]['#states'] = [
          'invisible' => [
            ':input[name="' . $field_name . '[' . $delta . '][grant_public]"]' => array('checked' => TRUE),
          ],
        ];
      }
    }

    $element['help'] = [
      '#type' => 'details',
      '#title' => 'Help about permissions',
      '#markup' => $this->t('The public checkbox checked means that standard 
      permissions will be applied. With this option checked you can simply
      reference an entity without any custom permissions applied to this current 
      node. 
      If you want to apply custom permissions for this node, permissions related 
      to the entity referenced, uncheck public option, 
      and then choose relevant permissions. If none of custom permissions are 
      checked, only the node\'s author will have access to the node.'),
      '#attributes' => ['class' => ['description', 'pbf-help']],
      '#weight' => 5,
      '#access' => $this->getSetting('grant_global') ? FALSE : TRUE,
    ];

    $element['#attached']['library'][] = 'pbf/widget';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'grant_global' => 0,
      'grant_public' => 1,
      'grant_view' => 0,
      'grant_update' => 0,
      'grant_delete' => 0,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $field_definition = $this->fieldDefinition;

    $options = [
      0 => $this->t('Set default grant access per node'),
      1 => $this->t('Set grant access settings generally'),
    ];

    $element['grant_global'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Set default grant access settings per node or define them generally'),
      '#default_value' => $this->getSetting('grant_global'),
      '#options' => $options,
      '#description' => $this->t('You can set the default grant access settings for each node. Otherwise, you can set grant settings generally for every entities. Grant access settings will be then hide to users in the form element'),
    );
    $element['grant_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Public'),
      '#default_value' => $this->getSetting('grant_public'),
      '#return_value' => (int) 1,
      '#empty' => 0,
      '#states' => array(
        'visible' => array(
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][grant_global]"]' => array('checked' => TRUE),
        ),
      ),
    ];
    $element['grant_view'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grant view'),
      '#default_value' => $this->getSetting('grant_view'),
      '#return_value' => (int) 1,
      '#empty' => 0,
      '#states' => array(
        'visible' => array(
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][grant_global]"]' => array('checked' => TRUE),
        ),
      ),
    ];
    $element['grant_update'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grant update'),
      '#default_value' => $this->getSetting('grant_update'),
      '#return_value' => (int) 1,
      '#empty' => 0,
      '#states' => array(
        'visible' => array(
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][grant_global]"]' => array('checked' => TRUE),
        ),
      ),
    ];
    $element['grant_delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Grant delete'),
      '#default_value' => $this->getSetting('grant_delete'),
      '#return_value' => (int) 1,
      '#empty' => 0,
      '#states' => array(
        'visible' => array(
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][grant_global]"]' => array('checked' => TRUE),
        ),
      ),
    ];

    $element['#attached']['library'][] = 'pbf/widget';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $operators = $this->getMatchOperatorOptions();
    $summary[] = t('Autocomplete matching: @match_operator', array('@match_operator' => $operators[$this->getSetting('match_operator')]));
    $summary[] = t('Textfield size: @size', array('@size' => $this->getSetting('size')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }
    else {
      $summary[] = t('No placeholder');
    }
    if ($this->getSetting('grant_global')) {
      $summary[] = t('Grants access set generally. Grant access used are :');
    }
    else {
      $summary[] = t('Grants access set on each node. Default grant access are :');
    }
    $summary[] = t('Public:@public, Grant view:@view, Grant update:@update, Grant delete:@delete', [
      '@public' => $this->getSetting('grant_public'),
      '@view' => $this->getSetting('grant_view'),
      '@update' => $this->getSetting('grant_update'),
      '@delete' => $this->getSetting('grant_delete'),
    ]);

    return $summary;
  }

}
