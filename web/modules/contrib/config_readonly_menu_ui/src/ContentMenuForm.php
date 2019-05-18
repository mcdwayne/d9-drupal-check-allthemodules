<?php

namespace Drupal\config_readonly_menu_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Site\Settings;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\menu_ui\MenuForm;

/**
 * Form that only allows to edit content menu items, but no config.
 */
class ContentMenuForm extends MenuForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if (!Settings::get('config_readonly')) {
      return $form;
    }
    // The default menu form allows to edit menu label, description,..., which
    // are config. We only want to keep the menu links part.
    drupal_set_message($this->t('Some parts of this form are read-only and have therefore been disabled.'), 'warning');
    foreach (Element::children($form) as $element) {
      if (!in_array($element, ['links', 'actions'])) {
        $form[$element]['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (Settings::get('config_readonly')) {
      // We do not submit the menu config entity form itself, but only the
      // overview form containing the menu links.
      if (!$this->entity->isNew() || $this->entity->isLocked()) {
        $this->submitOverviewForm($form, $form_state);
      }
    }
    else {
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildOverviewForm(array &$form, FormStateInterface $form_state) {
    $form = parent::buildOverviewForm($form, $form_state);
    if (!Settings::get('config_readonly')) {
      return $form;
    }
    // A same menu can contain content-defined links and config-defined links.
    // If we are on a menu links reordering form, we need to alter the form so
    // that users can change the content menu links' weight but not the config
    // ones...
    $links = &$form['links'];
    $readonly_links_found = FALSE;
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $link = $links[$id]['#item']->link;
        if (!$link instanceof MenuLinkContent) {
          $links[$id]['weight']['#disabled'] = TRUE;
          $links[$id]['enabled']['#disabled'] = TRUE;
          $links[$id]['operations']['#access'] = FALSE;
          $readonly_links_found = TRUE;
        }
      }
    }
    // If the menu contains config links, we need to remove the tabledrag
    // feature because tabledrag.js can change a weight even if it's disabled.
    if ($readonly_links_found) {
      unset($form['links']['#tabledrag']);
    }
    return $form;
  }
}
