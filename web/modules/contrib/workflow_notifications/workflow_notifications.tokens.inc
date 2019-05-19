<?php

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\workflow\Entity\WorkflowState;

/**
 * Implements hook_token_info().
 **/

function workflow_notifications_token_info() {
  $types['workflow_state'] = [
    'name' => t('Workflow State'),
    'description' => t('Token for the workflow state entity'),
    'needs-data' => 'workflow_state',
  ];
  $ws['id'] = [
    'name' => t('State ID'),
    'description' => t('Machine name of the state'),
  ];
  $ws['label'] = [
    'name' => t('Label'),
    'description' => t('State name'),
  ];
  $ws['wid'] = [
    'name' => t('workflow ID'),
    'description' => t('ID of the Workflow'),
  ];
  return [
    'types' => $types,
    'tokens' => ['workflow_state' => $ws],
  ];
}


/**
 * Implements hook_tokens().
 * @param $type
 * @param $tokens
 * @param array $data
 * @param array $options
 * @param BubbleableMetadata $bubbleable_metadata
 *
 * @return array
 */
function workflow_notifications_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'workflow_state' && !empty($data['workflow_state'])) {
    $ws = $data['workflow_state'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $ws->id;
          break;
        case 'label':
          $replacements[$original] = $ws->label;
          break;
        case 'wid':
          $replacements[$original] = $ws->wid;
          break;
      }
    }
  }

  if (!empty($data['field_property'])) {
    if (isset($options['langcode'])) {
      $url_options = ['absolute' => TRUE];
      $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
      $langcode = $options['langcode'];
    }
    else {
      $langcode = NULL;
    }

    $token_service = \Drupal::token();
    foreach ($tokens as $token => $original) {
      $filtered_tokens = $tokens;
      $delta = 0;
      $parts = explode(':', $token);
      if (is_numeric($parts[0])) {
        if (count($parts) > 1) {
          $delta = $parts[0];
          $property_name = $parts[1];
          // Pre-filter the tokens to select those with the correct delta.
          $filtered_tokens = \Drupal::token()->findWithPrefix($tokens, $delta);
          // Remove the delta to unify between having and not having one.
          array_shift($parts);
        }
        else {
          // Token is fieldname:delta, which is invalid.
          continue;
        }
      }
      else {
        $property_name = $parts[0];
      }
      if (isset($data[$data['field_name']][$delta])) {
        $field_item = $data[$data['field_name']][$delta];
      }
      else {
        // The field has no such delta, abort replacement.
        continue;
      }
      if (isset($field_item->$property_name) && ($field_item->$property_name instanceof WorkflowState)) {
        // Entity reference field.
        $entity = $field_item->$property_name;
        // Obtain the referenced entity with the correct language.

        $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
        if (count($parts) > 1) {
          $field_tokens = $token_service->findWithPrefix($filtered_tokens, $property_name);
          $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity->getEntityTypeId(), TRUE);
          $replacements += $token_service->generate($token_type, $field_tokens, [$token_type => $entity], $options, $bubbleable_metadata);
        }
        else {
          $replacements[$original] = $entity->label();
        }
      }
    }
  }
  return $replacements;

}