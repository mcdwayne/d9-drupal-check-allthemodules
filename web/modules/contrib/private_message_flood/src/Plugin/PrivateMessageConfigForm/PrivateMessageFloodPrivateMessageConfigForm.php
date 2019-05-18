<?php

namespace Drupal\private_message_flood\Plugin\PrivateMessageConfigForm;

use Drupal\Core\Form\FormStateInterface;
use Drupal\private_message\Plugin\PrivateMessageConfigForm\PrivateMessageConfigFormBase;
use Drupal\private_message\Plugin\PrivateMessageConfigForm\PrivateMessageConfigFormPluginInterface;

/**
 * Adds Private Message Flood settings to the Private Message config page.
 *
 * @PrivateMessageConfigForm(
 *   id = "private_message_flood_settings",
 *   name = @Translation("Private Message Flood Protection settings"),
 * )
 */
class PrivateMessageFloodPrivateMessageConfigForm extends PrivateMessageConfigFormBase implements PrivateMessageConfigFormPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormStateInterface $formState) {

    $form['header'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Flood protection limits the number of post users can make in a given period of time. You can apply flood protection on a per-role basis below. Priority is given to roles from top to bottom. The highest priority role a user is found to have, will be checked against the flood protection settings for that role when determining if they have flooded the private message system.'),
    ];

    $group_class = 'group-order-weight';

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $items = [];
    $weight_level = 0;
    foreach ($roles as $role_id => $role_name) {
      $config = $this->configFactory->get('private_message_flood.role.' . $role_id);

      $weight = $config->get('weight');
      if (empty($weight)) {
        $weight = $weight_level;
        $weight_level++;
      }

      $items[$role_id] = [
        'label' => $role_name,
        'weight' => $weight,
        'elements' => [
          'type' => [
            '#type' => 'select',
            '#title' => $this->t('Limit'),
            '#options' => [
              '' => $this->t('-- SELECT --'),
              'thread' => $this->t('Threads'),
              'post' => $this->t('Posts'),
            ],
            '#default_value' => $config->get('type') ? $config->get('type') : '',
            '#description' => $this->t('Choose which should be limited: the number of threads, or the number of posts'),
            '#attributes' => [
              'class' => ['pm_limit_type_select'],
            ],
            '#attached' => [
              'library' => ['private_message_flood/limit_type_element'],
            ],
          ],
          'limit' => [
            '#type' => 'number',
            '#title' => $this->t('Maxium number of <span class="pm_limit_type_wrapper">messages/threads</span> allowed'),
            '#description' => $this->t('Leave empty to disable flood protection for this role'),
            '#default_value' => $config->get('limit'),
            '#states' => [
              'invisible' => [
                '#edit-private-message-flood-settings-roles-' . $role_id . '-values-details-elements-type' => ['value' => ''],
              ],
            ],
          ],
          'duration' => [
            '#type' => 'duration',
            '#title' => $this->t('Duration'),
            '#granularity' => 'y:d:m:h:i:s',
            '#default_value' => $config->get('duration'),
            '#states' => [
              'invisible' => [
                [
                  ['#edit-private-message-flood-settings-roles-' . $role_id . '-values-details-elements-type' => ['value' => '']],
                  'or',
                  ['#edit-private-message-flood-settings-roles-' . $role_id . '-values-details-elements-limit' => ['value' => '']],
                ],
              ],
            ],
          ],
        ],
      ];
    }

    $form['roles'] = [
      '#type' => 'table',
      '#caption' => $this->t('Roles'),
      '#header' => [
        $this->t('Label'),
        $this->t('Values'),
        $this->t('Weight'),
      ],
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => $group_class,
        ],
      ],
    ];

    // Build rows.
    foreach ($items as $key => $value) {
      $form['roles'][$key]['#attributes']['class'][] = 'draggable';
      $form['roles'][$key]['#weight'] = $value['weight'];

      // Label col.
      $form['roles'][$key]['label'] = [
        '#plain_text' => $value['label'],
      ];

      // Values col.
      $form['roles'][$key]['values'] = [
        'details' => [
          '#type' => 'details',
          '#title' => $this->t('Click to expand'),
          '#open' => FALSE,
          'elements' => $value['elements'],
        ],
      ];

      // Weight col.
      $form['roles'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $value['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $value['weight'],
        '#attributes' => ['class' => [$group_class]],
      ];
    }

    uasort($form['roles'], ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    foreach ($formState->getValue(['private_message_flood_settings', 'roles']) as $rid => $data) {
      if (isset($data['values'], $data['values']['details'], $data['values']['details']['elements'], $data['values']['details']['elements']['type'])) {
        if (!empty($data['values']['details']['elements']['type']) && empty($data['values']['details']['elements']['limit'])) {
          $formState->setError($form['private_message_flood_settings']['roles'][$rid]['values']['details']['elements']['limit'], $this->t('Please enter the post limit for the %role role', ['%role' => $form['private_message_flood_settings']['roles'][$rid]['label']['#plain_text']]));
        }
        elseif (!empty($data['values']['details']['elements']['limit']) && empty($data['values']['details']['elements']['duration'])) {
          $formState->setError($form['private_message_flood_settings']['roles'][$rid]['values']['details']['elements']['duration'], $this->t('Please enter the duration for the %role role', ['%role' => $form['private_message_flood_settings']['roles'][$rid]['label']['#plain_text']]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array $values) {

    foreach ($values['roles'] as $rid => $data) {
      $this->configFactory->getEditable('private_message_flood.role.' . $rid)
        ->set('limit', $data['values']['details']['elements']['limit'])
        ->set('type', $data['values']['details']['elements']['type'])
        ->set('duration', $data['values']['details']['elements']['duration'])
        ->set('weight', $data['weight'])
        ->save();
    }
  }

}
