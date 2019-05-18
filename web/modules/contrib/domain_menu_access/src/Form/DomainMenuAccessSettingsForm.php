<?php

namespace Drupal\domain_menu_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

class DomainMenuAccessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_menu_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_menu_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain_menu_access.settings')->get('menu_enabled');
    if (!$config) {
      $config = [];
    }

    $menu = Menu::loadMultiple();
    if (empty($menu)) {
      $form['markup'] = [
        '#markup' => $this->t('Your menu list is empty. Please, try add the menu and return here.'),
      ];
    }
    else {
      $form['description'] = [
        '#markup' => $this->t('Please, select menu for enable control by domain records.'),
      ];
      /** @var \Drupal\system\Entity\Menu $item */
      foreach ($menu as $key => $item) {
        $form[$key] = [
          '#type' => 'checkbox',
          '#title' => $item->label(),
          '#default_value' => in_array($key, $config) ? '1' : '',
          '#description' => $item->getDescription(),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain_menu_access.settings');
    $menu = array_keys(Menu::loadMultiple());
    if (!empty($menu) && !empty($form_state->getValues())) {
      $menu_enabled = [];
      $values = $form_state->getValues();
      foreach ($values as $key => $value) {
        if ($value && in_array($key, $menu)) {
          $menu_enabled[] = $key;
        }
      }
      $config->set('menu_enabled', $menu_enabled);
      $config->save();
    }
    parent::submitForm($form, $form_state);
  }

}
