<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\ReorderNodesForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReorderNodesForm extends FormBase {

  private $regions;

  /**
   * Database Service Object.
   *
   * @var Connection
   */
  protected $database;

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

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_ui_node_reorder';
  }

  private function sort($a = NULL, $b = NULL) {
    if (empty($a)) {
      // Initialize.
      $this->regions = array_keys(system_region_list(\Drupal::theme()
        ->getActiveTheme()
        ->getName(), REGIONS_VISIBLE));
      return;
    }

    if ($a->stuck != $b->stuck) {
      return $b->stuck - $a->stuck;
    }
    if ($a->region != $b->region) {
      return array_search($b->region, $this->regions) - array_search($a->region, $this->regions);
    }
    if ($a->weight != $b->weight) {
      return $a->weight - $b->weight;
    }
    return $b->created - $a->created;
  }

  public function buildForm(array $form, FormStateInterface $form_state, MMTree $mm_tree = NULL) {
    mm_static($form, 'mm_ui_node_reorder');

    $mmtid = $mm_tree->id();
    $where = '';
    $hidden = mm_get_node_info(Constants::MM_NODE_INFO_NO_REORDER);
    if ($hidden) {
      $where = " AND n.type NOT IN('" . join("', '", $hidden) . "')";
    }
    $node_sql = mm_content_get_accessible_nodes_by_mmtid_query($mmtid, $count_sql, ', MAX(n.type) AS type, MAX(nfr.title) AS title, MAX(n.region) AS region, MAX(n.weight) AS weight', ' LEFT JOIN {node_field_revision} nfr ON nfr.vid = n.vid', $where);
    $count = $this->database->query($count_sql)->fetchColumn();

    $form['#title'] = $this->t('Reorder contents');
    $form['#tree'] = TRUE;
    if ($count) {
      $form['mmtid'] = [
        '#type' => 'value',
        '#value' => $mmtid,
      ];
      $form['actions'] = ['#type' => 'actions'];
      if ($count > Constants::MM_UI_MAX_REORDER_ITEMS) {
        $form['empty_menu'] = ['#markup' => $this->t('<p>There are too many pieces of content on this @thing to make reordering feasible.</p>', mm_ui_strings(FALSE))];
      }
      else {
        $form['prefix'] = [
          '#markup' => $this->t('<div id="help">To reorder a piece of content, grab the @sample and drag it to a new location. Be sure to <em>Save configuration</em> when done.</div>', ['@sample' => mm_ui_tabledrag_sample()]),
          '#weight' => -1,
        ];
        $form['#suffix'] = $this->t('<p></p><div id="help"><p>If a custom content order is being used, any new contents you add will appear at the top of the list. You may want to return here to change their location, afterward.</p><p>Use the <em>Remove custom ordering and save</em> button to revert to the default, reverse-chronological order. Any changes in region are unaffected by this button.</p></div>');

        $theme = \Drupal::theme()->getActiveTheme()->getName();
        $form['edited_theme'] = [
          '#type' => 'value',
          '#value' => $theme,
        ];

        $all_regions = system_region_list($theme, REGIONS_VISIBLE);
        $form['regions'] = [
          '#type' => 'value',
          '#value' => $all_regions,
        ];

        $q = $this->database->query($node_sql);

        foreach ($q as $row) {
          if (empty($row->region) || !isset($all_regions[$row->region])) {
            $row->region = Constants::MM_UI_REGION_CONTENT;
          }
          $rows[] = $row;
        }
        $this->sort(); // Initialize.
        uasort($rows, [$this, 'sort']);

        $new_weight = 1;
        foreach ($rows as $row) {
          $id = 'nid:' . $row->nid;
          $form['nodes'][$id] = [];
          $form['nodes'][$id]['#attributes'] = ['class' => ['menu-enabled']];
          $title = trim($row->title);
          if (empty($title)) {
            $title = $this->t('Title not provided');
          }
          $form['nodes'][$id]['title']['#markup'] = Link::fromTextAndUrl($title, Url::fromRoute('entity.node.canonical', ['node' => $row->nid]))
            ->toString();
          $form['nodes'][$id]['type']['#markup'] = NodeType::load($row->type)->label();
          $form['nodes'][$id]['weight'] = [
            '#type' => 'weight',
            '#delta' => $count,
            '#default_value' => $new_weight++,
          ];
          $allowed_regions = array_intersect_key($all_regions, array_flip(mm_content_get_allowed_regions_for_user(NULL, $row->type)));
          if (!isset($allowed_regions[$row->region])) {
            // Current region is not allowed, so allow only it (can't leave region)
            $allowed_regions = [$row->region => $all_regions[$row->region]];
          }
          $form['nodes'][$id]['region'] = [
            '#type' => 'select',
            '#default_value' => $row->region,
            '#title_display' => 'invisible',
            '#title' => $this->t('Region for @title', ['@title' => $title]),
            '#options' => $allowed_regions,
          ];
          $form['nodes'][$id]['nid'] = [
            '#type' => 'hidden',
            '#value' => $row->nid,
            '#wrapper_attributes' => ['class' => ['hidden']],
          ];
          if ($row->stuck) {
            $form['nodes'][$id]['stuck'] = [
              '#type' => 'value',
              '#value' => TRUE,
            ];
          }
        }

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save configuration'),
        ];
        $form['actions']['spacer'] = ['#markup' => '&nbsp;&nbsp;&nbsp;&nbsp;'];
      }
      $form['actions']['reorder_normal'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove custom ordering and save'),
      ];
    }
    else {
      $form['empty_menu'] = ['#markup' => $this->t('There is no content on this @thing to reorder.', mm_ui_strings(FALSE))];
    }
    mm_ui_node_reorder($form);
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mmtid = $form_state->getValue('mmtid');
    // Remove everything.
    $this->database->delete('mm_node_reorder')
      ->condition('mmtid', $mmtid)
      ->execute();
    if ($form_state->getTriggeringElement()['#id'] == 'edit-actions-reorder-normal') {
      foreach ($form_state->getValue('nodes') as $section) {
        foreach ($section as $entry) {
          // make sure these are integers
          if (is_array($entry) && isset($entry['nid']) && intval($entry['nid']) && $entry['region'] != Constants::MM_UI_REGION_CONTENT) {
            $this->database->insert('mm_node_reorder')
              ->fields([
                'mmtid' => $mmtid,
                'nid' => intval($entry['nid']),
                'weight' => 0,
                'region' => $entry['region'],
              ])
              ->execute();
          }
        }
      }
      \Drupal::messenger()->addStatus($this->t('Custom ordering has been removed.'));
    }
    else {
      // Drupal's tableorder code produces weights where 0 is the bottom of the
      // list and they get smaller (negative) as they go up. If we were to save
      // the order this way, it would mean that newly-added nodes would get put
      // at the bottom of the list (0), even though they appear at the top when
      // actually rendered. So, find the topmost item and make that 1.
      $min_weight = 0;
      foreach ($form_state->getValue('nodes') as $section) {
        foreach ($section as $entry) {
          // make sure these are integers
          if (is_array($entry) && isset($entry['nid']) && intval($entry['nid'])) {
            $min_weight = min($min_weight, $entry['weight']);
          }
        }
      }
      foreach ($form_state->getValue('nodes') as $section) {
        foreach ($section as $entry) {
          // make sure these are integers
          if (is_array($entry) && isset($entry['nid']) && intval($entry['nid'])) {
            $this->database->insert('mm_node_reorder')
              ->fields([
                'mmtid' => $mmtid,
                'nid' => intval($entry['nid']),
                'weight' => $min_weight < 0 ? $entry['weight'] - $min_weight + 1 : $entry['weight'],
                'region' => $entry['region'] == Constants::MM_UI_REGION_CONTENT ? NULL : $entry['region'],
              ])
              ->execute();
          }
        }
      }
      \Drupal::messenger()->addStatus($this->t('The contents have been reordered.'));
    }
    // Make sure the page draws with the new order
    mm_content_clear_caches();
    mm_content_invalidate_mm_tree_cache($mmtid);
  }

  static public function access(MMTree $mm_tree) {
    $mmtid = $mm_tree->id();
    $perms = mm_content_user_can($mmtid);
    return AccessResult::allowedIf($mmtid > 0 && !$perms[Constants::MM_PERMS_IS_RECYCLED] && $perms[Constants::MM_PERMS_WRITE] && $mmtid != mm_content_users_mmtid() && !mm_content_is_group($mmtid) && !mm_content_is_archive($mmtid) && !mm_content_is_archive($mmtid, TRUE));
  }

}