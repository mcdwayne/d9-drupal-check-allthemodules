<?php

namespace Drupal\private_taxonomy\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Plugin implementation of the 'private_term_reference' field type.
 *
 * @FieldType(
 *   id = "private_taxonomy_term_reference",
 *   label = @Translation("Private taxonomy term"),
 *   description = @Translation("This field stores a reference to a private term."),
 *   category = @Translation("Reference"),
 *   default_widget = "options_select",
 *   default_formatter = "private_taxonomy_term_reference_link",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class PrivateTaxonomyTermReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'taxonomy_term',
      'options_list_callback' => NULL,
      'allowed_values' => [
        [
          'vocabulary' => '',
          'users' => '',
        ],
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(AccountInterface $account = NULL) {
    // Flatten options firstly, because Possible Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getPossibleOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(AccountInterface $account = NULL) {
    // Flatten options firstly, because Settable Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    if ($callback = $this->getSetting('options_list_callback')) {
      return call_user_func_array($callback, [$this->getFieldDefinition(), $this->getEntity()]);
    }
    else {
      $options = [];
      $allowed_values = $this->getSetting('allowed_values');
      $users = $allowed_values[0]['users'];
      $vocabulary = $allowed_values[0]['vocabulary'];

      switch ($users) {
        case 'all':
          $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
          $query->addTag('translatable');
          $query->addTag('term_access');
          $vid = $vocabulary;
          $query->join('user_term', 'ut', 't.tid = ut.tid');
          $query->join('users_field_data', 'u', 'ut.uid = u.uid');
          $results = $query
            ->condition('t.vid', $vid)
            ->fields('t', ['tid', 'name'])
            ->fields('u', ['name'])
            ->execute();
          $options = [];
          foreach ($results as $option) {
            $options[$option->tid] = $option->name .
              ' (' . $option->u_name . ')';
          }
          break;

        case 'owner':
          $user = \Drupal::currentUser();
          $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
          $query->addTag('translatable');
          $query->addTag('term_access');
          $vid = $vocabulary;
          $query->join('user_term', 'ut', 't.tid = ut.tid AND ut.uid = :uid',
            [':uid' => $user->id()]);
          $options = $query
            ->condition('t.vid', $vid)
            ->fields('t', ['tid', 'name'])
            ->execute()
            ->fetchAllKeyed();
          break;

        default:
          $user = \Drupal::currentUser();
          // This is a role.
          $query = \Drupal::database()->select('users_data', 'u');
          $query->join('user__roles', 'ur', 'ur.entity_id = u.uid');
          $role = substr($users, 1, strlen($users) - 2);
          $uids = $query->condition('ur.roles_target_id', $role)
            ->fields('u', ['uid'])
            ->execute()
            ->fetchCol();
          $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
          $query->addTag('translatable');
          $query->addTag('term_access');
          $vid = $vocabulary;
          $query->join('user_term', 'ut', 't.tid = ut.tid');
          $query->join('users_field_data', 'u', 'ut.uid = u.uid');
          $results = $query->condition('u.uid', $uids, 'IN')
            ->condition('t.vid', $vid)
            ->fields('t', ['tid', 'name'])
            ->fields('u', ['name'])
            ->execute();
          $options = [];
          foreach ($results as $option) {
            $options[$option->tid] = $option->name .
              ' (' . $option->u_name . ')';
          }
          break;

      }
      asort($options);
      return $options;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'taxonomy_term_data',
          'columns' => ['target_id' => 'tid'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $vocabularies = Vocabulary::loadMultiple();
    $vocabulary_options = [];
    foreach ($vocabularies as $vocabulary) {
      if (private_taxonomy_is_vocabulary_private($vocabulary->id())) {
        $vocabulary_options[$vocabulary->id()] = $vocabulary->label();
      }
    }

    $users_options = [];
    $users_options['all'] = 'All';
    $roles = user_roles(TRUE);
    foreach ($roles as $rid => $role) {
      $users_options['-' . $rid . '-'] = '-' . $role->label() . '-';
    }
    $users_options['owner'] = 'Owner';

    $element = [];
    $element['#tree'] = TRUE;

    foreach ($this->getSetting('allowed_values') as $delta => $tree) {
      $element['allowed_values'][$delta]['vocabulary'] = [
        '#type' => 'select',
        '#title' => $this->t('Vocabulary'),
        '#default_value' => $tree['vocabulary'],
        '#options' => $vocabulary_options,
        '#required' => TRUE,
        '#description' => $this->t('The private vocabulary which supplies the options for this field.'),
        '#disabled' => $has_data,
      ];
      $element['allowed_values'][$delta]['users'] = [
        '#type' => 'select',
        '#title' => $this->t('Users'),
        '#default_value' => $tree['users'],
        '#options' => $users_options,
        '#required' => TRUE,
        '#description' => $this->t('Selections are All, Owner (just their terms) or by role which will include their own terms plus any terms belonging to users with the selected role.'),
        '#disabled' => $has_data,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    $options = [];

    return $options;
  }

}
