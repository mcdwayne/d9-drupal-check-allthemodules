<?php

namespace Drupal\rules_webform\Event;

use Symfony\component\EventDispatcher\Event;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Base class for events available in the 'RULES WEBFORM' module.
 *
 * Event objects creates and events dispatches from related hooks which implemented in rules_webform.module.
 */
class RulesWebformEventBase extends Event {
  const EVENT_NAME = 'webform_submit';

  /**
   * The webform fields with values (submissions).
   *
   * @var Drupal\rules_webform\Plugin\DataType\WebformFields
   */
  public $webform_fields;

  /**
   * Submitted webform info.
   *
   * @var Drupal\rules_webform\Plugin\DataType\WebformInfo
   */
  public $webform_info;

  /**
   * Сonstructs the object.
   *
   * Rewrite the information needed for 'webform_fields' properties definitions.
   * This allows to create rules for several different webforms.
   * The information are then using in 'WebformFieldsDataDefinition' class.
   * And it's needed for properly working of property selector of 'webform_fields' context variable.
   *
   * @param Drupal\webform\Entity\WebformSubmission $submission
   *   Webform submission object.
   */
  public function __construct(WebformSubmission $submission) {

    // Store webform submission for using in 'set_webform_field' action.
    \Drupal::state()->set('rules_webform.submission', $submission);

    // Get the information about a webform fields.
    $webform = Webform::load($submission->getWebform()->id());
    $elements = $webform->getElementsInitializedAndFlattened();
    $fields_definitions = [];
    $this->extractcompositeElements($elements, $fields_definitions);
    // Store webform fields information for using in 'WebformFieldsDataDefinition' class.
    \Drupal::state()->set('rules_webform.fields_definitions', $fields_definitions);

    $this->extractcompositeSubmissionData($submission->getData());
    // Add properties data to the 'webform_info' context variable.
    $this->initializeWebformInfo($submission);
  }

  /**
   * Extract the information about a webform fields.
   *
   * This information will be used for fields data definition in the 'WebformFieldsDataDefinition' class.
   */
  private function extractcompositeElements(array $elements, &$fields_definitions) {
    foreach ($elements as $name => $options) {
      if (isset($options['#webform_composite_elements'])) {
        $this->extractcompositeElements($options['#webform_composite_elements'], $fields_definitions);
      }
      else {
        // Exclude wizard pages and buttons from the list of elements.
        if (($options['#type'] != 'webform_wizard_page') && ($options['#type'] != "webform_actions")) {
          $fields_definitions[$name] = (string) $options['#title'];
          // If a user will submit an empty webform which contents composite element,
          // then value of this element can be 'NULL'.
          // Therefore prefill the array of webform_fields to prevent error message appearance.
          $this->webform_fields[$name] = '';
        }
      }
    }
  }

  /**
   * Extract fields values of the submitted webform.
   */
  private function extractcompositeSubmissionData(array $submissionData) {
    foreach ($submissionData as $key => $value) {
      if (is_array($value)) {
        $this->extractcompositeSubmissionData($value);
      }
      else {
        // If a user will submit an empty webform which contents composite element,
        // then value of this composite element can be 'NULL'.
        // Make the check to prevent adding such item to the webform_fields array.
        if (isset($value)) {
          $this->webform_fields[$key] = $value;
        }
      }
    }
  }

  /**
   * Add data about webform (such as name, date and other) to the 'webform_info' context variable.
   */
  private function initializeWebformInfo(WebformSubmission $submission) {

    // The submission object needed for "Delete webform submission" action.
    // He is not added to the property definitions of webform_info and therefore is not visible in autocomplete.
    $this->webform_info['submission'] = $submission;

    // Adding properties for which was created properties definitions and which are visible in autocomplete.
    $this->webform_info['id'] = $submission->getWebform()->id();
    $this->webform_info['title'] = $submission->getWebform()->get('title');
    $this->webform_info['submitter_id'] = $submission->getOwnerId();
    $this->webform_info['submitter_name'] = $submission->getOwner()->getDisplayName();
    $this->webform_info['submitter_email'] = $submission->getOwner()->getEmail();

    $timestamp = $submission->getcreatedTime();
    $this->webform_info['created']['timestamp'] = $timestamp;
    $this->webform_info['created']['date_short'] = \Drupal::service('date.formatter')->format($timestamp, 'date_short');
    $this->webform_info['created']['date_medium'] = \Drupal::service('date.formatter')->format($timestamp, 'date_medium');
    $this->webform_info['created']['date_long'] = \Drupal::service('date.formatter')->format($timestamp, 'date_long');
    $this->webform_info['created']['html_datetime'] = \Drupal::service('date.formatter')->format($timestamp, 'html_datetime');
    $this->webform_info['created']['html_date'] = \Drupal::service('date.formatter')->format($timestamp, 'html_date');
    $this->webform_info['created']['html_time'] = \Drupal::service('date.formatter')->format($timestamp, 'html_time');

    $timestamp = $submission->getchangedTime();
    $this->webform_info['changed']['timestamp'] = $timestamp;
    $this->webform_info['changed']['date_short'] = \Drupal::service('date.formatter')->format($timestamp, 'date_short');
    $this->webform_info['changed']['date_medium'] = \Drupal::service('date.formatter')->format($timestamp, 'date_medium');
    $this->webform_info['changed']['date_long'] = \Drupal::service('date.formatter')->format($timestamp, 'date_long');
    $this->webform_info['changed']['html_datetime'] = \Drupal::service('date.formatter')->format($timestamp, 'html_datetime');
    $this->webform_info['changed']['html_date'] = \Drupal::service('date.formatter')->format($timestamp, 'html_date');
    $this->webform_info['changed']['html_time'] = \Drupal::service('date.formatter')->format($timestamp, 'html_time');

    if ($submission->isDraft()) {
      // To prevent an error message if a user will try to use 'completed' values when a draft was submitted.
      $this->webform_info['completed']['timestamp'] = '';
      $this->webform_info['completed']['date_short'] = '';
      $this->webform_info['completed']['date_medium'] = '';
      $this->webform_info['completed']['date_long'] = '';
      $this->webform_info['completed']['html_datetime'] = '';
      $this->webform_info['completed']['html_date'] = '';
      $this->webform_info['completed']['html_time'] = '';

    }
    else {
      $timestamp = $submission->getcompletedTime();
      $this->webform_info['completed']['timestamp'] = $timestamp;
      $this->webform_info['completed']['date_short'] = \Drupal::service('date.formatter')->format($timestamp, 'date_short');
      $this->webform_info['completed']['date_medium'] = \Drupal::service('date.formatter')->format($timestamp, 'date_medium');
      $this->webform_info['completed']['date_long'] = \Drupal::service('date.formatter')->format($timestamp, 'date_long');
      $this->webform_info['completed']['html_datetime'] = \Drupal::service('date.formatter')->format($timestamp, 'html_datetime');
      $this->webform_info['completed']['html_date'] = \Drupal::service('date.formatter')->format($timestamp, 'html_date');
      $this->webform_info['completed']['html_time'] = \Drupal::service('date.formatter')->format($timestamp, 'html_time');
    }

    $this->webform_info['number'] = $submission->serial();
    $this->webform_info['id_submission'] = $submission->id();
    $this->webform_info['uuid'] = $submission->uuid();
    $this->webform_info['uri'] = $submission->get('uri')->value;
    $this->webform_info['ip'] = $submission->getRemoteAddr();
    $this->webform_info['language'] = $submission->language()->getName();
    $this->webform_info['is_draft'] = $submission->isDraft();
    $this->webform_info['current_page'] = $submission->getcurrentPage();
  }

}
