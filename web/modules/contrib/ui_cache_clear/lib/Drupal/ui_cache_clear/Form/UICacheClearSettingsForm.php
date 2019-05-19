<?php

/**
 * @file
 * Contains \Drupal\ui_cache_clear\Form\UICacheClearSettingsForm.
 */

namespace Drupal\ui_cache_clear\Form;

use Drupal\system\SystemConfigFormBase;

/**
 * Configure pants settings for this site.
 */
class UICacheClearSettingsForm extends SystemConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ui_cache_clear_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('ui_cache_clear.settings');

    // Allow to add shortcut for page/Boost cache clearing.
    if (module_exists('shortcut')) {
      $form['add_shortcut'] = self::showShortcutLink();
    }

    $form['always_clear_page'] = array(
      '#type' => 'checkbox',
      '#title' => t('Automaticaly clear current page cache for anonymous users when use UI Cache Clear links'),
      '#description' => t('When clear Block, Views or Panels cache with UI Cache Clear, additionally clear current Page cache.'),
      '#default_value' => $config->get('always_clear_page'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->configFactory->get('ui_cache_clear.settings')
      ->set('always_clear_page', $form_state['values']['always_clear_page'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
  * Helper method to output "Add Shortcut" link on settings page.
  * @see shortcut_preprocess_page()
  */
 public static function showShortcutLink() {
   $element = array();

   $link = 'admin/config/development/performance/ui_cache_clear/page';
   $shortcut_set = shortcut_current_displayed_set();

   foreach ($shortcut_set->links as $uuid => $shortcut) {
     if ($link == $shortcut['link_path']) {
       $mlid = $shortcut['mlid'];
       break;
     }
   }
   if (empty($mlid)) {
     $query = array(
       'link' => $link,
       'name' => 'Clear this page cache',
     );
     $query += drupal_get_destination();
     $query['token'] = drupal_get_token('shortcut-add-link');

     $link_text = shortcut_set_switch_access() ? t('Add to %shortcut_set shortcuts', array('%shortcut_set' =>  $shortcut_set->label())) : t('Add to shortcuts');
     $link_path = 'admin/config/user-interface/shortcut/manage/' . $shortcut_set->id() . '/add-link-inline';

     $element = array(
       '#type' => 'fieldset',
       '#title' => t('Add shortcut “@link”', array('@link' => t('Clear this page cache'))),
     );
     $element['link'] = array(
       '#attached' => array(
          'css' => array(
            drupal_get_path('module', 'shortcut') . '/shortcut.base.css',
            drupal_get_path('module', 'shortcut') . '/shortcut.theme.css',
          ),
        ),
       '#prefix' => '<div class="add-or-remove-shortcuts add-shortcut">',
       '#type' => 'link',
       '#title' => '<span class="icon">'. t('Add or remove shortcut') .'</span><span class="text">' . $link_text . '</span>',
       '#href' => $link_path,
       '#options' => array('query' => $query, 'html' => TRUE),
       '#suffix' => '</div>',
     );
     $element['after']['#markup'] = '<br />';

   }

   return $element;
 }

}
