<?php

namespace Drupal\twitter_profile_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'twitter_widget' widget.
 *
 * @FieldWidget(
 *   id = "twitter_widget",
 *   label = @Translation("Twitter widget"),
 *   field_types = {
 *     "twitter_widget"
 *   }
 * )
 */
class TwitterWidgetWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('twitter_profile_widget.settings');
    if ($config->get('twitter_widget_token') !== 'Credentials Valid') {
      drupal_set_message(t('Credentials for the Twitter API have not been configured or are invalid. Review the <a href=":widget">Twitter widget</a> settings.', [':widget' => '/admin/config/media/twitter_profile_widget']), 'warning');
    }
    $field_name = $items->getName();
    $item = $items[$delta];
    $element['headline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headline'),
      '#description' => $this->t('Optional header that appears above the tweets.'),
      '#default_value' => isset($item->headline) ? $item->headline : 'Latest Tweets',
    ];
    $options = [
      'status' => 'User tweets',
      'timeline' => 'User timeline',
      'favorites' => 'Favorited by user',
      'search' => 'Search (Twitter-wide)',
    ];
    $element['list_type'] = [
      '#type' => 'select',
      '#title' => t('List type'),
      '#options' => $options,
      '#default_value' => isset($item->list_type) ? $item->list_type : 'status',
    ];
    $element['account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User account'),
      '#description' => $this->t('The username (handle) from which to pull tweets.'),
      '#default_value' => isset($item->account) ? $item->account : '',
      '#states' => [
        'invisible' => [
          ':input[name="' . $field_name . '[0][list_type]"]' => ['value' => 'search'],
        ],
      ],
    ];
    $element['search'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#description' => $this->t('A search query, which may include Twitter <a href=":examples" target="blank">query operators</a>. Results are sorted based on Twitter ranking algorithm.', [':examples' => 'https://dev.twitter.com/rest/public/search#query-operators']),
      '#default_value' => isset($item->search) ? $item->search : '',
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . '[0][list_type]"]' => ['value' => 'search'],
        ],
      ],
    ];
    $element['timeline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeline list'),
      '#description' => $this->t('Must already exist in Twitter to return any results. Lists are found at https://twitter.com/[username]/lists'),
      '#default_value' => isset($item->timeline) ? $item->timeline : '',
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . '[0][list_type]"]' => ['value' => 'timeline'],
        ],
      ],
    ];
    $element['count'] = [
      '#type' => 'select',
      '#title' => t('Number of tweets to display'),
      '#options' => array_combine(range(1, 10), range(1, 10)),
      '#default_value' => isset($item->count) ? $item->count : 5,
    ];
    $element['retweets'] = [
      '#type' => 'checkbox',
      '#title' => t('Display retweets'),
      '#default_value' => isset($item->retweets) ? $item->retweets : 1,
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . '[0][list_type]"]' => [
            ['value' => 'status'],
            ['value' => 'timeline'],
          ],
        ],
      ],
    ];
    $element['replies'] = [
      '#type' => 'checkbox',
      '#title' => t('Display replies'),
      '#default_value' => isset($item->replies) ? $item->replies : 1,
      '#states' => [
        'visible' => [
          ':input[name="' . $field_name . '[0][list_type]"]' => [
            ['value' => 'status'],
            ['value' => 'timeline'],
          ],
        ],
      ],
    ];
    $element['view_all'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"View all..." text'),
      '#description' => $this->t('Optional text displayed at the bottom of the widget, linking to Twitter.'),
      '#default_value' => isset($item->view_all) ? $item->view_all : 'View on Twitter',
    ];
    $element['#element_validate'] = [[$this, 'validate']];
    return $element;
  }

  /**
   * Validate the Twitter block parameters.
   */
  public function validate($element, FormStateInterface $form_state) {
    $config = \Drupal::config('twitter_profile_widget.settings');
    if ($config->get('twitter_widget_token') !== 'Credentials Valid') {
      $form_state->setError($element, $this->t('Credentials for the Twitter API have not been configured or are invalid. Review the <a href=":widget">Twitter widget</a> settings.', [':widget' => '/admin/config/media/twitter_profile_widget']));
    }
    $values = $form_state->getValues();
    $fields = $values['field_twitter_profile_widget'][0];
    if ($fields['list_type'] == 'search' && $fields['search'] == '') {
      $form_state->setError($element['search'], $this->t('The "Search term" type requires entering a search parameter.'));
    }
    if ($fields['list_type'] != 'search' && $fields['account'] == '') {
      $form_state->setError($element['account'], $this->t('This Twitter widget type requires that you enter an account handle.'));
    }
    if ($fields['list_type'] == 'timeline' && $fields['timeline'] == '') {
      $form_state->setError($element['timeline'], $this->t('The "User timeline" type requires entering a timeline list.'));
    }
  }

}
