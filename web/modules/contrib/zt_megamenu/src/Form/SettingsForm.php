<?php

namespace Drupal\zt_megamenu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Class SettingsForm.
 *
 * @package Drupal\zt_megamenu\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'zt_megamenu.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('zt_megamenu.settings');

    $form['zt_megamenu_menu_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select menu for ZT Megamenu'),
      '#default_value' => $config->get('zt_megamenu_menu_id'),
      '#options' => $this->ztGetMenuList(),
    ];

    $form['zt_megamenu_image_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Machine name of the image field for ZT Megamenu'),
      '#default_value' => $config->get('zt_megamenu_image_machine_name'),
    ];

    $form['zt_megamenu_bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select background color for ZT Megamenu'),
      '#default_value' => $config->get('zt_megamenu_bgcolor'),
      '#attributes' => ['class' => ['jscolor']],
    ];

    $form['zt_megamenu_txtcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select text color for ZT Megamenu'),
      '#default_value' => $config->get('zt_megamenu_txtcolor'),
      '#attributes' => ['class' => ['jscolor']],
    ];

    $form['zt_megamenu_content_bgcolor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Select background color of content for ZT Megamenu'),
      '#default_value' => $config->get('zt_megamenu_content_bgcolor'),
      '#attributes' => ['class' => ['jscolor']],
    ];

    $form['zt_megamenu_opacity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu tab opacity (range 0.1 - 1.0)'),
      '#default_value' => $config->get('zt_megamenu_opacity'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Currently nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('zt_megamenu.settings');
    $config->set('zt_megamenu_menu_id', $form_state->getValue('zt_megamenu_menu_id'))
      ->save();
    $config->set('zt_megamenu_image_machine_name', $form_state->getValue('zt_megamenu_image_machine_name'))
      ->save();
    $config->set('zt_megamenu_bgcolor', $form_state->getValue('zt_megamenu_bgcolor'))
      ->save();
    $config->set('zt_megamenu_txtcolor', $form_state->getValue('zt_megamenu_txtcolor'))
      ->save();
    $config->set('zt_megamenu_opacity', $form_state->getValue('zt_megamenu_opacity'))
      ->save();
    $config->set('zt_megamenu_content_bgcolor', $form_state->getValue('zt_megamenu_content_bgcolor'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return an associative array of the custom menus names.
   */
  private function ztGetMenuList() {
    $all_menus = Menu::loadMultiple();
    $menus = [];
    foreach ($all_menus as $id => $menu) {
      $menus[$id] = $menu->label();
    }
    asort($menus);

    return $menus;
  }

}
