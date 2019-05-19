<?php

namespace Drupal\social_feed_aggregator\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\CronInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with examples on how to use cron.
 */
class SocialPostSettingsForm extends ConfigFormBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, CronInterface $cron, StateInterface $state) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->cron = $cron;
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('cron'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_feed_aggregator';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_feed_aggregator.settings');

    $next_execution = $this->state->get('social_feed_aggregator.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : REQUEST_TIME;

    $args = [
      '%time' => date_iso8601($this->state->get('social_feed_aggregator.next_execution')),
      '%seconds' => $next_execution - REQUEST_TIME,
    ];
    $form['status']['last'] = [
      '#type' => 'item',
      '#markup' => $this->t('The Social Feed Aggregator will next execute the first time the cron runs after %time (%seconds seconds from now)', $args),
    ];

    /**
     * Facebook.
     */
    $facebook_count = $config->get('facebook_count');

    if(is_null($facebook_count)) {
      $config->set('facebook_count', 1)
        ->save();

      $facebook_count = 1;
    }

    $form['facebook'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook settings'),
      '#open' => TRUE,
      '#prefix' => '<div id="facebook-wrapper" data-count="'.$facebook_count.'">',
      '#suffix' => '</div>',
    ];

    for($i = 0; $i < $facebook_count; $i++) {
      $form['facebook'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Account #'.($i + 1)),
        '#open' => TRUE,
      ];

      $form['facebook'][$i]['facebook_'.$i.'_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled?'),
        '#default_value' => $config->get('facebook.'.$i.'.enabled'),
      ];

      $form['facebook'][$i]['facebook_'.$i.'_username'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $config->get('facebook.'.$i.'.username'),
        '#required' => $config->get('facebook.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['facebook'][$i]['facebook_'.$i.'_app_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('App ID'),
        '#default_value' => $config->get('facebook.'.$i.'.app_id'),
        '#required' => $config->get('facebook.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['facebook'][$i]['facebook_'.$i.'_app_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('App Secret'),
        '#default_value' => $config->get('facebook.'.$i.'.app_secret'),
        '#required' => $config->get('facebook.'.$i.'.enabled') ? TRUE : FALSE,
        '#description' => $this->t('Obtain your Facebook app details by following the guide at <a href="@link">@link</a>', [
          '@link' => 'https://developers.facebook.com/docs/apps/register',
        ]),
      ];
    }

    $form['facebook']['actions']['add_account'] = [
      '#type' => 'submit',
      '#value' => t('Add Facebook Account'),
      '#submit' => ['::addFacebook'],
      '#ajax' => [
        'callback' => '::addFacebookCallback',
        'wrapper' => 'facebook-wrapper',
      ],
    ];

    if($facebook_count > 1) {
      $form['facebook']['actions']['remove_account'] = [
        '#type' => 'submit',
        '#value' => t('Remove Facebook Account'),
        '#submit' => ['::removeFacebook'],
        '#ajax' => [
          'callback' => '::addFacebookCallback',
          'wrapper' => 'facebook-wrapper',
        ],
      ];
    }

    /**
     * Twitter.
     */
    $twitter_count = $config->get('twitter_count');

    if(is_null($twitter_count)) {
      $config->set('twitter_count', 1)
        ->save();

      $twitter_count = 1;
    }

    $form['twitter'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter settings'),
      '#open' => TRUE,
      '#prefix' => '<div id="twitter-wrapper" data-count="'.$twitter_count.'">',
      '#suffix' => '</div>',
    ];

    for($i = 0; $i < $twitter_count; $i++) {
      $form['twitter'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Account #'.($i + 1)),
        '#open' => TRUE,
      ];

      $form['twitter'][$i]['twitter_'.$i.'_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled?'),
        '#default_value' => $config->get('twitter.'.$i.'.enabled'),
      ];

      $form['twitter'][$i]['twitter_'.$i.'_username'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $config->get('twitter.'.$i.'.username'),
        '#required' => $config->get('twitter.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['twitter'][$i]['twitter_'.$i.'_consumer_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Consumer Key'),
        '#default_value' => $config->get('twitter.'.$i.'.consumer_key'),
        '#required' => $config->get('twitter.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['twitter'][$i]['twitter_'.$i.'_consumer_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Consumer Secret'),
        '#default_value' => $config->get('twitter.'.$i.'.consumer_secret'),
        '#required' => $config->get('twitter.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['twitter'][$i]['twitter_'.$i.'_access_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access Token'),
        '#default_value' => $config->get('twitter.'.$i.'.access_token'),
        '#required' => $config->get('twitter.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['twitter'][$i]['twitter_'.$i.'_access_token_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access Token Secret'),
        '#default_value' => $config->get('twitter.'.$i.'.access_token_secret'),
        '#required' => $config->get('twitter.'.$i.'.enabled') ? TRUE : FALSE,
        '#description' => $this->t('Obtain your Twitter app details by following the guide at <a href="@link">@link</a>', [
          '@link' => 'https://dev.twitter.com/oauth/overview/application-owner-access-tokens',
        ]),
      ];
    }

    $form['twitter']['actions']['add_account'] = [
      '#type' => 'submit',
      '#value' => t('Add Twitter Account'),
      '#submit' => ['::addTwitter'],
      '#ajax' => [
        'callback' => '::addTwitterCallback',
        'wrapper' => 'twitter-wrapper',
      ],
    ];

    if($twitter_count > 1) {
      $form['twitter']['actions']['remove_account'] = [
        '#type' => 'submit',
        '#value' => t('Remove Twitter Account'),
        '#submit' => ['::removeTwitter'],
        '#ajax' => [
          'callback' => '::addTwitterCallback',
          'wrapper' => 'twitter-wrapper',
        ],
      ];
    }

    /**
     * Instagram.
     */
    $instagram_count = $config->get('instagram_count');

    if(is_null($instagram_count)) {
      $config->set('instagram_count', 1)
        ->save();

      $instagram_count = 1;
    }

    $form['instagram'] = [
      '#type' => 'details',
      '#title' => $this->t('Instagram settings'),
      '#open' => TRUE,
      '#prefix' => '<div id="instagram-wrapper" data-count="'.$instagram_count.'">',
      '#suffix' => '</div>',
    ];

    for($i = 0; $i < $instagram_count; $i++) {
      $form['instagram'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Account #'.($i + 1)),
        '#open' => TRUE,
      ];

      $form['instagram'][$i]['instagram_'.$i.'_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled?'),
        '#default_value' => $config->get('instagram.'.$i.'.enabled'),
      ];

      $form['instagram'][$i]['instagram_'.$i.'_username'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username'),
        '#default_value' => $config->get('instagram.'.$i.'.username'),
        '#required' => $config->get('instagram.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['instagram'][$i]['instagram_'.$i.'_client_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client ID'),
        '#default_value' => $config->get('instagram.'.$i.'.client_id'),
        '#required' => $config->get('instagram.'.$i.'.enabled') ? TRUE : FALSE,
      ];

      $form['instagram'][$i]['instagram_'.$i.'_access_token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Access Token'),
        '#default_value' => $config->get('instagram.'.$i.'.access_token'),
        '#required' => $config->get('instagram.'.$i.'.enabled') ? TRUE : FALSE,
        '#description' => $this->t('Obtain your Instagram app details by following the guide at <a href="@link">@link</a>', [
          '@link' => 'https://www.instagram.com/developer/authentication/',
        ]),
      ];
    }

    $form['instagram']['actions']['add_account'] = [
      '#type' => 'submit',
      '#value' => t('Add Instagram Account'),
      '#submit' => ['::addInstagram'],
      '#ajax' => [
        'callback' => '::addInstagramCallback',
        'wrapper' => 'instagram-wrapper',
      ],
    ];

    if($instagram_count > 1) {
      $form['instagram']['actions']['remove_account'] = [
        '#type' => 'submit',
        '#value' => t('Remove Instagram Account'),
        '#submit' => ['::removeInstagram'],
        '#ajax' => [
          'callback' => '::addInstagramCallback',
          'wrapper' => 'instagram-wrapper',
        ],
      ];
    }

    /**
     * CRON.
     */
    if ($this->currentUser->hasPermission('administer site configuration')) {
      $form['cron_run'] = [
        '#type' => 'details',
        '#title' => $this->t('Run cron manually'),
        '#open' => TRUE,
      ];
      $form['cron_run']['cron_trigger']['actions'] = ['#type' => 'actions'];
      $form['cron_run']['cron_trigger']['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Run cron now'),
        '#submit' => [[$this, 'cronRun']],
      ];
    }

    $form['configuration'] = [
      '#type' => 'details',
      '#title' => $this->t('Schedule Cron'),
      '#open' => TRUE,
    ];
    $form['configuration']['social_feed_aggregator_interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron interval'),
      '#description' => $this->t('Time after which cron will respond to a processing request.'),
      '#default_value' => $config->get('cron.interval'),
      '#options' => [
        60 => $this->t('1 minute'),
        300 => $this->t('5 minutes'),
        600 => $this->t('10 minutes'),
        900 => $this->t('15 minutes'),
        1800 => $this->t('30 minutes'),
        3600 => $this->t('1 hour'),
        21600 => $this->t('6 hours'),
        86400 => $this->t('1 day'),
      ],
    ];

    $allowed_formats = filter_formats();
    foreach (filter_formats() as $format_name => $format) {
       $allowed_formats[$format_name] = $format->label();
    }

    $form['formats'] = [
      '#type' => 'details',
      '#title' => $this->t('Post Format'),
      '#open' => TRUE,
    ];

    $form['formats']['formats_post_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Post format'),
      '#default_value' => $config->get('formats.post_format'),
      '#options' => $allowed_formats
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Facebook.
   */
  public function addFacebook(array &$form, FormStateInterface $form_state) {
    $facebook_count = $this->config('social_feed_aggregator.settings')
      ->get('facebook_count');

    $this->config('social_feed_aggregator.settings')
      ->set('facebook_count', ($facebook_count + 1))
      ->save();

    $form_state->setRebuild();
  }

  public function addFacebookCallback(array &$form, FormStateInterface $form_state) {
    return $form['facebook'];
  }

  public function removeFacebook(array &$form, FormStateInterface $form_state) {
    $facebook_count = $this->config('social_feed_aggregator.settings')
      ->get('facebook_count');

    $this->config('social_feed_aggregator.settings')      
      ->delete('facebook.'.$facebook_count)
      ->set('facebook_count', ($facebook_count - 1))
      ->save();

    $form_state->setRebuild();
  }

  /**
   * Twitter.
   */
  public function addTwitter(array &$form, FormStateInterface $form_state) {
    $twitter_count = $this->config('social_feed_aggregator.settings')
      ->get('twitter_count');

    $this->config('social_feed_aggregator.settings')
      ->set('twitter_count', ($twitter_count + 1))
      ->save();

    $form_state->setRebuild();
  }

  public function addTwitterCallback(array &$form, FormStateInterface $form_state) {
    return $form['twitter'];
  }

  public function removeTwitter(array &$form, FormStateInterface $form_state) {
    $twitter_count = $this->config('social_feed_aggregator.settings')
      ->get('twitter_count');

    $this->config('social_feed_aggregator.settings')
      ->delete('twitter.'.$twitter_count)
      ->set('twitter_count', ($twitter_count - 1))
      ->save();

    $form_state->setRebuild();
  }

  /**
   * Instagram.
   */
  public function addInstagram(array &$form, FormStateInterface $form_state) {
    $instagram_count = $this->config('social_feed_aggregator.settings')
      ->get('instagram_count');

    $this->config('social_feed_aggregator.settings')
      ->set('instagram_count', ($instagram_count + 1))
      ->save();

    $form_state->setRebuild();
  }

  public function addInstagramCallback(array &$form, FormStateInterface $form_state) {
    return $form['instagram'];
  }

  public function removeInstagram(array &$form, FormStateInterface $form_state) {
    $instagram_count = $this->config('social_feed_aggregator.settings')
      ->get('instagram_count');

    $this->config('social_feed_aggregator.settings')
      ->delete('instagram.'.$instagram_count)
      ->set('instagram_count', ($instagram_count - 1))
      ->save();

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Extract the values submitted by the user.
    $values = $form_state->getValues();

    /**
     * Facebook.
     */
    $facebook_count = $this->config('social_feed_aggregator.settings')
      ->get('facebook_count');

    for($i = 0; $i < $facebook_count; $i++) {
      if($values['facebook_'.$i.'_enabled'] == 1) {
        if(empty($values['facebook_'.$i.'_username']) || empty($values['facebook_'.$i.'_app_id']) || empty($values['facebook_'.$i.'_app_secret'])) {
          $form_state->setErrorByName('facebook_'.$i.'_enabled', $this->t('All details are needed if enabled.'));
        }
      }
    }

    /**
     * Twitter.
     */
    $twitter_count = $this->config('social_feed_aggregator.settings')
      ->get('twitter_count');

    for($i = 0; $i < $twitter_count; $i++) {
      if($values['twitter_'.$i.'_enabled'] == 1) {
        if(empty($values['twitter_'.$i.'_enabled']) || empty($values['twitter_'.$i.'_username']) || empty($values['twitter_'.$i.'_consumer_key']) || empty($values['twitter_'.$i.'_consumer_secret']) || empty($values['twitter_'.$i.'_access_token']) || empty($values['twitter_'.$i.'_access_token_secret'])) {
          $form_state->setErrorByName('twitter_'.$i.'_enabled', $this->t('All details are needed if enabled.'));
        }
      }
    }

     /**
      * Instagram.
      */
      $instagram_count = $this->config('social_feed_aggregator.settings')
        ->get('instagram_count');

      for($i = 0; $i < $instagram_count; $i++) {
        if($values['instagram_'.$i.'_enabled'] == 1) {
          if(empty($values['instagram_'.$i.'_username']) || empty($values['instagram_'.$i.'_client_id']) || empty($values['instagram_'.$i.'_access_token'])) {
            $form_state->setErrorByName('instagram_'.$i.'_enabled', $this->t('All details are needed if enabled.'));
          }
        }
      }

    parent::validateForm($form, $form_state);
  }

  /**
   * Allow user to directly execute cron, optionally forcing it.
   */
  public function cronRun(array &$form, FormStateInterface &$form_state) {

    // Use a state variable to signal that cron was run manually from this form.
    $this->state->set('social_feed_aggregator.next_execution', 0);
    $this->state->set('social_feed_aggregator_show_status_message', TRUE);
    if ($this->cron->run()) {
      drupal_set_message($this->t('Cron ran successfully.'));
    }
    else {
      drupal_set_message($this->t('Cron run failed.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    /**
     * Facebook.
     */
    $facebook_count = $this->config('social_feed_aggregator.settings')
      ->get('facebook_count');

    for($i = 0; $i < $facebook_count; $i++) {
      $this->config('social_feed_aggregator.settings')
        ->set('facebook.'.$i.'.enabled', $values['facebook_'.$i.'_enabled'])
        ->set('facebook.'.$i.'.username', $values['facebook_'.$i.'_username'])
        ->set('facebook.'.$i.'.app_id', $values['facebook_'.$i.'_app_id'])
        ->set('facebook.'.$i.'.app_secret', $values['facebook_'.$i.'_app_secret']);
    }

    /**
     * Twitter.
     */
    $twitter_count = $this->config('social_feed_aggregator.settings')
      ->get('twitter_count');

    for($i = 0; $i < $twitter_count; $i++) {
      $this->config('social_feed_aggregator.settings')
        ->set('twitter.'.$i.'.enabled', $values['twitter_'.$i.'_enabled'])
        ->set('twitter.'.$i.'.username', $values['twitter_'.$i.'_username'])
        ->set('twitter.'.$i.'.consumer_key', $values['twitter_'.$i.'_consumer_key'])
        ->set('twitter.'.$i.'.consumer_secret', $values['twitter_'.$i.'_consumer_secret'])
        ->set('twitter.'.$i.'.access_token', $values['twitter_'.$i.'_access_token'])
        ->set('twitter.'.$i.'.access_token_secret', $values['twitter_'.$i.'_access_token_secret']);
    }

    /**
     * Instagram.
     */
    $instagram_count = $this->config('social_feed_aggregator.settings')
      ->get('instagram_count');

    for($i = 0; $i < $instagram_count; $i++) {
      $this->config('social_feed_aggregator.settings')
        ->set('instagram.'.$i.'.enabled', $values['instagram_'.$i.'_enabled'])
        ->set('instagram.'.$i.'.username', $values['instagram_'.$i.'_username'])
        ->set('instagram.'.$i.'.client_id', $values['instagram_'.$i.'_client_id'])
        ->set('instagram.'.$i.'.access_token', $values['instagram_'.$i.'_access_token']);
    }

    /**
     * General.
     */
    $this->config('social_feed_aggregator.settings')
      ->set('cron.interval', $form_state->getValue('social_feed_aggregator_interval'))
      ->set('facebook_count', $facebook_count)
      ->set('twitter_count', $twitter_count)
      ->set('instagram_count', $instagram_count)
      ->set('formats.post_format', $form_state->getValue('formats_post_format'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_feed_aggregator.settings'];
  }

}
