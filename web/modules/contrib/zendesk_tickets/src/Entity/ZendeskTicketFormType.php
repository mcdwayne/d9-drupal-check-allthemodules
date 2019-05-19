<?php

namespace Drupal\zendesk_tickets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\zendesk_tickets\ZendeskTicketFormTypeInterface;
use Drupal\zendesk_tickets\Zendesk\ZendeskAPI;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the Zendesk Form Type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "zendesk_ticket_form_type",
 *   label = @Translation("Zendesk Ticket Form Type"),
 *   handlers = {
 *     "access" = "Drupal\zendesk_tickets\ZendeskTicketFormTypeAccessControlHandler",
 *     "form" = {},
 *     "list_builder" = "Drupal\zendesk_tickets\ZendeskTicketFormTypeListBuilder",
 *     "zendesk_api" = "Drupal\zendesk_tickets\Zendesk\ZendeskAPI",
 *     "zendesk_ticket_form_builder" = "Drupal\zendesk_tickets\ZendeskTicketFormTypeSubmitFormBuilder"
 *   },
 *   admin_permission = "administer zendesk ticket form types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/zendesk-ticket-form-type",
 *     "canonical" = "/submit-request/{zendesk_ticket_form_type}",
 *     "edit" = "/admin/structure/zendesk-ticket-form-types/zendesk-ticket-form-type/{zendesk_ticket_form_type}",
 *     "enable" = "/admin/structure/zendesk-ticket-form-types/zendesk-ticket-form-type/{zendesk_ticket_form_type}/enable",
 *     "disable" = "/admin/structure/zendesk-ticket-form-types/zendesk-ticket-form-type/{zendesk_ticket_form_type}/disable"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "machineName",
 *     "importedTime",
 *     "ticketFormData",
 *     "hasLocalStatus",
 *     "weight"
 *   }
 * )
 */
class ZendeskTicketFormType extends ConfigEntityBase implements ZendeskTicketFormTypeInterface {

  /**
   * {@inheritdoc}
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  protected $label;

  /**
   * The machine name of the form.
   *
   * @var string
   */
  protected $machineName;

  /**
   * {@inheritdoc}
   */
  protected $status = TRUE;

  /**
   * TRUE if this form has a local Drupal status.
   *
   * @var bool
   */
  protected $hasLocalStatus = FALSE;

  /**
   * The importedTime timestamp.
   *
   * @var int
   */
  protected $importedTime;

  /**
   * The encoded JSON form data string.
   *
   * @var string
   */
  protected $ticketFormData;

  /**
   * The decoded JSON form object.
   *
   * @var object
   */
  protected $ticketFormObject;

  /**
   * The form weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    // Do not remove this override: the default value of $rel is different.
    return parent::urlInfo($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'canonical', $options = array()) {
    // Do not remove this override: the default value of $rel is different.
    return parent::url($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    // Do not remove this override: the default value of $rel is different.
    return parent::link($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    // Do not remove this override: the default value of $rel is different.
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = [];
    if (!in_array($rel, ['collection'], TRUE)) {
      // The entity ID is needed as a route parameter.
      // Provide machine name based paths.
      $name = $this->getMachineName();
      if ($name && ($name_path = static::convertMachineNameToUrlPath($name))) {
        $uri_route_parameters[$this->getEntityTypeId()] = $name_path;
      }
      else {
        $uri_route_parameters[$this->getEntityTypeId()] = $this->id();
      }
    }
    else {
      $uri_route_parameters = parent::urlRouteParameters($rel);
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    parent::set($property_name, $value);
    if ($property_name == 'ticketFormData') {
      $this->setTicketFormData($value);
    }
    elseif ($property_name == 'ticketFormObject') {
      $this->setTicketFormObject($value);
    }

    return $this;
  }

  /**
   * Set the label and create new machine name.
   */
  public function setLabel($label) {
    $this->label = $label;
    $this->setMachineName($label);
    return $this;
  }

  /**
   * Set the machine name.
   *
   * @param string $name
   *   The machine name.
   */
  public function setMachineName($name) {
    $this->machineName = $this->createMachineName($name);
    return $this;
  }

  /**
   * Get the machine name.
   *
   * @return string
   *   The machine name.
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * {@inheritdoc}
   */
  public function getImportedTime() {
    return $this->importedTime;
  }

