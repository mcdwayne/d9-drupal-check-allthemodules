<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\RegionSettingsForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monster_menus\Constants;
use Drupal\node\Entity\NodeType;

class RegionSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_regions';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $all_types = [];
    /** @var NodeType $t */
    foreach (NodeType::loadMultiple() as $t) {
      $all_types[$t->id()] = $t->label();
    }
    natcasesort($all_types);

    $form['regions'] = ['#type' => 'vertical_tabs'];
    _mm_ui_get_regions($regions, $select, FALSE);
    $perms = mm_content_get_perms_for_region();
    foreach ($regions as $region => $data) {
      $form[$region] = [
        '#type' => 'details',
        '#title' => $data['long_name'],
        '#description' => $data['message'],
        '#group' => 'regions',
        '#tree' => TRUE,
      ];
      $types = mm_content_get_allowed_types_for_region($region);
      $form[$region]['types'] = [
        '#type' => 'details',
        '#title' => $this->t('Content types allowed in this region'),
        '#open' => TRUE,
      ];
      if ($region == Constants::MM_UI_REGION_CONTENT) {
        $form[$region]['types']['#description'] = $this->t('All types are always available in the <em>Content</em> region.');
      }
      else {
        $form[$region]['types']['allowed_all'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow all types'),
          '#default_value' => $types === 'all',
        ];
        $form[$region]['types']['allowed_types'] = [
          '#type' => 'select',
          '#multiple' => TRUE,
          '#size' => 10,
          '#default_value' => is_array($types) ? $types : [],
          '#options' => $all_types,
          '#states' => [
            'invisible' => ['#' . Html::cleanCssIdentifier("edit-$region-types-allowed-all") => ['checked' => TRUE]],
          ],
        ];
      }
      $form[$region]['perms'] = [
        '#type' => 'details',
        '#title' => $this->t('Who can add content to this region'),
        '#open' => TRUE,
      ];

      $users = [];
      if (isset($perms[$region]['users'])) {
        foreach ($perms[$region]['users'] as $uid) {
          $users[$uid]['name'] = mm_content_uid2name($uid);
          $users[$uid]['modes'][] = Constants::MM_PERMS_WRITE;
        }
      }

      $groups = [];
      if (isset($perms[$region]['groups'])) {
        foreach ($perms[$region]['groups'] as $gid) {
          $members = mm_content_get_users_in_group($gid, '<br />', FALSE, 20, TRUE, $form);
          if ($members == '') {
            $members = $this->t('(none)');
          }
          $groups[$gid]['name'] = mm_content_get_name($gid);
          $groups[$gid]['members'] = $members;
          $groups[$gid]['modes'][] = Constants::MM_PERMS_WRITE;
        }
      }

      $types = [
        Constants::MM_PERMS_WRITE => [
          $this->t('Use region'),
          'If checked, @class can put content into this region.',
        ],
      ];

      EditContentForm::permissionsForm($form[$region]['perms'], $types, !empty($perms[$region]['everyone']) ? [Constants::MM_PERMS_WRITE] : [], $groups, $users);
      $form[$region]['perms']['table']['#tree'] = TRUE;
      $form[$region]['perms']['table'][1]['group-w-everyone']['#name'] = Html::cleanCssIdentifier($region) . '[perms][table][1][group-w-everyone]';
    }

    $form['_actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
        '#button_type' => 'primary',
      ],
    ];

    mm_static($form, 'settings_perms', 'mm-admin-regions');
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $allowed_types = [];
    $vals =& $form_state->getValues();
    $perms = [];
    foreach (array_keys(mm_content_get_perms_for_region()) as $region) {
      list($groups, $users) = _mm_ui_form_parse_perms($form_state, (object)$vals[$region]['perms'], TRUE);
      $perms['users'][$region] = !empty($users[Constants::MM_PERMS_WRITE]) ? $users[Constants::MM_PERMS_WRITE] : [];
      $perms['groups'][$region] = !empty($groups[Constants::MM_PERMS_WRITE]) ? $groups[Constants::MM_PERMS_WRITE] : [];
      $perms['everyone'][$region] = !empty($vals[$region]['perms']['table'][1]['group-w-everyone']);
      $allowed_types[$region] = $region == Constants::MM_UI_REGION_CONTENT || !empty($vals[$region]['types']['allowed_all']) ? 'all' : array_values($vals[$region]['types']['allowed_types']);
    }
    \Drupal::service('config.factory')->getEditable('monster_menus.settings')
      ->set('nodes.allowed_region_node_types', $allowed_types)
      ->set('nodes.allowed_region_perms', $perms)
      ->save();
  }

}
