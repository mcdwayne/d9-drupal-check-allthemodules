<?php

namespace Drupal\enforce_profile_field;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EnforceProfile.
 *
 * @package Drupal\enforce_profile_field
 */
class EnforceProfile {

  /**
   * A form mode identifier.
   *
   * @var string
   */
  protected $formMode = '';

  /**
   * Required fields.
   *
   * @var arrray
   */
  protected $fields = [];

  /**
   * Constructs an EnforceProfile object.
   *
   * @param string $form_mode
   *   A form mode identifier.
   */
  public function __construct($form_mode) {
    $this->formMode = $form_mode;
  }

  /**
   * Get from mode.
   *
   * @param \Drupal\enforce_profile_field\EntityInterface $entity
   *   An entity holding the form mode field.
   * @param string $field_name
   *   A field machine name.
   *
   * @return string
   *   A form mode.
   */
  public static function getFormMode(EntityInterface $entity, $field_name) {
    // Get form mode settings from field definition.
    $field_definition = $entity->getFieldDefinition($field_name);
    $form_mode = $field_definition->getSetting('form_mode');

    return $form_mode;
  }

  /**
   * Validate field items.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $item
   *   A field item list.
   */
  public function validate(FieldItemListInterface $item) {
    $this->collectRequiredFields($item);
    $this->validateRequiredFields($item);
  }

  /**
   * Collect required fields.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $item
   *   A field item list.
   */
  private function collectRequiredFields(FieldItemListInterface $item) {
    $user = \Drupal::currentUser();
    /** @var \Drupal\user\UserInterface $user_account */
    $user_account = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->load($user->id());

    // Get all required field machine names.
    $values = $item->getValue();
    $fields = [];
    // Collect all required fields .
    foreach ($values as $value) {
      $machine_name = $value['value'];

      // Collect the field only if it still exists.
      $field_definition = $user_account->getFieldDefinition($machine_name);
      if (isset($field_definition)) {
        $field = $user_account->get($machine_name);
        $fields[] = $field;
      }
    }

    $this->setRequiredFields($fields);
  }

  /**
   * Set required fields.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface[] $fields
   *   An array of required field items.
   */
  private function setRequiredFields(array $fields) {
    $this->fields = $fields;
  }

  /**
   * Validate required fields.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $item
   *   A field item list.
   */
  private function validateRequiredFields(FieldItemListInterface $item) {
    $fields = $this->getRequiredFields();

    foreach ($fields as $field) {
      // Validate that the field item is filled in.
      if ($field->isEmpty()) {
        // Force redirect the user to fill in missing fields.
        $this->redirect($item);
        break;
      }
    }
  }

  /**
   * Get required fields.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   An array of required field items.
   */
  private function getRequiredFields() {
    return $this->fields;
  }

  /**
   * Redirect the user to fill in missing fields.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $item
   *   A field item list.
   */
  private function redirect(FieldItemListInterface $item) {
    $field_labels = $this->getRequiredFieldsLabels();
    $entity = $item->getEntity();
    $destination = $entity->toUrl()->toString();
    $destination_title = $entity->getTitle();

    // Inform user about missing fields that needs to be filled in in order
    // to access the page.
    $args = ['@field_labels' => $field_labels, '@destination_title' => $destination_title];
    $message = t('You need to fill in the following fields: "@field_labels" in order to be allowed to access the page: @destination_title.', $args);
    drupal_set_message($message, 'status', TRUE);

    // Get form mode path.
    $url = $this->getFormModeUrl($destination);

    $response = new RedirectResponse($url);
    $response->send();
    exit();
  }

  /**
   * Get required field labels.
   *
   * @return string
   *   A required field labels separated by comma.
   */
  private function getRequiredFieldsLabels() {
    $labels = [];
    $fields = $this->getRequiredFields();

    foreach ($fields as $field) {
      $labels[] = $field->getFieldDefinition()->getLabel();
    }

    return implode(', ', $labels);
  }

  /**
   * Get form mode url.
   *
   * @param string $destination
   *   Destination query of the url.
   *
   * @return string
   *   Form mode url.
   */
  public function getFormModeUrl($destination = '') {
    $entity_form_display = $this->getFormDisplay();

    // Redirect the user to fill in missing fields.
    $mode = $entity_form_display->get('mode');
    $user = \Drupal::currentUser();
    $base_url = URL::fromRoute('entity.user.edit_form', ['user' => $user->id()])
      ->toString();

    // Prepare destination query if present.
    $options = [];
    if (!empty($destination)) {
      $options['query'] = ['destination' => $destination];
    }
    // TODO: Look for a better way how to get an entity form display url.
    $url = Url::fromUserInput($base_url . '/' . $mode, $options)->toString();

    return $url;
  }

  /**
   * Get form display.
   *
   * @param string $entity_type_id
   *   An entity type id.
   * @param string $bundle
   *   A bundle machine name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   A form display.
   */
  private function getFormDisplay($entity_type_id = 'user', $bundle = 'user') {
    $form_mode_id = $entity_type_id . '.' . $bundle . '.' . $this->formMode;

    // Get the selected entity form display.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
    $form_display = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load($form_mode_id);

    return $form_display;
  }

}
