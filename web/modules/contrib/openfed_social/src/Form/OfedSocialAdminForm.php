<?php

/**
 * @file
 * Contains \Drupal\ofed_social\Form\OfedSocialAdminForm.
 */

namespace Drupal\ofed_social\Form;

use Drupal\Core\Form\FormBase;

class OfedSocialAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ofed_social_admin_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // We'll create a multichoice selection table, sortable, with several social
    // networks. A theme selection field will also be added to support a smooth
    // migration from a sharethis solution to Openfed Social.
    $networks = \Drupal::config('ofed_social.settings')
      ->get('ofed_social_networks');
    $networks_enabled = \Drupal::config('ofed_social.settings')
      ->get('ofed_social_networks_enabled');
    $options = $enabled = [];
    $counter = 0;
    $form['weight'] = ['#tree' => TRUE];
    foreach ($networks as $network_key => $network_values) {
      $options[$network_key] = $network_key;
      if (isset($networks_enabled[$network_key])) {
        $enabled[] = $network_key;
      }
      $form['weight'][$network_key] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', [
          '@title' => $network_values['label'],
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $counter,
        '#attributes' => [
          'class' => [
            'social-networks-order-weight',
          ],
        ],
      ];
      $form['name'][$network_key] = [
        '#markup' => \Drupal\Component\Utility\Html::escape($network_values['label']),
      ];
      $form['labels'][$network_key] = [
        '#type' => 'hidden',
        '#value' => $network_values['label'],
      ];
      $counter++;
    }
    $form['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enabled languages'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => $enabled,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];
    $form['#theme'] = 'ofed_social_admin_form';

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $submissions = $form_state->getValues();
    $existing_networks_settings = \Drupal::config('ofed_social.settings')
      ->get('ofed_social_networks');
    $networks = $networks_enabled = [];
    // Sorting by weight will ensure the right order when saving the variable.
    asort($submissions['weight']);
    foreach ($submissions['weight'] as $network_key => $weight) {
      // We'll add an extra variable so we can easily get the enabled networks.
      if ($submissions['enabled'][$network_key]) {
        $networks_enabled[$network_key] = [
          'label' => $submissions[$network_key],
        ];
      }
      // We've to update the full network list with the current sort order.
      $networks[$network_key] = $existing_networks_settings[$network_key];
    }
    \Drupal::configFactory()
      ->getEditable('ofed_social.settings')
      ->set('ofed_social_networks_enabled', $networks_enabled)
      ->save();
    \Drupal::configFactory()
      ->getEditable('ofed_social.settings')
      ->set('ofed_social_networks', $networks)
      ->save();
    drupal_set_message(t('Configuration saved.'));
    return;
  }
}

