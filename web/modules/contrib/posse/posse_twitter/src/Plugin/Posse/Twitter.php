<?php

namespace Drupal\posse_twitter\Plugin\Posse;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\posse\PosseInterface;
use Drupal\posse\Plugin\Posse\PosseBase;
use Abraham\TwitterOAuth\TwitterOAuth;

/**
* Base class for the Posse plugin functionality.
* @Posse(
*   id = "posse_twitter",
*   label = "Twitter Settings",
*   display_label = "Twitter",
* )
*
*/
class Twitter extends PosseBase implements PosseInterface {

  /**
  * {@inheritdoc}
  */
  public function configurationForm(&$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->get('posse.twitter');

    $form['twitter'] = [
      '#type' => 'details',
      '#title' => t('Twitter'),
      '#description' => t('Get your Twitter.com api details from https://apps.twitter.com/.'),
      '#tree' => TRUE,
    ];

    $form['twitter']['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => t('Consumer Key'),
      '#description' => t('Enter the consumer key from twitter.'),
      '#default_value' => $config->get('consumer_key'),
    ];

    $form['twitter']['consumer_secret'] = [
      '#type' => 'password',
      '#title' => t('Consumer Secret'),
      '#description' => t('Enter the consumer secret from twitter.'),
      '#default_value' => $config->get('consumer_secret'),
    ];

    $form['twitter']['access_token'] = [
      '#type' => 'textfield',
      '#title' => t('Access Token'),
      '#description' => t('Enter the access token from twitter.'),
      '#default_value' => $config->get('access_token'),
    ];

    $form['twitter']['access_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Access Token Secret'),
      '#description' => t('Enter the access token secret from twitter.'),
      '#default_value' => $config->get('access_secret'),
    ];

