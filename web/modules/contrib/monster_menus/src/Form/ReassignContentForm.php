<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\ReassignContentForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReassignContentForm extends FormBase {
  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_reassign_content';
  }

  /**
   * Constructs an object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    _mm_ui_userlist_setup([], $form, 'old_user', $this->t('Old username'), TRUE, '');
    $form['old_user-choose']['#title'] = '';
    unset($form['old_user']['#mm_list_submit_on_add']);

    _mm_ui_userlist_setup([], $form, 'new_user', $this->t('New username'), TRUE, '');
    $form['new_user-choose']['#title'] = '';
    unset($form['new_user']['#mm_list_submit_on_add']);

    $form['migrate_user_content'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Migrate content in personal user space'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'danger',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $old = $form_state->getValue('old_user');
    $new = $form_state->getValue('new_user');
    // Both fields are required, so let the default handler catch that.
    if (!empty($old) && !empty($new)) {
      $old_user = User::load(mm_ui_mmlist_key0($old));
      $new_user = User::load(mm_ui_mmlist_key0($new));
      if (empty($old_user)) {
        $form_state->setErrorByName('old_user', $this->t('The old user was not found.'));
      }
      if (empty($new_user)) {
        $form_state->setErrorByName('new_user', $this->t('The new user was not found.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $old = User::load(mm_ui_mmlist_key0($form_state->getValue('old_user')));
    $new = User::load(mm_ui_mmlist_key0($form_state->getValue('new_user')));

    $node_update = $this->database->update('node_field_data')
      ->fields(['uid' => $new->id()])
      ->condition('uid', $old->id());

    $mmtids = [];
    if (!$form_state->getValue('migrate_user_content') && !empty($old->user_mmtid)) {
      // Exclude nodes on user homepage and children.
      $query = $this->database->select('mm_tree_parents', 'p')
        ->fields('p', ['mmtid'])
        ->distinct();
      $query->condition('p.parent', $old->user_mmtid);
      $mmtids = array_merge([$old->user_mmtid], $query->execute()->fetchCol());
      $node_update->condition('nid', $this->database->select('mm_node2tree', 'm')
        ->fields('m', ['nid'])
        ->condition('m.mmtid', $mmtids, 'NOT IN'), 'IN');
    }

    $query = $this->database->select('mm_tree', 't')
      ->fields('t', ['mmtid']);
    $query->condition('t.uid', $old->id());
    if ($mmtids) {
      $query->condition('t.mmtid', $mmtids, 'NOT IN');
    }
    $result = $query->execute();
    $i = 0;
    foreach ($result as $item) {
      mm_content_update_quick(['uid' => $new->id()], ['mmtid' => $item->mmtid]);
      $i++;
    }
    \Drupal::messenger()->addStatus($this->t('Groups and pages that have switched owners: @count', ['@count' => $i]));

    $num_updated = $node_update->execute();
    \Drupal::messenger()->addStatus($this->t('Nodes that have switched users: @count', ['@count' => $num_updated]));

    // Find "individuals" groups that are about to become empty, so they can be
    // removed from mm_node_write.
    $empty_groups = $this->database->query('SELECT g.gid FROM {mm_node_write} nw ' . 'INNER JOIN {mm_group} g ON g.gid = nw.gid ' . 'WHERE g.gid < 0 AND g.uid = :uid AND (SELECT COUNT(*) FROM {mm_group} WHERE gid = g.gid) = 1', [':uid' => $old->id()])
      ->fetchCol();
    $num_deleted = $this->database->delete('mm_group')
      ->condition('uid', $old->id())
      ->execute();
    \Drupal::messenger()->addStatus($this->t('Number of groups the user has been removed from: @count', ['@count' => $num_deleted]));
    if ($empty_groups) {
      $this->database->delete('mm_node_write')
        ->condition('gid', $empty_groups, 'IN')
        ->execute();
    }

    $vgroups = [];
    foreach (mm_content_get_uids_in_group(NULL, $old->id(), FALSE, TRUE, FALSE) as $gid) {
      $vgroups[] = Link::fromTextAndUrl(mm_content_get_name($gid), mm_content_get_mmtid_url($gid))->toString();
    }
    if ($vgroups) {
      array_unshift($vgroups, $this->t('The user is still a member of these virtual group(s):'));
      \Drupal::messenger()->addStatus(FilteredMarkup::create(implode('<br />', $vgroups)));
    }
  }

}
