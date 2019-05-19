<?php

namespace Drupal\site_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\site_notifications\SiteNotificationsHelper;

/**
 * Settings form.
 */
class SiteNotificationsSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_notifications_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $checked_types = [];
    $checked_roles = [];
    $settings      = SiteNotificationsHelper::getSettings();

    if (!empty($settings)) {
      foreach ($settings as $value) {
        $checked_types           = $value->content_types;
        $checked_roles           = $value->roles;
        $notify_status           = $value->notify_status;
        $refresh_interval        = $value->refresh_interval;
        $show_notification_count = $value->show_notification_count;
      }

      $checked_types = SiteNotificationsHelper::stringToArray($checked_types);
      $checked_roles = SiteNotificationsHelper::stringToArray($checked_roles);
    }

    $form['notifications'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notifications Settings'),
    ];

    $content_type_checkboxes = [];

    $all_content_types_data = node_type_get_types();
    foreach ($all_content_types_data as $type_id => $type) {
      $content_type_checkboxes[$type_id] = Html::escape($type->label());
    }
    $form['notifications']['content_types'] = [
      '#type'         => 'checkboxes',
      '#title'        => $this->t('Check Content Types for which you want to enable Notifications'),
      '#default_value' => $checked_types,
      '#options'      => $content_type_checkboxes,
      '#description'  => $this->t('All content types are listed above.'),
    ];

    $roles_checkboxes = [];
    $roles_checkboxes = array_map(function ($item) {
      return $item->label();
    }, user_roles());

    $select_counts = [];
    foreach (range(5, 30, 5) as $number) {
      $select_counts[$number] = $number;
    }

    unset($roles_checkboxes['authenticated']);

    $form['notifications']['roles'] = [
      '#type'           => 'checkboxes',
      '#title'          => $this->t('Check User roles to whome you want to enable Notifications'),
      '#default_value'  => $checked_roles,
      '#options'        => $roles_checkboxes,
      '#description'    => $this->t('All roles are listed above.'),
    ];
    $form['notifications']['refresh_interval'] = [
      '#type'           => 'number',
      '#title'          => $this->t('Refresh interval (in ms)'),
      '#default_value'  => $refresh_interval,
      '#size'           => 13,
      '#maxlength'      => 12,
      '#description'    => $this->t('Refresh interval indicates time interval after which notification block contents will be automatically refreshed.<br/>Tip: 1000 ms = 1 second.'),
    ];
    $form['notifications']['show_notification_count'] = [
      '#type'           => 'select',
      '#title'          => $this->t('Select notifications count'),
      '#options'        => $select_counts,
      '#default_value'  => $show_notification_count,
      '#title'          => $this->t('Select number of notifications to be shown on block'),
    ];
    $form['notifications']['notify_status'] = [
      '#type'           => 'checkbox',
      '#default_value'  => $notify_status,
      '#title'          => $this->t('Check if you want to enable notifications.'),
    ];
    $form['notifications']['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Submit'),
    ];
    $form['notifications']['cancel'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Cancel'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('op') != 'Cancel') {

      $content_types           = $form_state->getValue('content_types');
      $roles                   = $form_state->getValue('roles');
      $notify_status           = $form_state->getValue('notify_status');
      $refresh_interval        = $form_state->getValue('refresh_interval');
      $show_notification_count = $form_state->getValue('show_notification_count');

      $selected_content_types = '';
      $i = 0;
      foreach ($content_types as $key => $value) {
        if ($value != '0') {
          if ($i == 0) {
            $selected_content_types = $key;
          }
          else {
            $selected_content_types .= ',' . $key;
          }
          $i++;
        }
      }

      $selected_roles = '';
      $j = 0;
      foreach ($roles as $key1 => $value1) {
        if ($value1 != '0') {
          if ($j == 0) {
            $selected_roles = $key1;
          }
          else {
            $selected_roles .= ',' . $key1;
          }
          $j++;
        }
      }

      // Truncate table.
      SiteNotificationsHelper::truncate();

      $fields = [
        'content_types'             => $selected_content_types,
        'roles'                     => $selected_roles,
        'notify_status'             => $notify_status,
        'refresh_interval'          => $refresh_interval,
        'show_notification_count'   => $show_notification_count,
      ];

      // Update New Configurations.
      SiteNotificationsHelper::insert('site_notifications_settings', $fields);

      drupal_set_message($this->t('Notification settings are saved successfully.'));
    }
    elseif ($form_state->getValue('op') == 'Cancel') {
      $this->redirect('site_notifications.settings_form');
    }
  }

}
