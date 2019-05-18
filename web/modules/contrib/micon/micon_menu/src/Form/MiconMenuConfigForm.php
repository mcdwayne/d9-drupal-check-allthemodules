<?php

namespace Drupal\micon_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\micon\Entity\Micon;

/**
 * Class MiconMenuConfigForm.
 *
 * @package Drupal\micon_menu\Form
 */
class MiconMenuConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'micon_menu.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'micon_menu_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('micon_menu.config');
    $form['packages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Icon Packages'),
      '#description' => $this->t('The icon packages that should be made available to menu items. If no packages are selected, all will be made available.'),
      '#options' => Micon::loadActiveLabels(),
      '#default_value' => $config->get('packages'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('micon_menu.config')
      ->set('packages', $form_state->getValue('packages'))
      ->save();

    drupal_flush_all_caches();
  }

}
