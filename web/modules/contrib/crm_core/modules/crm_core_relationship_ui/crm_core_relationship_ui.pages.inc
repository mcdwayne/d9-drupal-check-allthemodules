<?php

/**
 * @file
 *
 * CRM Core Relationship UI Pages.
 *
 * @TODO: Unported.
 */

use Drupal\crm_core_contact\Entity\ContactType;
use Drupal\relation\Entity\Relation;
use Drupal\relation\Entity\RelationType;

//@codingStandardsIgnoreFile

/**
 * Wrapper around entity_ui_get_form().
 *
 * Create new crm_activity entity and pass it to entity_ui_get_form().
 *
 * @param mixed $contact
 *   Contact.
 * @param mixed $type
 *   Type.
 *
 * @return mixed
 *   Unknown.
 */
function crm_core_relation_entity_ui_get_form_wrapper($contact, $type, $reverse) {
  global $user;
  $types = RelationType::loadMultiple([$type]);
  $current_type = array_pop($types);
  // Default empty Relation.
  $values = [
    'relation_type' => $type,
    'uid' => $user->uid,
    'title' => '',
    'reverse' => $reverse,
    'directional' => $current_type->directional,
    'r_unique' => $current_type->r_unique,
    'source_bundles' => $current_type->source_bundles,
    'target_bundles' => $current_type->target_bundles,
    'label' => $current_type->directional && $reverse ? $current_type->reverse_label : $current_type->label,
  ];
  $breadcrumb = [
    l(t('Home'), '<front>'),
    l(t('CRM Core'), 'crm-core'),
  ];
  if ($contact) {
    $values['crm_core_contact'] = $contact;
    $contact_uri = $contact->uri();
    $breadcrumb[] = l(t('Contacts'), 'crm-core/contact');
    $breadcrumb[] = l(t($contact->label()), $contact_uri['path']);
    $breadcrumb[] = l(t('Add a relationship'), $contact_uri['path'] . '/relationships/add');
  }
  else {
    $breadcrumb[] = l(t('Add a relationship'), 'crm-core/relationship/add');
  }

  drupal_set_breadcrumb($breadcrumb);
  $relation = crm_core_relation_create($values);

  return entity_ui_get_form('crm_core_relationship', $relation, 'add');
}

/**
 * Form builder for CRM Activity forms.
 */
function crm_core_relationship_form($form, &$form_state, $relation) {
  // Ensure this include file is loaded when the form is rebuilt from the cache.
  $form_state['build_info']['file'] = drupal_get_path('module', 'crm_core_ui') . '/pages/contact_activity.pages.inc';
  $form_state['entity_type'] = 'relation';
  $form_state['relation'] = $form_state['crm_core_relationship'];
  unset($form_state['crm_core_relationship']);

  if (isset($relation->crm_core_contact)) {
    // Get the contact id of who is trying to add the activity.
    $form['contact_id'] = [
      '#type' => 'value',
      '#value' => arg(2),
    ];
  }

  module_load_include('inc', 'crm_core_relationship_ui');

  $form = [];

  $form['relationship_type'] = [
    '#type' => 'value',
    '#value' => $relation,
  ];
  $form['reverse'] = [
    '#type' => 'value',
    '#value' => $relation->reverse,
  ];
  $form['relationship_type_label'] = [
    '#type' => 'item',
    '#title' => t('Relationship type'),
    '#markup' => $relation->label,
  ];

  foreach ([0, 1] as $reverse_field) {
    $contact_type_names = [];
    foreach (crm_core_relationship_load_contact_types($relation, $reverse_field) as $contact_type) {
      $contact_type_names[] = t($contact_type->name);
    }
    $contact_type_names = implode(', ', $contact_type_names);
    if (isset($relation->crm_core_contact)) {
      $form[$reverse_field ? 'destination_contact' : 'source_contact'] = [
        '#title' => $reverse_field ? t('Destination contact name') : t('Source contact name'),
        '#description' => (t('Following contact types allowed: !types.', ['!types' => $contact_type_names])),
        '#type' => 'textfield',
        '#default_value' => !($reverse_field ^ $relation->reverse) ? $relation->crm_core_contact->label() . " [cid:{$relation->crm_core_contact->contact_id}]" : '',
        '#disabled' => !($reverse_field ^ $relation->reverse),
        '#required' => TRUE,
        '#autocomplete_path' => 'crm-core/contact/' . $relation->crm_core_contact->contact_id . '/relationships/add/' . $relation->relation_type . '/' . $reverse_field . '/autocomplete',
      ];
    }
    else {
      $form[$reverse_field ? 'destination_contact' : 'source_contact'] = [
        '#title' => $reverse_field ? t('Destination contact name') : t('Source contact name'),
        '#description' => (t('Following contact types allowed: !types.', ['!types' => $contact_type_names])),
        '#type' => 'textfield',
        '#default_value' => '',
        '#required' => TRUE,
        '#autocomplete_path' => 'crm-core/relationships/' . $relation->relation_type . '/' . $reverse_field . '/autocomplete',
      ];
    }
  }

  $form['uid'] = [
    '#type' => 'value',
    '#value' => $relation->uid,
  ];

  field_attach_form('relation', $relation, $form, $form_state);

  unset($form['endpoints']);

  $form['actions'] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['form-actions']],
    '#weight' => 40,
  ];

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = [];

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save Relationship'),
    '#submit' => $submit + ['crm_core_relationship_ui_add_relationship_form_submit'],
  ];

  // Show Delete button if we edit activity.
  if ($form_state['op'] == 'edit') {
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => ['crm_core_relationship_ui_add_relationship_form_delete'],
    ];
  }

  // We append the validate handler to #validate in case a form callback_wrapper
  // is used to add validate handlers earlier.
  $form['#validate'][] = 'crm_core_relationship_ui_add_relationship_form_validate';

  // Add after build to set proper page Title as it
  // got set by entity_ui_main_form_defaults.
  // @see entity_ui_get_form().
  $form['#after_build'][] = 'crm_core_relationship_form_after_build';

  return $form;
}