  /**
   * {@inheritdoc}
   */
  public function setImportedTime($timestamp) {
    $this->importedTime = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasLocalStatus() {
    return !empty($this->hasLocalStatus);
  }

  /**
   * {@inheritdoc}
   */
  public function setHasLocalStatus($has_local_status = TRUE) {
    $this->hasLocalStatus = (bool) $has_local_status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function ticketFormStatus() {
    $form_object = $this->getTicketFormObject();
    return $form_object && !empty($form_object->id) && $form_object->id == $this->id() &&
      !empty($form_object->active) && !empty($form_object->end_user_visible);
  }

  /**
   * {@inheritdoc}
   */
  public function canSubmit() {
    $api = $this->zendeskApi();
    $id = $this->id();
    return isset($id) && $this->status() && $this->ticketFormStatus() && $api && $api->isCapable();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsFileUploads() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicketFormData() {
    return $this->ticketFormData;
  }

  /**
   * {@inheritdoc}
   */
  public function setTicketFormData($string) {
    $this->ticketFormData = $string;
    $this->setTicketFormObjectByString($string);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTicketFormObject() {
    if (!isset($this->ticketFormObject) && ($data_string = $this->getTicketFormData())) {
      $this->setTicketFormObjectByString($data_string);
    }
    return $this->ticketFormObject;
  }

  /**
   * {@inheritdoc}
   */
  public function setTicketFormObject($object) {
    $this->ticketFormObject = $object;
    $this->ticketFormData = static::jsonEncode($object);
    $this->setValuesFromTicketFormObject();
    return $this;
  }

  /**
   * Set the ticket form object with a JSON string.
   *
   * @param string $string
   *   The form JSON data string.
   */
  protected function setTicketFormObjectByString($string) {
    $this->ticketFormObject = static::jsonDecode($string);

    // Ensure ticket_fields is an array.
    // The ticket_fields gets encoded as an object instead of an array of
    // objects.
    // Converting to an array directly led to this bug -
    // https://mindpalette.com/php-broken-array-from-json_decode-object/
    if (isset($this->ticketFormObject->ticket_fields)) {
      $fields = $this->ticketFormObject->ticket_fields;
      $fields_array = [];
      foreach ($fields as $k => $v) {
        $fields_array[$k] = $v;
      }

      $this->ticketFormObject->ticket_fields = $fields_array;
    }

    $this->setValuesFromTicketFormObject();
    return $this;
  }

  /**
   * Extracts values from the form object.
   */
  protected function setValuesFromTicketFormObject() {
    $form_object = $this->getTicketFormObject();
    if ($form_object) {
      $new_label = NULL;
      if (!empty($form_object->display_name)) {
        $new_label = $form_object->display_name;
      }
      elseif (!empty($form_object->name)) {
        $new_label = $form_object->name;
      }
      elseif (empty($this->label) && isset($form_object->id)) {
        $new_label = 'Form ' . $form_object->id;
      }
      if (isset($new_label)) {
        $this->setLabel($new_label);
      }

      if (isset($form_object->position)) {
        $this->setWeight($form_object->position);
      }
    }

    return $this;
  }

  /**
   * Return the Zendesk API instance.
   *
   * @return \Drupal\zendesk_tickets\Zendesk\ZendeskAPI|null
   *   An instance of the form builder handler.
   */
  public function zendeskApi() {
    return static::getHandlerInstance('zendesk_api', $this->getEntityTypeId(), $this->entityTypeManager());
  }

  /**
   * Return the ticket form builder handler instance.
   *
   * @return \Drupal\zendesk_tickets\ZendeskTicketFormTypeSubmitFormBuilderInterface|null
   *   An instance of the form builder handler.
   */
  public function ticketFormBuilder() {
    return static::getHandlerInstance('zendesk_ticket_form_builder', $this->getEntityTypeId(), $this->entityTypeManager());
  }

  /**
   * Wraps the transliteration service.
   *
   * @return \Drupal\Component\Transliteration\TransliterationInterface
   *   The transliteration service object.
   */
  protected function transliteration() {
    if (!$this->transliteration) {
      $this->transliteration = \Drupal::transliteration();
    }
    return $this->transliteration;
  }

  /**
   * Create a machine name.
   *
   * @param string $value
   *   The sting to convert.
   *
   * @return string
   *   The machien name.
   */
  public function createMachineName($value) {
    $new_value = $this->transliteration()->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $new_value = strtolower($new_value);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', $new_value);
    return preg_replace('/_+/', '_', $new_value);
  }

  /**
   * Convert a machine name to a url path.
   *
   * @param string $name
   *   The machine name.
   *
   * @return string
   *   The name to be use in a url path.
   */
  public static function convertMachineNameToUrlPath($name) {
    return str_replace('_', '-', $name);
  }

  /**
   * Convert a machine name from a url path to the native machine name.
   *
   * @param string $name_path
   *   The name used in a url path.
   *
   * @return string
   *   The native machine name.
   */
  public static function convertMachineNameFromUrlPath($name_path) {
    return str_replace('-', '_', $name_path);
  }

  /**
   * Retrieve a handler instance.
   *
   * @param string $handler_type
   *   The handler type.
   *
   * @return \Drupal\Core\Entity\EntityHandlerInterface|null
   *   An instance of the handler class for this entity type.
   */
  protected function getHandlerInstance($handler_type) {
    $entity_type_manager = $this->entityTypeManager();
    if (!isset($entity_type_manager)) {
      return NULL;
    }
    $entity_type = $this->getEntityTypeId();
    $has_handler = $entity_type_manager->hasHandler($entity_type, $handler_type);
    return $has_handler ? $entity_type_manager->getHandler($entity_type, $handler_type) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTicketForm() {
    $form = [];
    $builder = $this->ticketFormBuilder();
    if ($builder) {
      $form = $builder->buildForm($this);
    }
    return $form;
  }

  /**
   * Encode a variable to JSON.
   *
   * @param mixed $variable
   *   The variable to encode.
   *
   * @return string
   *   The JSON encoded string.
   */
  public static function jsonEncode($variable) {
    return Json::encode($variable);
  }

  /**
   * Decode a JSON encoded string.
   *
   * Custom override since Json::decode() forces an assoc array.
   *
   * @param string $string
   *   The JSON encoded string.
   *
   * @return object
   *   The decode JSON object.
   */
  public static function jsonDecode($string) {
    return json_decode($string, FALSE, 512, JSON_BIGINT_AS_STRING);
  }

}
