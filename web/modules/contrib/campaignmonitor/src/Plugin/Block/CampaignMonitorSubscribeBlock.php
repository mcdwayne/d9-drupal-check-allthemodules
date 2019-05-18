<?php

namespace Drupal\campaignmonitor\Plugin\Block;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Subscribe' block.
 *
 * @Block(
 *   id = "campaignmonitor_subscribe_block",
 *   admin_label = @Translation("Subscribe Block"),
 *   category = @Translation("Campaign Monitor Signup"),
 *   module = "campaignmonitor",
 * )
 */
class CampaignMonitorSubscribeBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $lists = campaignmonitor_get_lists();

    $list_options = [];
    foreach ($lists as $list_id => $list) {
      $list_options[$list_id] = $list['name'];
    }
    // A subscribe block can be for a particular list
    // Or can provide a choice of lists.
    $subscription_options = [
      'single' => $this->t('Single List'),
      'user_select' => $this->t('User selects list(s)'),
    ];

    $form['campaignmonitor'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['campaignmonitor']['list'] = [
      '#type' => 'radios',
      '#options' => $subscription_options,
      '#title' => $this->t('Subscription type'),
      '#description' => $this->t('Single list provides a block where the user subscribes to the list you nominate.
      User select list provides a block with checkboxes for the user to choose from.'),
      '#default_value' => isset($config['list']) ? $config['list'] : [],
      '#attributes' => ['class' => ['list-radios']],
      '#required' => TRUE,
    ];

    $form['campaignmonitor']['list_id'] = [
      '#type' => 'radios',
      '#options' => $list_options,
      '#title' => $this->t('List'),
      '#description' => $this->t('Choose the list for this subscribe block.'),
      '#default_value' => isset($config['list_id']) ? $config['list_id'] : '',
      '#states' => [
        'visible' => [
          '.list-radios' => ['value' => 'single'],
        ],
      ],
    ];

    $form['campaignmonitor']['list_id_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text'),
      '#description' => $this->t('The text to accompany the subscribe block. Leave blank to provide no text. Token
      available: @name = list name.'),
      '#default_value' => isset($config['list_id_text']) ? $config['list_id_text'] : 'Subscribe to the @name list',
      '#states' => [
        'visible' => [
          '.list-radios' => ['value' => 'single'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {

    $subscribe_option = $form_state->getValue(['campaignmonitor', 'list']);
    if ($subscribe_option == 'single') {
      $list_id = $form_state->getValue(['campaignmonitor', 'list_id']);
      if (empty($list_id)) {
        $form_state->setErrorByName('settings[campaignmonitor][list_id]', 'List Selection required');
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['list'] = $form_state->getValue([
      'campaignmonitor',
      'list',
    ]);
    $this->configuration['list_id'] = $form_state->getValue([
      'campaignmonitor',
      'list_id',
    ]);
    $this->configuration['list_id_text'] = $form_state->getValue([
      'campaignmonitor',
      'list_id_text',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $content = \Drupal::formBuilder()
      ->getForm('\Drupal\campaignmonitor\Form\CampaignMonitorSubscribeForm', $config);

    return $content;
  }

}
