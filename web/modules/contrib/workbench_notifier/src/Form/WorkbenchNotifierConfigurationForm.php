<?php

namespace Drupal\workbench_notifier\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workbench_moderation\Entity\ModerationStateTransition;
use Drupal\user\Entity\Role;

/**
 * Implements workbench_notifier configuration form.
 */
class WorkbenchNotifierConfigurationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workbench_notifier_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = $this->getValidRoles();
    $options = [];
    $options = $this->getTransitionDetails();
    foreach ($options as $moderation_state) {
      $moderationDetails = $this->getWorkbenchNotifierDetails($moderation_state['id']);
      $options[$moderation_state['id']]['message'] = $moderationDetails[$moderation_state['id']]['message'];
      if (!empty($moderationDetails[$moderation_state['id']]['roles'])) {
        $options[$moderation_state['id']]['roles'] = $moderationDetails[$moderation_state['id']]['roles'];
      }
    }

    $header = [
      t('Label'),
      t('From'),
      t('To'),
    ];

    foreach ($roles as $rid => $role) {
      $header[] = t("@role", ['@role' => ucwords($role)]);
    }

    $form['transition'] = [
      '#type' => 'details',
      '#title' => t(strtoupper('Transitions')),
      '#open' => TRUE,
    ];
    $form['transition_messages'] = [
      '#type' => 'details',
      '#title' => t(strtoupper('Notification messages')),
      '#open' => TRUE,
    ];
    $form['transition']['list'] = [
      '#type' => 'table',
      '#header' => $header,
    ];
    foreach ($options as $option) {
      $form['transition']['list'][$option['id']] = [
        '#attributes' => [
          'class' => 'workbench_notifier_transitions'
        ],
        'label' => [
          '#type' => 'markup',
          '#markup' => $option['label'],
        ],
        'from' => [
          '#type' => 'markup',
          '#markup' => $option['from'],
        ],
        'to' => [
          '#type' => 'markup',
          '#markup' => $option['to'],
        ],
      ];
      foreach ($roles as $rid => $role) {
        $form['transition']['list'][$option['id']][$rid] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Notify'),
          '#default_value' => !empty($option['roles'][$rid]),
        ];
      }

      $form['transition_messages']['message'][$option['id']] = [
        '#type' => 'textfield',
        '#title' => $this->t(ucfirst($option['from']) . ' To ' . ucfirst($option['to'])),
        '#size' => 60,
        '#maxlength' => 128,
        '#default_value' => $option['message'],
      ];
    }
    $form['transition_messages']['message']['tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node', 'user'],
      '#dialog' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $transition_lists = $form_state->getValue('list');
    foreach ($transition_lists as $key => $transition_list) {
      $moderation = $this->getTransitionDetails();
      $message = $form_state->getValue($key);

      \Drupal::database()->merge('workbench_notifiers')
        ->key(['moderation_id' => $key])
        ->fields([
          'from_name' => $moderation[$key]['from'],
          'to_name' => $moderation[$key]['to'],
          'message' => $message,
        ])
        ->execute();

      $wnid = $this->getWorkbenchNotifierId($key);

      foreach ($transition_list as $rid => $notify) {
        if ($notify != 0) {
          \Drupal::database()->merge('workbench_notifier_roles')
            ->key(['wnid' => $wnid, 'rid' => $rid])
            ->fields([
              'wnid' => $wnid,
              'rid' => $rid,
            ])
            ->execute();
        }
        else {
          \Drupal::database()->delete('workbench_notifier_roles')
            ->condition('wnid', $wnid)
            ->condition('rid', $rid)
            ->execute();
        }
      }
    }
    drupal_set_message($this->t('Configuration details saved'));
  }

  /**
   * Determines the valid roles for moderation.
   *
   * @return valid_roles
   *   Returns the valid roles or an empty array
   */
  private function getValidRoles() {
    $roles = Role::loadMultiple();
    $valid_roles = [];
    foreach ($roles as $role) {
      if ($role->hasPermission('view moderation states') && $role->hasPermission('view any unpublished content')) {
        $valid_roles[$role->id()] = $role->label();
      }
    }
    return $valid_roles;
  }

  /**
   * Load the existing transitions available and store it in the cache.
   *
   * @return moderation
   *   Returns the moderation transitions array
   */
  private function getTransitionDetails() {
    $cid = 'workbench_notifier:' . \Drupal::languageManager()->getCurrentLanguage()->getId();
    if ($cache = \Drupal::cache()->get($cid)) {
      $moderation = $cache->data;
    }
    else {
      foreach (ModerationStateTransition::loadMultiple() as $moderation_state) {
        $moderation[$moderation_state->id()]['id'] = $moderation_state->id();
        $moderation[$moderation_state->id()]['label'] = $moderation_state->label();
        $moderation[$moderation_state->id()]['from'] = $moderation_state->getFromState();
        $moderation[$moderation_state->id()]['to'] = $moderation_state->getToState();
      }
      \Drupal::cache()->set($cid, $moderation);
    }
    return $moderation;
  }

  /**
   * Load the workbench notification id of the moderation.
   *
   * @return wid
   *   Returns the workbench notification id
   */
  private function getWorkbenchNotifierId($moderation_id) {
    $wid = \Drupal::database()->select('workbench_notifiers', 'wn')
      ->fields('wn', ['wnid'])
      ->condition('moderation_id', $moderation_id)
      ->execute()->fetchField();
    return $wid;
  }

  /**
   * Load the moderation details.
   *
   * @param string $moderation
   *   Moderation id of the transition.
   *
   * @return mixed
   *   Returns the workbench notification id
   */
  private function getWorkbenchNotifierDetails($moderation) {
    $query = \Drupal::database()->select('workbench_notifiers', 'wn');
    $query->leftJoin('workbench_notifier_roles', 'wnr', 'wnr.wnid = wn.wnid');

    $query->fields('wn', ['message'])
      ->fields('wnr', ['rid', 'wnid']);

    $query->condition('moderation_id', $moderation);
    $details = $query->execute()->fetchAll();

    $moderationDetails[$moderation]['roles'] = [];
    foreach ($details as $detail) {
      $moderationDetails[$moderation]['message'] = $detail->message;
      if (!is_null($detail->rid)) {
        $moderationDetails[$moderation]['roles'][$detail->rid] = $detail->wnid;
      }
    }
    return $moderationDetails;
  }

}
