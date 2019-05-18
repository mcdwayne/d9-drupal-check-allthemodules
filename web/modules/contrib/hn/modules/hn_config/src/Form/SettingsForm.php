<?php

namespace Drupal\hn_config\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;

/**
 * Class ConfigForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hn_config.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hn_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hn_config.settings');

    /** @var \Drupal\system\Entity\Menu[] $menus */
    $menus = Menu::loadMultiple();
    $menu_list = [];

    foreach ($menus as $menu) {
      $menu_list[$menu->id()] = $menu->label();
    }

    $form['menus'] = [
      '#type' => 'select',
      '#title' => t('Menus'),
      '#description' => t('Select the menus to be returned in the HN response. Hold CTRL to select multiple'),
      '#options' => $menu_list,
      '#default_value' => $config->get('menus'),
      '#multiple' => TRUE,
    ];

    $default_entities = implode(PHP_EOL, $config->get('entities'));

    $form['entities'] = [
      '#type' => 'textarea',
      '#title' => t('Config entities'),
      '#description' => t('Type in the configuration entity keys to add to the HN response. (One per line)'),
      '#default_value' => $default_entities,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $entity_keys = array_map('trim',
      array_filter(explode(PHP_EOL, $values['entities']))
    );

    foreach ($entity_keys as $entity_key) {
      if (\Drupal::config($entity_key)->isNew()) {
        $form_state->setErrorByName($entity_key, t('Config entity %name does not exist', [
          '%name' => $entity_key,
        ]));
      }
    }

    $form_state->setValue('entities', $entity_keys);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the config.
    $this->config('hn_config.settings')
      ->set('menus', array_keys($values['menus']))
      ->set('entities', $values['entities'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
