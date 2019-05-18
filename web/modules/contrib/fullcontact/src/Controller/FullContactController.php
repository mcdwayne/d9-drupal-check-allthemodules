<?php

namespace Drupal\fullcontact\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class FullContactController.
 *
 * @package Drupal\fullcontact\Controller
 */
class FullContactController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content(Request $request, AccountInterface $user) {
    $config = $this->config('fullcontact.adminsettings');
    $social_setting = $config->get('fullcontact_social_settings');

    $header = [t('Social Name'), t('Social Link')];
    $rows = [];

    $user_fields = User::load(\Drupal::currentUser()->id());
    $_socialarray = fullcontactGetSocialArray();

    foreach ($_socialarray as $key => $value) {
      $field = 'field_' . $key;

      if ($user_fields->hasField($field)) {
        $field_value = $user_fields->$field->getValue();
        $field_label = $user_fields->$field->getfieldDefinition()->label();

        if (!empty($field_value)) {
          $field_value = $field_value[0]['value'];
          $rows[] = [$field_label,
            Link::fromTextAndUrl($field_value, Url::fromUri($field_value, ['attributes' => ['target' => '_blank']])),
          ];
        }
      }
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Full contact create social array.
   */
  public function fullcontactGetSocialArray() {
    return [
      'fullcontact_facebook' => t('Facebook'),
      'fullcontact_googleplus' => t('Google Plus'),
      'fullcontact_instagram' => t('Instagram'),
      'fullcontact_linkedin' => t('Linkedin'),
      'fullcontact_twitter' => t('Twitter'),
      'fullcontact_youtube' => t('Youtube'),
      'fullcontact_quora' => t('Quora'),
      'fullcontact_flickr' => t('Flickr'),
      'fullcontact_github' => t('Github'),
      'fullcontact_pinterest' => t('Pinterest'),
      'fullcontact_klout' => t('Klout'),
      'fullcontact_gravatar' => t('Gravatar'),
      'fullcontact_foursquare' => t('Foursquare'),
      'fullcontact_xing' => t('Xing'),
      'fullcontact_aboutme' => t('About.me'),
      'fullcontact_angellist' => t('AngelList'),
      'fullcontact_keybase' => t('Keybase'),
      'fullcontact_plancast' => t('Plancast'),
      'fullcontact_hackernews' => t('HackerNews'),
      'fullcontact_lastfm' => t('Last.FM'),
    ];
  }

}
