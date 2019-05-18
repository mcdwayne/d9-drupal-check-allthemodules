<?php

/**
 * @file
 * Contains \Drupal\drowl_header_slides\Form\DrowlHeaderSlidesSettingsForm.
 */

namespace Drupal\drowl_header_slides\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Administration settings form.
 */
class DrowlHeaderSlidesSettingsForm extends ConfigFormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormID()
  {
    return 'drowl_header_slides_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('drowl_header_slides.settings');
    $menus = Menu::loadMultiple();
    $menuOptions = [];
    if (!empty($menus)) {
      foreach ($menus as $key => $menu) {
        $menuOptions[$key] = $menu->label();
      }
    }
    // TODO: It would be better to have a sort widget for all menus. Otherwise the wrong menu
    // may return its result for the block.
    $selectedMenus = $config->get('menus');
    $form['menus'] = [
      '#type' => 'select',
      '#title' => $this->t('Menus containing header images'),
      '#description' => $this->t('Select the menus to watch for header images.'),
      '#default_value' => !empty($selectedMenus) ? $selectedMenus : ['main'],
      '#options' => $menuOptions,
      '#multiple' => true,
      '#required' => true
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->configFactory->getEditable('drowl_header_slides.settings');
    $form_values = $form_state->getValues();
    $config->set('menus', $form_values['menus'])
      ->save();
    parent::submitForm($form, $form_state);
  }
}
