<?php

namespace Drupal\jg_leaderboard\Plugin\Block;

use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\jg_leaderboard\LeaderboardAPI;

/**
 * Class Leaderboard
 *
 * @Block (
 *   id = "leaderbord",
 *   admin_label = @Translation ("Just Giving Leaderboard"),
 *   category = @Translation ("Just Giving Leaderboard"),
 * )
 *
 */
Class Leaderboard extends BlockBase {
  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'envirnoment' => '',
      'api_key'     => '',
      'api_version' => '1',
    ];
  }

  /**
   * @inheritdoc
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config              = $this->getConfiguration();
    $form['api_key']     = [
      '#type'          => 'textfield',
      '#title'         => t('API Key'),
      '#default_value' => isset($config['api_key']) ? $config['api_key'] : '',
      '#required'      => TRUE,
    ];
    $form['event_id']    = [
      '#type'          => 'textfield',
      '#title'         => t('Event id'),
      '#default_value' => isset($config['event_id']) ? $config['event_id'] : '',
      '#required'      => TRUE,
    ];
    $form['envirnoment'] = [
      '#type'    => 'select',
      '#title'   => $this->t('Select Envirnoment'),
      '#options' => [
        'https://api-sandbox.justgiving.com/' => $this->t('Sandbox'),
        'https://api.justgiving.com/'         => $this->t('Production'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('api_key', $form_state->getValue('api_key'));
    $this->setConfigurationValue('envirnoment', $form_state->getValue(['envirnoment']));
    $this->setConfigurationValue('event_id', $form_state->getValue(['event_id']));
  }

  /**
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build() {
    $config                = $this->getConfiguration();
    $client                = [];
    $client['envirnoment'] = $config['envirnoment'];
    $client['api_key']     = $config['api_key'];
    $client['api_version'] = "1";
    $eventId               = $config['event_id'];

    $events  = new LeaderboardAPI($client, $eventId);
    $headers = [
      'headers' => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json'
      ]
    ];

    $client = \Drupal::httpClient();
    //@todo make the other part of the uri dynamic.
    $uri      = $events->getEvent($eventId) . '/pages?page=1&pagesize=100';
    $request  = $client->request('GET', $uri, $headers);
    $response = json_decode($request->getBody());

    // All fundraising pages linked to a specific event.
    $fundraising_pages = $response->fundraisingPages;

    // Sort object array in descending order.
    $sort = function ($a, $b) {
      return $b->totalRaisedOnline - $a->totalRaisedOnline;
    };

    usort($fundraising_pages, $sort);
    $leaderboard_links = [];

    $i = 0;
    foreach ($fundraising_pages as $page) {
      $url             = Url::fromUri(t('http://justgiving.com/') . $page->pageShortName);
      $link_attributes = [
        'attributes' => [
          'class' => [
            'class1',
            'class2'
          ],
        ],
      ];
      $url->setOptions($link_attributes);
      $page_and_raised_amount = $page->pageTitle . ' £' . round($page->raisedAmount);
      $link                   = Link::fromTextAndUrl($page_and_raised_amount, $url);
      $leaderboard_links[]    = $link;

      // Only Display 10 pages.
      if (++$i >= 10) {
        break;
      }
    }

    $build = [
      '#theme'       => 'item_list',
      // Uncomment this if you would like to use custom theme for the leaderboard.
      //'#theme'       => 'event-leaderboard',
      '#items'       => $leaderboard_links,
      '#title'       => 'Event Leaderboard',
      '#Description' => 'Event Leaderboard cuctom block that displays top 10 donors for this event.',
    ];

    return $build;
  }

  /**
   * Block validation handler.
   *
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $client = [];

    $client['envirnoment'] = $form_state->getValue(['envirnoment']);
    $client['api_key']     = $form_state->getValue(['api_key']);
    $client['api_version'] = "1";
    $eventId               = $form_state->getValue(['event_id']);

    $events     = new LeaderboardAPI($client, $eventId);
    $statusCode = $events->eventStatusCode($eventId);
    if ($statusCode !== 200) {
      $form_state->setErrorByName('', t('We could not make a successful call with those details provided. This is the error status code returned: ') . $statusCode);
    }
  }
}
