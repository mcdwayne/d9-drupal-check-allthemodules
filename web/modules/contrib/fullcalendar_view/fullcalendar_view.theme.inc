<?php

/**
 * @file
 * Theme for Full Calendar views.
 */

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Implement fullcalendar view theme preprocess function.
 *
 * @param array $variables
 *   Template variables.
 */
function template_preprocess_views_view_fullcalendar(array &$variables) {
  global $base_path;

  $view = $variables['view'];
  $style = $view->style_plugin;
  $options = $style->options;
  $fields = $view->field;
  // Get current language.
  $language = \Drupal::languageManager()->getCurrentLanguage();
  if ($language->isDefault()) {
    $variables['language'] = $base_path;
  }
  else {
    $variables['language'] = $base_path . $language->getId() . '/';
  }
  // Current user.
  $user = $variables['user'];
  // CSRF token.
  $token = '';
  if (!$user->isAnonymous()) {
    $token = \Drupal::csrfToken()->get($user->id());
  }
  // New event bundle type.
  $event_bundle_type = $options['bundle_type'];
  $entity_type = $view->getBaseEntityType();
  if ($entity_type->id() === 'node') {
    $add_form = 'node/add/' . $event_bundle_type;
  }
  else {
    $entity_links = $entity_type->get('links');
    if (isset($entity_links['add-form'])) {
      $add_form = str_replace('{' . $entity_type->id() . '}', $event_bundle_type, $entity_links['add-form']);
    }
    elseif (isset($entity_links['add-page'])) {
      $add_form = str_replace('{' . $entity_type->id() . '}', $event_bundle_type, $entity_links['add-page']);
    }
  }

  // Can the user add a new event?
  $dbl_click_to_create = FALSE;
  if (!empty($user) && $user->hasPermission(strtolower("create $event_bundle_type " . $entity_type->getLabel()))) {
    $dbl_click_to_create = TRUE;
  }
  // Pass entity type to twig template.
  $variables['entity_id'] = $entity_type->id();
  // Update options for twig.
  $variables['options'] = $options;
  // Hide the create event link from user who doesn't have the permission
  // or if this feature is turn off.
  $variables['showAddEvent'] = $dbl_click_to_create
    && $options['createEventLink'];

  // Field machine name of start date.
  $start_field = $options['start'];
  // Start date field is critical.
  if (empty($start_field)) {
    return;
  }
  // Field machine name of end date.
  $end_field = $options['end'];
  // Field machine name for event description.
  $des_field = $options['des'];
  // Field machine name of taxonomy field.
  $tax_field = $options['tax_field'];
  // Default date of the calendar.
  switch ($options['default_date_source']) {
    case 'now':
      $default_date = date('Y-m-d');
      break;

    case 'fixed':
      $default_date = $options['defaultDate'];
      break;

    case 'first':
      // Don't do anything, we'll set it below.
      break;
  }
  // Default Language.
  $default_lang = $options['defaultLanguage'];
  // Color for bundle types.
  $color_content = $options['color_bundle'];
  // Color for taxonomies.
  $color_tax = $options['color_taxonomies'];
  // Date fields.
  $start_field_option = $fields[$start_field]->options;
  $end_field_option = empty($end_field) ? NULL : $fields[$end_field]->options;
  // Custom timezone or user timezone.
  $timezone = !empty($start_field_option['settings']['timezone_override']) ?
    $start_field_option['settings']['timezone_override'] : drupal_get_user_timezone();
  // Open a new window for details of an event.
  $title_field = (empty($options['title']) || $options['title'] == 'title') ? 'title' : $options['title'];
  // Calendar entries linked to entity.
  $link_to_entity = FALSE;
  if (isset($fields[$title_field]->options['settings']['link_to_entity'])) {
    $link_to_entity = $fields[$title_field]->options['settings']['link_to_entity'];
  }
  elseif (isset($fields[$title_field]->options['settings']['link'])) {
    $link_to_entity = $fields[$title_field]->options['settings']['link'];
  }
  // Right side buttons.
  $right_buttons = 'month';
  foreach ($options['right_buttons'] as $key => $value) {
    if ($value !== 0) {
      $right_buttons .= ',' . $key;
    }
  }
  $entries = [];

  if (!empty($start_field)) {
    // Timezone conversion service.
    $timezone_service = \Drupal::service('fullcalendar_view.timezone_conversion_service');
    // Save view results into entries array.
    foreach ($view->result as $row) {
      // Set the row_index property used by advancedRender function.
      $view->row_index = $row->index;
      // Days of a week for a recurring event.
      $dow = NULL;
      // Days of a month for a recurring event.
      $dom = NULL;
      $monthly = NULL;
      $weekly = NULL;
      // Recurring range.
      $range = NULL;
      // Result entity of current row.
      $current_entity = $row->_entity;
      // Start field is vital, if it doesn't exist then ignore this entity.
      if (!$current_entity->hasField($start_field)) {
        continue;
      }
      // Monthly recurring.
      if ($current_entity->hasField('field_monthly_event')) {
        $monthly = $current_entity->get('field_monthly_event')->getValue();
      }
      // If the monthly recurring is set, the weekly recurring will be ignored.
      if (!empty($monthly)) {
        $dom = [];
        foreach ($monthly as $day) {
          if (!empty($day['value'])) {
            $dom[] = $day['value'];
          }
        }
      }
      // Weekly recurring.
      elseif ($current_entity->hasField('field_weekly_event')) {
        $weekly = $current_entity->get('field_weekly_event')->getValue();
      }
      if (!empty($weekly)) {
        $dow = [];
        foreach ($weekly as $day) {
          if (isset($day['value'])) {
            // Sunday is 0.
            $dow[] = $day['value'] % 7;
          }
        }

        if (!empty($dow)) {
          $dow = '[' . implode(',', $dow) . ']';
        }
      }
      // Entity id.
      $entity_id = $current_entity->id();
      // Entity bundle type.
      $entity_bundle = $current_entity->bundle();
      // Background color based on taxonomy field.
      if (!empty($tax_field) && $current_entity->hasField($tax_field)) {
        // Event type.
        $event_type = $current_entity->get($tax_field)->target_id;
      }
      // Calendar event start date.
      $start_date = $current_entity->get($start_field)->getValue();
      // Calendar event end date.
      $end_date = empty($end_field) || !$current_entity->hasField($end_field) ? '' : $current_entity->get($end_field)->getValue();
      // Apart from start date and end date field,
      // other fields might be rewritten by
      // the field settgins of the view.
      if ($options['use_entity_fields']) {
        // Description for events. For multiple bundle types,
        // there might be more than one field specified.
        if (!empty($des_field) && is_array($des_field)) {
          foreach ($des_field as $des_field_name) {
            if ($current_entity->hasField($des_field_name)) {
              $des = $current_entity->get($des_field_name)->getValue();
              // We just need only one description text.
              // Once we got it, quit the loop.
              break;
            }
          }
        }
        if (!isset($des)) {
          $des = '';
        }
        if (is_array($des)) {
          $des = reset($des);
          if (isset($des['value'])) {
            $des = $des['value'];
          }
        }
        // Event title.
        if (empty($options['title']) || $options['title'] == 'title') {
          $title = $current_entity->label();
        }
        elseif ($current_entity->hasField($options['title'])) {
          $title = $current_entity->get($options['title'])->value;
        }
        else {
          $title = 'Invalid event title';
        }
      }
      else {
        // Description for events. For multiple bundle types,
        // there might be more than one field specified.
        if (!empty($des_field) && is_array($des_field)) {
          foreach ($des_field as $des_field_name) {
            if ($row->_entity->hasField($des_field_name)) {
              $des_raw = $fields[$des_field_name]->advancedRender($row);
              $des = empty($des_raw) ? '' : $des_raw;
              // We just need only one description text.
              // Once we got it, quit the loop.
              break;
            }
          }
        }
        if (!isset($des)) {
          $des = '';
        }
        if (is_array($des)) {
          $des = reset($des);
          if (isset($des['value'])) {
            $des = $des['value'];
          }
        }
        // Event title.
        if (empty($options['title']) || $options['title'] == 'title') {
          $title = $fields['title']->advancedRender($row);
        }
        elseif ($current_entity->hasField($options['title'])) {
          $title = $fields[$options['title']]->advancedRender($row);
        }
        else {
          $title = 'Invalid event title';
        }
      }
      $entry = [
        'title' => $title,
        'description' => $des,
        'id' => $entity_id,
        'url' => $current_entity->toUrl()->toString(),
      ];

      if (!empty($start_date)) {
        // There might be more than one value for a field,
        // but we only need the first one and ignore others.
        $start_date = reset($start_date)['value'];
        // Examine the field type.
        if ($start_field_option['type'] === 'timestamp') {
          $start_date = intval($start_date);
          $start_date = date(DATE_ATOM, $start_date);
        }
        elseif (strpos($start_field_option['type'], 'datetime') === FALSE && strpos($start_field_option['type'], 'daterange') === FALSE) {
          // This field is not a valid date time field.
          continue;
        }
        // A user who doesn't have the permission can't edit an event.
        if (!$current_entity->access('update')) {
          $entry['editable'] = FALSE;
        }

        // If we don't yet know the default_date (we're configured to use the
        // date from the first row, and we haven't set it yet), do so now.
        if (!isset($default_date)) {
          // Only use the first 10 digits since we only care about the date.
          $default_date = substr($start_date, 0, 10);
        }

        $all_day = (strlen($start_date) < 11) ? TRUE : FALSE;

        if ($all_day) {
          // Recurring event.
          if (!empty($dow) || !empty($dom)) {
            if (empty($options['business_start'])) {
              $business_start = new DrupalDateTime('2018-02-24T08:00:00');
            }
            else {
              $business_start = new DrupalDateTime($options['business_start']);
            }
            $entry['start'] = $business_start->format('H:i');
            $range['start'] = $start_date;
          }
          else {
            $entry['start'] = $start_date;
          }
        }
        else {
          // Recurring event.
          if (!empty($dow) || !empty($dom)) {
            $format = 'H:i';
            $range['start'] = $timezone_service->utcToLocal($start_date, $timezone, 'Y-m-d');
          }
          else {
            $format = DATE_ATOM;
          }
          // By default, Drupal store date time date in UTC timezone.
          // So we need to convert it into user timezone.
          $entry['start'] = $timezone_service->utcToLocal($start_date, $timezone, $format);
        }
      }
      else {
        continue;
      }
      // Cope with end date in the same way as start date above.
      if (!empty($end_date)) {
        if ($end_field_option['type'] === 'timestamp') {
          $end_date = reset($end_date)['value'];
          $end_date = intval($end_date);
          $end_date = date(DATE_ATOM, $end_date);
        }
        elseif (strpos($end_field_option['type'], 'daterange') !== FALSE) {
          $end_date = $end_date[0]['end_value'];
        }
        elseif (strpos($end_field_option['type'], 'datetime') === FALSE) {
          // This field is not a valid date time field.
          $end_date = '';
        }
        else {
          $end_date = reset($end_date)['value'];
        }

        if (!empty($end_date)) {
          $all_day = (strlen($end_date) < 11) ? TRUE : FALSE;
          if ($all_day) {
            $end = new DrupalDateTime($end_date);
            // The end date is inclusive for a all day event,
            // which is not what we want. So we need one day offset.
            $end->modify('+1 day');
            // Recurring event.
            if (!empty($dow) || !empty($dom)) {
              if (!empty($options['business_end'])) {
                $business_end = new DrupalDateTime($options['business_end']);
              }
              else {
                $business_end = new DrupalDateTime('2018-02-24T18:00:00');
              }
              $entry['end'] = $business_end->format('H:i');
              $range['end'] = $end->format('Y-m-d');
            }
            else {
              $entry['end'] = $end->format('Y-m-d');
            }
          }
          else {
            // Recurring event.
            if (!empty($dow) || !empty($dom)) {
              $format = 'H:i';
              $range['end'] = $timezone_service->utcToLocal($end_date, $timezone, 'Y-m-d', '+1 day');
            }
            else {
              $format = DATE_ATOM;
            }

            $entry['end'] = $timezone_service->utcToLocal($end_date, $timezone, $format);
          }
        }
      }
      else {
        // Without end date field, this event can't be resized.
        $entry['durationEditable'] = FALSE;
      }
      // Set the color for this event.
      if (isset($event_type) && isset($color_tax[$event_type])) {
        $entry['backgroundColor'] = $color_tax[$event_type];
      }
      elseif (isset($color_content[$entity_bundle])) {
        $entry['backgroundColor'] = $color_content[$entity_bundle];
      }

      if (!empty($dom)) {
        $entry['dom'] = $dom;
        $entry['ranges'] = [$range];
        // Recurring event is not editable.
        $entry['editable'] = FALSE;
      }
      elseif (!empty($dow)) {
        $entry['dow'] = $dow;
        $entry['ranges'] = [$range];
        // Recurring event is not editable.
        $entry['editable'] = FALSE;
      }
      $entries[] = $entry;
    }

    // Remove the row_index property as we don't it anymore.
    unset($view->row_index);

    // Load tooltip plugin.
    if (!empty($des_field)) {
      $variables['#attached']['library'][] = 'fullcalendar_view/tooltip';
    }
    // Load the fullcalendar js library.
    $variables['#attached']['library'][] = 'fullcalendar_view/fullcalendar';
    // Pass data to js file.
    $variables['#attached']['drupalSettings'] = [
      'fullCalendarView' => $entries,
      'defaultDate' => empty($default_date) ? date('Y-m-d') : $default_date,
      'defaultLang' => $default_lang,
      'languageSelector' => $options['languageSelector'],
      'alloweventOverlap' => $options['alloweventOverlap'],
      'updateConfirm' => $options['updateConfirm'],
      'linkToEntity' => $link_to_entity,
      'eventBundleType' => $event_bundle_type,
      'startField' => $start_field,
      'endField' => $end_field,
      'rightButtons' => $right_buttons,
      'defaultView' => isset($options['default_view']) ? $options['default_view'] : 'month',
      'dblClickToCreate' => $dbl_click_to_create,
      'navLinks' => $options['nav_links'],
      'entityType' => $entity_type->id(),
      'addForm' => isset($add_form) ? $add_form : '',
      'token' => $token,
    ];
  }
}
