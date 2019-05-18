<?php

namespace Drupal\responsive_share_buttons\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ResponsiveShareButtonsForm extends ConfigFormBase {
  public function getFormId() {
    return 'responsive_share_buttons_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      'responsive_share_buttons.settings',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('responsive_share_buttons.settings');

    // Get the current networks, sorted by weight.
    $networks = $config->get('networks');
    uasort($networks, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));

    foreach ($networks as $name => $network) {

      $form['networks']['network_active_' . $name] = array(
        '#type' => 'checkbox',
        '#title' => $name,
        '#default_value' => isset($network['active']) ? $network['active'] : FALSE,
      );

      $form['networks']['network_weight_' . $name] = array(
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#default_value' => isset($network['weight']) ? $network['weight'] : 0,
        '#attributes' => array('class' => array('item-row-weight')),
      );
    }

    $form['twitter_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Twitter username'),
      '#default_value' => $config->get('twitter_name', ''),
      '#description' => t('Add a twitter name for tweets to include a via mention'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the existing configuration.
    $config = $this->config('responsive_share_buttons.settings');
    $networks = $config->get('networks');

    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      $network_name = substr($key, 15);

      if (strpos($key, 'network_active') === 0) {
        $networks[$network_name]['active'] = $value;
      }

      if (strpos($key, 'network_weight') === 0) {
        $networks[$network_name]['weight'] = $value;
      }
    }

    $config
      ->set('networks', $networks)
      ->set('twitter_name', $form_state->getValue('twitter_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
