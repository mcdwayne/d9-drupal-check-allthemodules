<?php

namespace Drupal\crm_core_user_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\crm_core_contact\Entity\IndividualType;
use Drupal\crm_core_user_sync\UserSyncBatch;

/**
 * Configure crm_core_user_sync settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['crm_core_user_sync.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_core_user_sync_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = user_roles(TRUE);
    $types = IndividualType::loadMultiple();

    $config = $this->config('crm_core_user_sync.settings');
    $rules = $config->get('rules');
    uasort($rules, [$this, 'weightCmp']);

    $form['description'] = [
      '#plain_text' => $this->t('CRM Core User Synchronization can automatically create contact records associated with user accounts under certain conditions.'),
    ];

    $form['auto_sync_user_create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create an associated contact when account is created'),
      '#description' => $this->t('When checked, this checkbox will automatically create new contacts when a new user account is created according to rules listed above. Rules will be processed in order until a new contact is created.'),
      '#default_value' => $config->get('auto_sync_user_create'),
    ];

    $form['contact_load'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load contact related to the current user'),
      '#description' => $this->t('When checked, contact related to the current user will be loaded as part of the user account object in $account->crm_core["contact"]. In certain situations,  loading contact data as part of a user entity can create performance issues (for instance, when there are hundreds of fields associated with each contact). Uncheck this box if it is creating problems with performance.'),
      '#default_value' => $config->get('contact_load'),
    ];

    $form['contact_show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show contact information'),
      '#description' => $this->t('When checked, contact related to the current user will be shown on user profile page. Configurable from "Manage display" page.'),
      '#default_value' => $config->get('contact_show'),
    ];

    $form['rules'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Role'),
        $this->t('Contact type'),
        $this->t('Status'),
        $this->t('Operations'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'rule-weight',
        ],
      ],
      '#empty' => $this->t('No rules configured'),
    ];

    foreach ($rules as $key => $rule) {
      $row = [];
      $row['#attributes']['class'][] = 'draggable';
      $row['#weight'] = $rule['weight'];

      $row['role'] = ['#plain_text' => $roles[$rule['role']]->label()];
      $row['contact_type'] = ['#plain_text' => $types[$rule['contact_type']]->label()];
      $row['enabled'] = ['#plain_text' => $rule['enabled'] ? 'Enabled' : 'Disabled'];
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];

      $row['weight'] = [
        '#type' => 'weight',
        '#title_display' => 'invisible',
        '#default_value' => $rule['weight'],
        '#attributes' => ['class' => ['rule-weight']],
      ];

      $links = & $row['operations']['#links'];
      $links['edit'] = [
        'title' => 'Edit',
        'url' => Url::fromRoute('crm_core_user_sync.rule.edit', ['rule_key' => $key]),
      ];
      $links['delete'] = [
        'title' => 'Delete',
        'url' => Url::fromRoute('crm_core_user_sync.rule.delete', ['rule_key' => $key]),
      ];

      if ($rule['enabled']) {
        $links['disable'] = [
          'title' => 'Disable',
          'url' => Url::fromRoute('crm_core_user_sync.rule.disable', ['rule_key' => $key]),
        ];
      }
      else {
        $links['enable'] = [
          'title' => 'Enable',
          'url' => Url::fromRoute('crm_core_user_sync.rule.enable', ['rule_key' => $key]),
        ];
      }

      $form['rules'][$key] = $row;
    }

    $form['crm_core_user_sync_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Sync Current Users'),
    ];
    $form['crm_core_user_sync_wrapper']['user_sync'] = [
      '#type' => 'submit',
      '#value' => $this->t('Synchronize Users'),
      '#submit' => ['::bulkUserSync'],
    ];

    $form['crm_core_user_sync_wrapper']['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Click this button to apply user synchronization rules to all user accounts that are currently not associated with a contact in the system. It will create an associated contact record for each user according to the rules configured above. Warning: this cannot be undone.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Sets batch for bulk user synchronization.
   */
  public function bulkUserSync(array $form, FormStateInterface $form_state) {
    $operations[] = [[UserSyncBatch::class, 'progress'], []];
    $batch = [
      'operations' => $operations,
      'title' => $this->t('Processing user synchronization'),
      'finished' => [UserSyncBatch::class, 'finished'],
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('crm_core_user_sync.settings');
    $rules = $config->get('rules');

    $rule_values = $form_state->getValue('rules', []);
    if (!empty($rule_values)) {
      foreach ($rule_values as $key => $values) {
        if (!empty($values['weight'])) {
          $rules[$key]['weight'] = $values['weight'];
        }
      }
    }

    uasort($rules, [$this, 'weightCmp']);

    $config
      ->set('rules', $rules)
      ->set('auto_sync_user_create', $form_state->getValue('auto_sync_user_create'))
      ->set('contact_load', $form_state->getValue('contact_load'))
      ->set('contact_show', $form_state->getValue('contact_show'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Weight comparison callback.
   */
  private function weightCmp($a, $b) {
    if ($a['weight'] == $b['weight']) {
      return 0;
    }

    return ($a['weight'] < $b['weight']) ? -1 : 1;
  }

}