    try {
      $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('access_token'), $config->get('access_secret'));
      $content = $connection->get("account/verify_credentials");
      $statuses = $connection->get("search/tweets", ["q" => "#Drupal"]);
      if (count($statuses->statuses) > 0) {
        $form['twitter']['warning'] = [
          '#markup' => t('Twitter is properly configured and connected to the api.')
        ];
      }
      else {
        $form['twitter']['warning'] = [
          '#markup' => t('Twitter is not properly configured.')
        ];
      }
    }
    catch(\Exception $e) {
      $form['twitter']['warning'] = [
        '#markup' => t('Twitter is not properly configured.')
      ];
    }

  }

  /**
  * {@inheritdoc}
  */
  public function validateConfiguration($form, FormStateInterface &$form_state) {
  }

  /**
  * {@inheritdoc}
  */
  public function submitConfiguration($form, FormStateInterface &$form_state) {
    $config = \Drupal::service('config.factory')->getEditable('posse.twitter');
    $values = $form_state->getValues()['twitter'];
    $config->set('consumer_key', $values['consumer_key']);
    $config->set('consumer_secret', $values['consumer_secret']);
    $config->set('access_token', $values['access_token']);
    $config->set('access_secret', $values['access_secret']);
    $config->save();
  }

  /**
  * {@inheritdoc}
  */
  public function syndicate(ContentEntityBase $entity, $insert = TRUE) {
    // @TODO: clean this code up a bit....

    // Get the configuration for this entity and bundle
    $settingsId = "posse.twitter." . $entity->getEntityTypeId() . "." . $entity->bundle();
    $entitySettings = \Drupal::service('config.factory')->get($settingsId);
    $status = $entitySettings->get('status');
    if ((!isset($entity->get('twitter_id')->getValue()[0]) || $entity->get('twitter_id')->getValue()[0]['value'] == '') && $entity->isPublished() && $status) {
      $token_service = \Drupal::token();
      // Prepare the information to be sent to Twitter's API
      $statusMessage = $token_service->replace($entitySettings->get('tweet'), [ $entity->getEntityTypeId() => $entity ]);
      $postUrl = ($entitySettings->get('alias') !== '') ? $token_service->replace($entitySettings->get('alias'), [ $entity->getEntityTypeId() => $entity ]) : $entity->toUrl('canonical', ['absolute' => TRUE])->toString();

      // Send to twitter the status update.
      $config = \Drupal::service('config.factory')->get('posse.twitter');
      $connection = new TwitterOAuth($config->get('consumer_key'), $config->get('consumer_secret'), $config->get('access_token'), $config->get('access_secret'));
      $data = [
        "status" => $statusMessage .', ' . $postUrl,
      ];
      $status = $connection->post("statuses/update", $data);
      $code = $connection->getLastHttpCode();
      if ($code == 200) {
        $entity->set('twitter_id', $status->id);
        $entity->save();
      } else {
          // Handle error case
          // @TODO: decide how to handle errors here....
      }
    }
    
  }

  /**
  * {@inheritdoc}
  */
  public function syndicateForm($form, FormStateInterface $form_state) {
    $settingsId = "posse.twitter.{$form['#entity_type']}.{$form['#bundle']}";

    $config = \Drupal::service('config.factory')->get($settingsId);
    $settings['status'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable Tweeting'),
      '#description' => t('Tweet this content type.'),
      '#default_value' => $config->get('status')
    ];
    $settings['tweet'] = [
      '#type' => 'textfield',
      '#title' => t('Tweet'),
      '#description' => t('Enter the token that will be used when sending a tweet, remember the current maximum length is 280 characters.'),
      '#default_value' => $config->get('tweet')
    ];

    $settings['attachment_image'] = [
      '#type' => 'textfield',
      '#title' => t('Image'),
      '#description' => t('You can attach up to three images, one animated gif, or one video to a tweet.'),
      '#default_value' => $config->get('attachment_image')
    ];

    $settings['attachment_animation'] = [
      '#type' => 'textfield',
      '#title' => t('Video/GIF'),
      '#description' => t('You can attach up to three images, one animated gif, or one video to a tweet.'),
      '#default_value' => $config->get('attachment_animation')
    ];

    $settings['alias'] = [
      '#type' => 'textfield',
      '#title' => t('Custom Alias'),
      '#description' => t('For decoupled sites that may use an alternative to the url alias generated by Drupal, this should be an absolute url.'),
      '#default_value' => $config->get('alias')
    ];

    $settings['replies_as_comments'] = [
      '#type' => 'checkbox',
      '#title' => t('Store replies as comments'),
      '#description' => t('Track replies to your tweet as comments to improve engagement. * Currently not implemented.'),
      '#default_value' => $config->get('replies_as_comments')
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $token_types = [ 'node' ];
      $settings['token_tree'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
      );
    }
    else {
      $settings['token_tree'] = array(
        '#markup' => '<p>' . t('Enable the <a href="@drupal-token">Token module</a> to view the available token browser.', array(
          '@drupal-token' => 'http://drupal.org/project/token',
        )) . '</p>',
      );
    }

    return $settings;
  }

  /**
  * {@inheritdoc}
  */
  public function syndicateFormValidate($form, FormStateInterface &$form_state) {}

  /**
  * {@inheritdoc}
  */
  public function syndicateFormSubmit($form, FormStateInterface &$form_state) {
    $settingsId = "posse.twitter.{$form['#entity_type']}.{$form['#bundle']}";

    $config = \Drupal::service('config.factory')->getEditable($settingsId);
    $values = $form_state->getValues()['posse_twitter']['settings'];

    $config->set('status', $values['status']);
    $config->set('tweet', $values['tweet']);
    $config->set('attachment_image', $values['attachment_image']);
    $config->set('attachment_animation', $values['attachment_animation']);
    $config->set('replies_as_comments', $values['replies_as_comments']);
    $config->set('alias', $values['alias']);
    $config->save();
  }

  /**
  * {@inheritdoc}
  */
  public function aggregateComments(ContentEntityBase $entity) {
    // For twitter we want to interlace response tweets into the comment section of the site.
    $config = \Drupal::service('config.factory')->getEditable("posse.twitter.{$entity->getEntityTypeId()}.{$entity->bundle()}");
    if ($config->get('status') && $config->get('replies_as_comments')) {

    }
  }


}