/**
 * After build callback for crm_activity_form.
 *
 * Set proper page title.
 */
function crm_core_relationship_form_after_build(&$form, &$form_state) {
  if ($form_state['op'] == 'add') {
    $relation = $form_state['relation'];
    $relation_type = RelationType::load($relation->relation_type);
    drupal_set_title(t('Add new relationship @label', ['@label' => $relation_type->label()]));
  }

  return $form;
}

/**
 * Return a list of links to add relationship for specific CRM contact.
 *
 * @param $contact
 *   Entity object of crm_core_contact type.
 */
function crm_core_relationship_ui_add_relationship($contact) {

  drupal_set_title(t('Add a relationship'));
  $relationship_type_links = [];
  if ($contact) {
    _crm_core_relationships_ui_breadcrmub($contact);
    foreach ([0, 1] as $reverse) {
      $relationship_types = crm_core_relationship_load_relationship_types($contact->type, $reverse);
      foreach ($relationship_types as $relationship_type) {
        if (user_access('create relation entities of bundle ' . $relationship_type->relation_type) || user_access('create relation entities of any contact relationship')) {
          $contact_uri = $contact->uri();
          $href = $contact_uri['path'] . '/relationships/add/' . $relationship_type->relation_type . '/' . $reverse;
          $title = $reverse ? $relationship_type->reverse_label : $relationship_type->label;
          $relationship_type_links[$relationship_type->relation_type] = [
            'title' => $title,
            'href' => $href,
            'localized_options' => [],
            'description' => t('Create %kind relationship.', ['%kind' => $title]),
          ];
        }
      }
    }
  }
  else {
    foreach ([0, 1] as $reverse) {
      foreach (ContactType::loadActive() as $contact_type) {
        $relationship_types = crm_core_relationship_load_relationship_types($contact_type->type, $reverse);
        foreach ($relationship_types as $relationship_type) {
          if (user_access('create relation entities of bundle ' . $relationship_type->relation_type) || user_access('create relation entities of any contact relationship')) {
            $href = 'crm-core/contact/relationship-add/' . $relationship_type->relation_type . '/' . $reverse;
            $title = $reverse ? $relationship_type->reverse_label : $relationship_type->label;
            $relationship_type_links[] = [
              'title' => $title,
              'href' => $href,
              'localized_options' => [],
              'description' => t('Create %kind relationship.', ['%kind' => $title]),
            ];
          }
        }
      }
    }
  }

  $return = '';
  if (!empty($relationship_type_links)) {
    $return = theme('crm_core_contact_add_list', ['content' => $relationship_type_links]);
  }

  return $return;
}

/**
 * Perform validation for add relationship form.
 */
