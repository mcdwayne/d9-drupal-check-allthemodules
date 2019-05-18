<?php 


namespace Drupal\flag_rating\Form;

use Drupal\flag\Form\FlagEditForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the flag rating edit form.
 *
 * @see \Drupal\flag\Form\FlagEditForm
 */
class FlagRatingEditForm extends FlagEditForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL) {
    
    // Default values.
    $options = [];
    $flag = $this->entity;
    $form = parent::buildForm($form, $form_state);
    
    $existing_fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('flagging', $flag->getOriginalId());
    foreach ($existing_fields as $field_name => $field) {
      // Exclude base fields.
      if (get_class($field) == 'Drupal\field\Entity\FieldConfig') {
        // Exclude all fields that are not numbers.
        if ($field->getType() == 'integer') {
          $options[$field_name] = $field->label();
        }
      }
    }

    $description = $this->t('Select which field is used to save rating scores.');
    $description .= $this->t('Must be of type <em>Number (integer)</em>.');
    $description .= '<br>';
    $description .= $this->t('<strong>Warning:</strong> Changin this setting will reset all scores to zero.');
    
    // Default from element wrapper.
    if (!isset($form['third_party_settings'])) { 
      $form['third_party_settings'] = [
        '#type' => 'details',
        '#collapsible' => FALSE,
        '#open' => TRUE,
        '#title' => $this->t('Third party settings'),
        '#weight' => -1,
      ];
    } 

    // Add the score field.
    $form['third_party_settings']['score_field'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Score field'),
      '#description' => $description,
      '#default_value' => $flag->getThirdPartySetting('flag_rating', 'score_field', NULL),
      '#required' => !empty($options),
    ];
    // Add min/max values
    $form['third_party_settings']['score_min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum rating'),
      '#default_value' => $flag->getThirdPartySetting('flag_rating', 'score_min', 1),
      '#required' => TRUE,
    ];
    $form['third_party_settings']['score_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max rating'),
      '#default_value' => $flag->getThirdPartySetting('flag_rating', 'score_max', 5),
      '#required' => TRUE,
    ];

    // Custom icon for the action link.
    $icon_directory = 'public://flag-rating-icon';
    $allowed_extensions = 'png jpeg jpg svg';
    $form['third_party_settings']['action_icon'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Icon file'),
      '#description' => $this->t('Allowed extensions: @extensions', ['@extensions' => $allowed_extensions]),
      '#upload_location' => $icon_directory,
      '#upload_validators' => [
        'file_validate_extensions' => [$allowed_extensions],
      ],
    ];
    if ($icon_file_id = $flag->getThirdPartySetting('flag_rating', 'action_icon', NULL)) {
      $form['third_party_settings']['action_icon']['#default_value'] = ['target_id' => $icon_file_id];
    }
    else {
      // Try to save default icon file.
      try {
        if ($icon_file = flag_rating_create_default_icon()) {
          $form['third_party_settings']['action_icon']['#default_value'] = ['target_id' => $icon_file->id()];
        }
        else {
          \Drupal::logger('flag_rating')->warning('Icon file could not be created. Please check your file system permissions.');
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('flag_rating')->error('Default icon file could not be created: ' . $e->getMessage());
      }
    }
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $flag = $this->entity;
    // Get clean Icon file ID.
    $icon_value = $form_state->getValue('action_icon');
    $action_icon = is_array($icon_value) ? reset($icon_value) : $icon_value;
    // Save extra configurations.
    $flag->setThirdPartySetting('flag_rating', 'score_field', $form_state->getValue('score_field'));
    $flag->setThirdPartySetting('flag_rating', 'score_min', $form_state->getValue('score_min'));
    $flag->setThirdPartySetting('flag_rating', 'score_max', $form_state->getValue('score_max'));
    $flag->setThirdPartySetting('flag_rating', 'action_icon', $action_icon);
    return parent::save($form, $form_state);
  }

}
