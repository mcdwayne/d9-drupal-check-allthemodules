<?php

namespace Drupal\bueditor\Plugin\Editor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\editor\Plugin\EditorBase;

/**
 * Defines BUEditor as an Editor plugin.
 *
 * @Editor(
 *   id = "bueditor",
 *   label = "BUEditor",
 *   supports_content_filtering = FALSE,
 *   supports_inline_editing = FALSE,
 *   is_xss_safe = TRUE,
 *   supported_element_types = {
 *     "textarea"
 *   }
 * )
 */
class BUEditor extends EditorBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    $settings['default_editor'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    $bueditor_editors = [];
    foreach (\Drupal::entityTypeManager()->getStorage('bueditor_editor')->loadMultiple() as $bueditor_editor) {
      $bueditor_editors[$bueditor_editor->id()] = $bueditor_editor->label();
    }
    // Default editor
    $form['default_editor'] = [
      '#type' => 'select',
      '#title' => $this->t('BUEditor Editor'),
      '#options' => $bueditor_editors,
      '#default_value' => $settings['default_editor'],
      '#description' => $this->t('Select the default editor for the authorized roles. Editors can be configured at <a href=":url">BUEditor admin page</a>.', [':url' => Url::fromRoute('bueditor.admin')->toString()]),
      '#empty_option' => '- ' . $this->t('Select an editor') . ' -',
    ];
    // Roles editors
    $role_ids = [];
    if ($format_form = $form_state->getCompleteForm()) {
      if (isset($format_form['roles']['#value'])) {
        $role_ids = $format_form['roles']['#value'];
      }
      elseif (isset($format_form['roles']['#default_value'])) {
        $role_ids = $format_form['roles']['#default_value'];
      }
    }
    elseif ($format = $editor->getFilterFormat()) {
      $role_ids = array_keys(filter_get_roles_by_format($format));
    }
    if (count($role_ids) > 1) {
      $form['roles_editors'] = [
        '#type' => 'details',
        '#title' => t('Role specific editors'),
      ];
      $roles = user_roles();
      foreach ($role_ids as $role_id) {
        $form['roles_editors'][$role_id] = [
          '#type' => 'select',
          '#title' => $this->t('Editor for %role', ['%role' => $roles[$role_id]->label()]),
          '#options' => $bueditor_editors,
          '#default_value' => isset($settings['roles_editors'][$role_id]) ? $settings['roles_editors'][$role_id] : '',
          '#empty_option' => '- ' . $this->t('Use the default') . ' -',
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
    $settings = &$form_state->getValue(['editor', 'settings']);
    // Remove empty role editor pairs.
    if (isset($settings['roles_editors'])) {
      $settings['roles_editors'] = array_filter($settings['roles_editors']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    $bueditor_editor = $this->getBUEditorEditor($editor);
    return $bueditor_editor ? $bueditor_editor->getLibraries($editor) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(Editor $editor) {
    $bueditor_editor = $this->getBUEditorEditor($editor);
    return $bueditor_editor ? $bueditor_editor->getJSSettings($editor) : [];
  }

  /**
   * Returns the selected BUEditor Editor entity for an account from editor settings.
   */
  public static function getBUEditorEditor(Editor $editor, AccountInterface $account = NULL) {
    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }
    $id = static::getBUEditorEditorId($editor, $account);
    return $id ? \Drupal::entityTypeManager()->getStorage('bueditor_editor')->load($id) : FALSE;
  }

  /**
   * Returns the selected BUEditor Editor id for an account from editor settings.
   */
  public static function getBUEditorEditorId(Editor $editor, AccountInterface $account) {
    $settings = $editor->getSettings();
    if (!empty($settings['roles_editors'])) {
      // Filter roles in two steps. May avoid a db hit by filter_get_roles_by_format().
      if ($roles_editors = array_intersect_key($settings['roles_editors'], array_flip($account->getRoles()))) {
        if ($roles_editors = array_intersect_key($roles_editors, filter_get_roles_by_format($editor->getFilterFormat()))) {
          return reset($roles_editors);
        }
      }
    }
    return $settings['default_editor'];
  }

}
