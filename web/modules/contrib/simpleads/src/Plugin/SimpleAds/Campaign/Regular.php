<?php

namespace Drupal\simpleads\Plugin\SimpleAds\Campaign;

use Drupal\simpleads\SimpleAdsCampaignBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simpleads\Campaigns;

/**
 * Regular Campign type.
 *
 * @SimpleAdsCampaign(
 *   id = "regular",
 *   name = @Translation("Regular Campaign")
 * )
 */
class Regular extends SimpleAdsCampaignBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    $campaigns = (new Campaigns())->setId($id)->load();
    $form['impressions'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Limit by Impressions'),
      '#description' => $this->t('Selected ads in this campaign will become inactive when the number of impressions reached.'),
    ];
    $form['impressions_limit'] = [
      '#type'        => 'number',
      '#title'       => $this->t('Number of Impressions'),
      '#description' => $this->t('Number of impressions before ads stop appearing.'),
      '#states' => [
        'visible'=> [
          'input[name="impressions"]' => ['checked' => TRUE],
        ]
      ],
    ];
    $form['clicks'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Limit by Clicks'),
      '#description' => $this->t('Selected ads in this campaign will become inactive when the number of clicks reached.'),
    ];
    $form['clicks_limit'] = [
      '#type'        => 'number',
      '#title'       => $this->t('Number of Clicks'),
      '#description' => $this->t('Number of Clicks (unique) before ads stop appearing.'),
      '#states' => [
        'visible'=> [
          'input[name="clicks"]' => ['checked' => TRUE],
        ]
      ],
    ];
    $form['start_date'] = [
      '#type'        => 'datetime',
      '#title'       => $this->t('Start Date'),
      '#description' => $this->t('Campaign Start Date'),
    ];
    $form['end_date'] = [
      '#type'        => 'datetime',
      '#title'       => $this->t('End Date'),
      '#description' => $this->t('Campaign End Date'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function createFormSubmit($options, FormStateInterface $form_state, $type = NULL) {
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function updateFormSubmit($options, FormStateInterface $form_state, $type = NULL, $id = NULL) {
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function activate() {
  }

  /**
   * {@inheritdoc}
   */
  public function deactivate() {

  }

}
