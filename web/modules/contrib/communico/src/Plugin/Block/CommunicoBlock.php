<?php

namespace Drupal\communico\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a Communico Block.
 *
 * @Block(
 *   id = "communico_block",
 *   admin_label = @Translation("Communico Block"),
 * )
 */
class CommunicoBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Render array of entire events block.
    $build = [
      '#theme' => 'communico_block',
      '#events' => $this->buildCommunicoBlock($config),
    ];

    // No cache for this block.
    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['communico_block_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Types'),
      '#description' => $this->t('Make sure these are a valid event type in Communico. Seperate multiple values with a comma'),
      '#default_value' => isset($config['communico_block_type']) ? $config['communico_block_type'] : '',
    ];

    $form['communico_block_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start Date'),
      '#description' => $this->t('Date you would like to display events starting with in YYYY-MM-DD format, leave blank to always start at the latest days events.'),
      '#default_value' => isset($config['communico_block_start']) ? $config['communico_block_start'] : '',
    ];

    $form['communico_block_end'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End Date'),
      '#description' => $this->t('Date you would like to display events ending with in YYYY-MM-DD format, leave blank to always view 5 days of events.'),
      '#default_value' => isset($config['communico_block_end']) ? $config['communico_block_end'] : '',
    ];

    $form['communico_block_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit'),
      '#description' => $this->t('Limit the number of results returned'),
      '#default_value' => isset($config['communico_block_limit']) ? $config['communico_block_limit'] : '10',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['communico_block_type'] = $form_state->getValue('communico_block_type');
    $this->configuration['communico_block_start'] = $form_state->getValue('communico_block_start');
    $this->configuration['communico_block_end'] = $form_state->getValue('communico_block_end');
    $this->configuration['communico_block_limit'] = $form_state->getValue('communico_block_limit');
  }

  /**
   * Build the communico event block.
   *
   * @param  array $config
   *   Array of block configs.
   *
   * @return array
   *   Array of rendered events.
   */
  protected function buildCommunicoBlock($config) {
    /** @var \Drupal\communico\ConnectorService $connector */
    $connector = \Drupal::service('communico.connector');

    // Retrieve config.
    $communico_config = \Drupal::config('communico.settings');
    if ($config['communico_block_start'] == NULL || $config['communico_block_start'] == '') {
      $config['communico_block_start'] = date('Y-m-d');
    }

    if ($config['communico_block_end'] == NULL || $config['communico_block_end'] == '') {
      $current_date = date('Y-m-d');
      $config['communico_block_end'] = date('Y-m-d', strtotime($current_date . "+7 days"));
    }

    // Utilize connector service to get feed.
    $events = $connector->getFeed($config['communico_block_start'], $config['communico_block_end'], $config['communico_block_type'], $config['communico_block_limit']);

    $rendered_events = [];
    $markup = '';
    $link_url = $communico_config->get('linkurl');

    // Loop events returned by communico feed and render.
    foreach ($events as $event) {
      $full_link = $link_url . '/event/' . $event['eventId'];

      $url = Url::fromUri($full_link);
      $link = Link::fromTextAndUrl(t($event['title']), $url )->toString();

      // Render array for an individual event item.
      $rendered_events[] = [
        '#theme' => 'communico_item',
        '#title_link' => $link,
        '#start_date' => $event['eventStart'],
        '#end_date' => $event['eventEnd'],
        '#location' => $event['locationName'],
        '#room' => $event['roomName'],
      ];
    }

    return $rendered_events;
  }
}
