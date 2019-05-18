<?php

/**
 * @file
 * Support functions for installation and updates.
 */

use Drupal\Core\Datetime\Entity\DateFormat;

/**
 * Install the contest_date date format.
 */
function _contest_install_date_format_install() {
  $cfg = (object) \Drupal::config('contest.date_format.contest_date')->get();

  $format = DateFormat::load($cfg->id);

  if ($format) {
    return;
  }
  $data = [
    'id'                  => $cfg->id,
    'label'               => $cfg->label,
    'date_format_pattern' => $cfg->pattern,
    'pattern'             => $cfg->pattern,
  ];
  $format = DateFormat::create($data);

  $format->save();
}

/**
 * Delete the contest_date date format.
 */
function _contest_install_date_format_delete() {
  $cfg = (object) \Drupal::config('contest.date_format.contest_date')->get();

  $format = DateFormat::load($cfg->id);

  if (!$format) {
    return;
  }
  $format->delete();
}

/**
 * Mung the user's form display.
 *
 * @todo Move away from direct database manipulation.
 */
function _contest_install_field_display() {
  $fields = _contest_install_get_fields();
  $name_contest = 'contest.entity_form_display.user.user.default';
  $name_core = 'core.entity_form_display.user.user.default';
  $stmt = "SELECT data FROM {config} WHERE collection = '' AND name = :name";
  $weight = 0;

  // Fetch the data.
  if (db_query("SELECT 1 FROM {config} WHERE collection = '' AND name = :name", [':name' => $name_core])->fetchField()) {
    $data = unserialize(db_query($stmt, [':name' => $name_core])->fetchField());
  }
  else {
    $data = unserialize(db_query($stmt, [':name' => $name_contest])->fetchField());
  }
  // Run away! Run away!
  if (empty($data)) {
    return;
  }
  // Find the max weight + 1.
  foreach ($data['content'] as $field) {
    if (!empty($field['weight']) && intval($field['weight']) >= $weight) {
      $weight = $field['weight'] + 1;
    }
  }
  // Update field display data.
  foreach ($fields as $field => $obj) {
    $data['content'][$field] = [
      'type'                 => $obj->type,
      'weight'               => $weight++,
      'third_party_settings' => [],
      'settings'             => [
        'size'        => $obj->size,
        'placeholder' => '',
      ],
    ];
  }
  $data['content']['user_picture']['weight'] = $weight++;
  $data['content']['language']['weight'] = $weight++;
  $data['content']['contact']['weight'] = $weight++;
  $data['content']['timezone']['weight'] = $weight++;

  // Merge udated settings.
  $key = [
    'collection' => '',
    'name'       => $name_core,
  ];
  $fields = ['data' => serialize($data)];

  db_merge('config')->key($key)->fields(['data' => serialize($data)])->execute();

  $key['name'] = $name_contest;

  db_merge('config')->key($key)->fields(['data' => serialize($data)])->execute();
}

/**
 * The data to set the display on the user's form.
 *
 * @return array
 *   A hash of field setting objects.
 */
function _contest_install_get_fields() {
  return [
    'field_contest_name' => (object) [
      'type' => 'string_textfield',
      'size' => 50,
    ],
    'field_contest_business' => (object) [
      'type' => 'string_textfield',
      'size' => 50,
    ],
    'field_contest_address' => (object) [
      'type' => 'string_textfield',
      'size' => 100,
    ],
    'field_contest_city' => (object) [
      'type' => 'string_textfield',
      'size' => 50,
    ],
    'field_contest_state' => (object) [
      'type' => 'string_textfield',
      'size' => 50,
    ],
    'field_contest_zip' => (object) [
      'type' => 'string_textfield',
      'size' => 10,
    ],
    'field_contest_birthdate' => (object) [
      'type' => 'number',
      'size' => 20,
    ],
    'field_contest_phone' => (object) [
      'type' => 'string_textfield',
      'size' => 20,
    ],
    'field_contest_optin' => (object) [
      'type' => 'boolean_checkbox',
      'size' => 60,
    ],
  ];
}
