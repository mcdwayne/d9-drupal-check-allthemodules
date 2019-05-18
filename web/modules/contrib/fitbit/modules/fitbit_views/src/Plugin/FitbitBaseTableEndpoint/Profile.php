<?php

namespace Drupal\fitbit_views\Plugin\FitbitBaseTableEndpoint;

use Drupal\fitbit_views\FitbitBaseTableEndpointBase;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Fitbit profile endpoint.
 *
 * @FitbitBaseTableEndpoint(
 *   id = "profile",
 *   name = @Translation("Fitbit profile"),
 *   description = @Translation("Returns a user's profile."),
 *   response_key = "displayName"
 * )
 */
class Profile extends FitbitBaseTableEndpointBase {

  /**
   * {@inheritdoc}
   */
  public function getRowByAccessToken(AccessToken $access_token, $arguments = NULL) {
    if ($data = $this->fitbitClient->getResourceOwner($access_token)) {
      $data = $data->toArray();
      $data = $this->filterArrayByPath($data, array_keys($this->getFields()));

      // Adjust avatar and avatar150
      $data['avatar'] = [
        'avatar' => $data['avatar'],
        'avatar150' => $data['avatar150'],
      ];
      unset($data['avatar150']);

      // Change memberSince to timestamp
      $data['memberSince'] = strtotime($data['memberSince']);

      return $data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $integer = ['id' => 'numeric'];
    $float = [
      'id' => 'numeric',
      'float' => TRUE,
    ];
    $standard = [
      'id' => 'standard',
    ];
    return [
      'age' => [
        'title' => $this->t('Age'),
        'field' => $integer,
      ],
      'avatar' => [
        'title' => $this->t('Avatar'),
        'field' => [
          'id' =>'fitbit_avatar',
        ],
      ],
      // We don't want to bubble this up to views.
      'avatar150' => NULL,
      'averageDailySteps' => [
        'title' => $this->t('Average daily steps'),
        'field' => $integer,
      ],
      'country' => [
        'title' => $this->t('Country'),
        'field' => $standard,
      ],
      'dateOfBirth' => [
        'title' => $this->t('Date of birth'),
        'field' => $standard,
      ],
      'displayName' => [
        'title' => $this->t('Display name'),
        'field' => $standard,
      ],
      'fullName' => [
        'title' => $this->t('Full name'),
        'field' => $standard,
      ],
      'gender' => [
        'title' => $this->t('Gender'),
        'field' => $standard,
      ],
      'height' => [
        'title' => $this->t('Height'),
        'field' => $float,
      ],
      'memberSince' => [
        'title' => $this->t('Member since'),
        'field' => [
          'id' => 'date',
        ],
      ],
      'timezone' => [
        'title' => $this->t('Timezone'),
        'field' => $standard,
      ],
      'topBadges' => [
        'title' => $this->t('Top badges'),
        'field' => [
          'id' => 'fitbit_badges',
        ],
      ],
      'weight' => [
        'title' => $this->t('Weight'),
        'field' => $float,
      ],
    ];
  }
}