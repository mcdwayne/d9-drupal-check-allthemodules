<?php

namespace Drupal\loyalist\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoyalistSettingsForm.
 *
 * @ingroup parsely_tag
 */
class LoyalistSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loyalist_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'loyalist.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('loyalist.settings');

    $form['help'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('The settings below define a <em>loyalist</em> in the
        context of this site. Currently, a loyalist is defined as any site 
        visitor who visits <strong>@visits times</strong> in 
        <strong>@interval</strong> with a <strong>@cooldown cooldown</strong> 
        period between visits.', [
          '@cooldown' => \Drupal::service('date.formatter')
            ->formatInterval($config->get('cooldown')),
          '@interval' => \Drupal::service('date.formatter')
            ->formatInterval($config->get('interval')),
          '@visits' => $config->get('visits'),
        ]
      ),
    ];

    $intervals = [
      86400, 86400 * 2, 86400 * 3, 86400 * 4, 86400 * 5, 86400 * 6,
      604800, 604800 * 2, 604800 * 3,
      2592000, 2592000 * 2, 2592000 * 3, 2592000 * 4, 2592000 * 5, 2592000 * 6,
      31536000,
    ];
    $options = [];
    foreach ($intervals as $interval) {
      $options[$interval] = \Drupal::service('date.formatter')
        ->formatInterval(($interval));
    }

    $form['interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Interval'),
      '#description' => $this->t('The amount of time to look at when 
        evaluating the <strong>Number of visits</strong> from a potential 
        loyalist.'),
      '#default_value' => $config->get('interval'),
      '#options' => $options,
    ];

    $form['visits'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of visits'),
      '#description' => $this->t('The number of visits by a single site 
        visitor within the <strong>Interval</strong> to qualify the visitor as 
        a loyalist.'),
      '#default_value' => $config->get('visits'),
      '#min' => 0,
      '#step' => 1,
    ];

    $intervals = [
      60 * 5, 60 * 10, 60 * 15, 60 * 30,
      3600, 3600 * 2, 3600 * 3, 3600 * 4, 3600 * 5, 3600 * 6, 3600 * 12,
      86400, 86400 * 2, 86400 * 3, 86400 * 4, 86400 * 5, 86400 * 6,
      604800,
    ];
    $options = [];
    foreach ($intervals as $interval) {
      $options[$interval] = \Drupal::service('date.formatter')
        ->formatInterval(($interval));
    }

    $form['cooldown'] = [
      '#type' => 'select',
      '#title' => $this->t('Visit cooldown'),
      '#description' => $this->t('Amount of times between page loads before
        considering a page load to be a new <strong>visit</strong>.'),
      '#default_value' => $config->get('cooldown'),
      '#options' => $options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $settings = ['interval', 'visits', 'cooldown'];
    foreach ($settings as $setting) {
      $this->config('loyalist.settings')->set($setting, $values[$setting]);
    }
    $this->config('loyalist.settings')->save();
    parent::submitForm($form, $form_state);
  }

}