function crm_core_relationship_ui_add_relationship_form_validate($form, &$form_state) {
  $relationship_type = $form_state['values']['relationship_type'];
  $reverse = $form_state['values']['reverse'];
  $source_contact = _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($form_state['values']['source_contact']);
  $destination_contact = _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($form_state['values']['destination_contact']);

  if (!$relationship_type || !is_object($relationship_type)) {
    form_set_error('relationship_type', t('Relationship type is required.'));

    return;
  }

  if (!crm_core_relationship_is_relationship_type($relationship_type)) {
    form_set_error('relationship_type', t('Relationship type is not valid CRM relationship type.'));

    return;
  }

  if (!$source_contact) {
    form_set_error('source_contact', t('Please, input source contact.'));

    return;
  }

  if (!$destination_contact) {
    form_set_error('destination_contact', t('Please, input destination contact.'));

    return;
  }

  $source_bundles = array_keys(crm_core_relationship_load_contact_types($relationship_type, 0));
  if (!in_array($source_contact->type, $source_bundles)) {
    form_set_error('source_contact', t('Please, input source contact of proper type.'));

    return;
  }

  $target_bundles = array_keys(crm_core_relationship_load_contact_types($relationship_type, $relationship_type->directional));
  if (!in_array($destination_contact->type, $target_bundles)) {
    form_set_error('destination_contact', t('Please, input destination contact of proper type.'));

    return;
  }

  if ($source_contact->contact_id == $destination_contact->contact_id) {
    form_set_error($reverse ? 'source_contact' : 'destination_contact', t('Relationship could not be loopback.'));
  }

  if ($relationship_type->r_unique) {
    if (\Drupal::service('entity.repository.relation')->relationExists(
      [
        [
          'entity_type' => 'crm_core_contact',
          'entity_id' => $source_contact->contact_id,
        ],
        [
          'entity_type' => 'crm_core_contact',
          'entity_id' => $destination_contact->contact_id,
        ],
      ],
      $relationship_type->relation_type
    )
    ) {
      form_set_error($reverse ? 'source_contact' : 'destination_contact', t('This relationship exists. It should be unique.'));

      return;
    }
  }

  $source_contact = _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($form_state['values']['source_contact']);
  $destination_contact = _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($form_state['values']['destination_contact']);

  // Create relationship between the order and the node.
  $endpoints = [
    0 => [
      'entity_type' => 'crm_core_contact',
      'entity_id' => $source_contact->contact_id,
      'entity_bundle' => $source_contact->type,
      'r_index' => 0,
      'entity_key' => $source_contact->type . ': ' . $source_contact->contact_id,
    ],
    1 => [
      'entity_type' => 'crm_core_contact',
      'entity_id' => $destination_contact->contact_id,
      'entity_bundle' => $destination_contact->type,
      'r_index' => 1,
      'entity_key' => $destination_contact->type . ': ' . $destination_contact->contact_id,
    ],
  ];
  $relation = Relation::create([$relationship_type->relation_type, $endpoints]);

  $form_state['relation'] = $relation;

  field_attach_form_validate('relation', $relation, $form, $form_state);
}

/**
 * Perform submit for add relationship form. Save relationship.
 */
function crm_core_relationship_ui_add_relationship_form_submit($form, &$form_state) {
  $relationship_type = $form_state['values']['relationship_type'];
  $reverse = $form_state['values']['reverse'];

  $source_contact = _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($form_state['values']['source_contact']);
  $destination_contact = _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($form_state['values']['destination_contact']);

  $relation = $form_state['relation'];
  $relation->save();

  field_attach_submit('relation', $relation, $form, $form_state);

  $redirect_contact = $reverse ? $destination_contact : $source_contact;
  $form_state['redirect'] = 'crm-core/contact/' . $redirect_contact->contact_id . '/relationships/';
}

/**
 * Autocomplete function for add relationship form. Lookup for contacts.
 *
 * @param $crm_core_contact
 *   Entity object of crm_core_contact type.
 * @param $relationship_type
 *   Relation type object.
 * @param $reverse
 *   Whether relation is reversed.
 * @param $string
 *   String CRM contact is started with.
 */
function crm_core_relationship_ui_add_relationship_autocomplete($crm_core_contact, $relationship_type, $reverse, $string) {
  module_load_include('inc', 'crm_core_relationship_ui');

  $matches = [];

  $contact_types = array_keys(crm_core_relationship_load_contact_types($relationship_type, $reverse));

  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'crm_core_contact')
    ->propertyCondition('type', $contact_types, 'IN')
    ->range(0, 20);

  if ($crm_core_contact != NULL) {
    $query->propertyCondition('contact_id', $crm_core_contact->contact_id, '<>');
  }

  $query->addMetaData('match', $string)
    ->addTag(variable_get('crm_core_contact_search_query_tag', 'crm_core_contact_search'));
  $result = $query->execute();
  $contacts = entity_load('crm_core_contact', array_keys($result['crm_core_contact']));
  foreach ($contacts as $contact) {
    $crm_core_contact_title = $contact->label();
    $matches[$crm_core_contact_title . " [cid:$contact->contact_id]"] = $crm_core_contact_title;
  }

  // Return for JS.
  drupal_json_output($matches);
}

/**
 * Extract contact id from the value of autocomplete contact field.
 *
 * @param $string
 *   String that is processed.
 */
function _crm_core_relationship_ui_get_contact_from_autocomplete_field_value($string) {
  $matches = [];
  preg_match('/\[cid:([0-9]+)\]/', $string, $matches);
  if (!array_key_exists(1, $matches) || !is_numeric($matches[1])) {
    return FALSE;
  }
  $contacts = entity_load('crm_core_contact', [$matches[1]]);
  if (empty($contacts)) {
    return FALSE;
  }

  return $contacts[$matches[1]];
}

/**
 * Relationship toggle status callback.
 */
function crm_core_relationship_ui_toggle_relationship_status($relationship_id, $status) {
  $relation = Relation::load($relationship_id);
  $relation->crm_core_relationship_status[LANGUAGE_NONE][0]['value'] = $status ? 1 : 0;
  entity_save('relation', $relation);
  drupal_set_message(t('Relationship status was successfully changed.'));
  $path = drupal_get_destination();
  drupal_goto($path);
}
