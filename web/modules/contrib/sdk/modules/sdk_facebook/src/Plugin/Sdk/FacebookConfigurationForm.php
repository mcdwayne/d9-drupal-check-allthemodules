<?php

namespace Drupal\sdk_facebook\Plugin\Sdk;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sdk\SdkPluginConfigurationFormBase;

/**
 * Form for SDK configuration.
 */
class FacebookConfigurationForm extends SdkPluginConfigurationFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface $form_state) {
    $form['information'] = [
      '#markup' => $this->t('You can manage applications: @link', [
        '@link' => static::externalLink('https://developers.facebook.com/apps'),
      ]),
    ];

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#required' => TRUE,
    ];

    $form['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application secret'),
      '#required' => TRUE,
    ];

    $form['api_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API version'),
      '#required' => TRUE,
    ];

    $form['scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Authorisation permissions'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => ['public_profile'],
      '#description' => $this->t('Additional information about permissions level can be found here: @link', [
        '@link' => static::externalLink('https://developers.facebook.com/docs/facebook-login/permissions'),
      ]),
      '#options' => [
        'public_profile' => $this->t('Public profile'),
        'user_friends' => $this->t('User friends'),
        'email' => $this->t('User email'),
        'user_about_me' => $this->t('User information'),
        'user_actions.books' => $this->t('User books'),
        'user_actions.fitness' => $this->t('User fitness'),
        'user_actions.music' => $this->t('User music'),
        'user_actions.news' => $this->t('User news'),
        'user_actions.video' => $this->t('User video'),
        'user_birthday' => $this->t('User birthday'),
        'user_education_history' => $this->t('User education history'),
        'user_events' => $this->t('User events'),
        'user_games_activity' => $this->t('User games activity'),
        'user_hometown' => $this->t('User hometown'),
        'user_likes' => $this->t('User likes'),
        'user_location' => $this->t('User location'),
        'user_managed_groups' => $this->t('User managed groups'),
        'user_photos' => $this->t('User photos'),
        'user_posts' => $this->t('User posts'),
        'user_relationships' => $this->t('User relationships'),
        'user_relationship_details' => $this->t('User relationship details'),
        'user_religion_politics' => $this->t('User religion/politics'),
        'user_tagged_places' => $this->t('User tagged places'),
        'user_videos' => $this->t('User videos'),
        'user_website' => $this->t('User website'),
        'user_work_history' => $this->t('User work history'),
        'read_custom_friendlists' => $this->t('Read custom friend lists'),
        'read_insights' => $this->t('Read insights'),
        'read_audience_network_insights' => $this->t('Read audience network insights'),
        'read_page_mailboxes' => $this->t('Read page mailboxes'),
        'manage_pages' => $this->t('Manage pages'),
        'publish_pages' => $this->t('Publish pages'),
        'publish_actions' => $this->t('Publish actions'),
        'rsvp_event' => $this->t('RSVP event'),
        'pages_show_list' => $this->t('Show pages list'),
        'pages_manage_cta' => $this->t('Manage CTA pages'),
        'pages_manage_instant_articles' => $this->t('Manage instant articles'),
        'ads_read' => $this->t('Read ADS'),
        'ads_management' => $this->t('ADS management'),
        'business_management' => $this->t('Business management'),
        'pages_messaging' => $this->t('Messaging pages'),
        'pages_messaging_phone_number' => $this->t('Messaging phone number pages'),
      ],
    ];
  }

}
