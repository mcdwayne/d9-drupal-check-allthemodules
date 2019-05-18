<?php

namespace Drupal\fitbit;

use djchen\OAuth2\Client\Provider\Fitbit;
use djchen\OAuth2\Client\Provider\FitbitUser;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Fitbit client wrapper. Implement custom methods to retrieve specific Fitbit
 * data using access_tokens stored in Drupal.
 */
class FitbitClient extends Fitbit {
  use StringTranslationTrait;

  /**
   * Header value to pass along for Accept-Languge, which toggles between the
   * allowed unit systems.
   *
   * @var string
   */
  protected $acceptLang;

  /**
   * FitbitClient constructor.
   *
   * @param array $options
   * @param string $accept_lang
   */
  public function __construct(array $options, $accept_lang = NULL) {
    parent::__construct($options);
    $this->setAcceptLang($accept_lang);
  }

  /**
   * Setter for the value of the Accept-Language header in all Fitbit profile
   * requests.
   *
   * @param string $accept_lang
   */
  public function setAcceptLang($accept_lang = NULL) {
    $this->acceptLang = $accept_lang;
  }

  /**
   * Get the resource owner by Drupal uid.
   *
   * @param AccessToken $access_token
   *   Fitbit AccessToken object.
   *
   * @return FitbitUser|null
   */
  public function getResourceOwner(AccessToken $access_token) {
    if ($response = $this->request('/1/user/-/profile.json', $access_token)) {
      return new FitbitUser($response);
    }
  }

  /**
   * Get a users badges.
   *
   * @param AccessToken $access_token
   *   Fitbit AccessToken object.
   *
   * @return mixed|null
   */
  public function getBadges(AccessToken $access_token) {
    return $this->request('/1/user/-/badges.json', $access_token);
  }

  /**
   * Get daily activity for the given user.
   *
   * @param AccessToken $access_token
   *   Fitbit AccessToken object.
   * @param string $date
   *
   * @return mixed
   */
  public function getDailyActivitySummary(AccessToken $access_token, $date = NULL) {
    if (!isset($date)) {
      $date = date('Y-m-d', REQUEST_TIME);
    }
    return $this->request('/1/user/-/activities/date/' . $date . '.json', $access_token);
  }

  /**
   * Get activity time series.
   *
   * @param AccessToken $access_token
   *   Fitbit AccessToken object.
   * @param $resource_path
   *   One of the allowable resource paths accepted by the Fitbit API, for
   *   example, activities/steps. For the full list, see
   *   https://dev.fitbit.com/docs/activity/#resource-path-options
   * @param array $activity_date_range
   *   Associative array with keys. Use date + period for a period based range,
   *   base_date and end_date for a specific date range:
   *   - date
   *   - period
   *   - base_date
   *   - end_date
   *   See https://dev.fitbit.com/docs/activity/#activity-time-series for more details.
   *
   * @return mixed
   */
  public function getActivityTimeSeries(AccessToken $access_token, $resource_path = NULL, $activity_date_range = []) {
    isset($resource_path) ?: $resource_path = 'activities/steps';
    if (!empty($activity_date_range['base_date']) && !empty($activity_date_range['end_date'])) {
      $activity_date_range = $activity_date_range['base_date'] . '/' . $activity_date_range['end_date'];
    }
    else {
      $activity_date_range = (isset($activity_date_range['date']) ? $activity_date_range['date'] : 'today') . '/' . (isset($activity_date_range['period']) ? $activity_date_range['period'] : '7d');
    }
    return $this->request('/1/user/-/' . $resource_path . '/date/' . $activity_date_range . '.json', $access_token);
  }

  /**
   * Request a resource on the Fitbit API.
   *
   * @param string $resource
   *   Path to the resource on the API. Should include a leading /.
   * @param AccessToken $access_token
   *   Fitbit AccessToken object.
   *
   * @return mixed|null
   *   API response or null in the case of an exception, which can happen if the
   *   user did not authorize the resource being requested.
   */
  public function request($resource, AccessToken $access_token) {
    $options = [];
    if ($this->acceptLang) {
      $options['headers'][Fitbit::HEADER_ACCEPT_LANG] = $this->acceptLang;
    }
    $request = $this->getAuthenticatedRequest(
      Fitbit::METHOD_GET,
      Fitbit::BASE_FITBIT_API_URL . $resource,
      $access_token,
      $options
    );

    try {
      return $this->getResponse($request);
    }
    catch (IdentityProviderException $e) {
      $log_level = RfcLogLevel::ERROR;
      // Look through the errors reported in the response body. If the only
      // error was an insufficient_scope error, report as a notice.
      $parsed = $this->parseResponse($e->getResponseBody());
      if (!empty($parsed['errors'])) {
        $error_types = [];
        foreach ($parsed['errors'] as $error) {
          if (isset($error['errorType'])) {
            $error_types[] = $error['errorType'];
          }
        }
        $error_types = array_unique($error_types);
        if (count($error_types) === 1 && reset($error_types) === 'insufficient_scope') {
          $log_level = RfcLogLevel::NOTICE;
        }
      }
      watchdog_exception('fitbit', $e, NULL, [], $log_level);
    }
  }

  /**
   * Return an array of supported values for Accept-Language, which correspond
   * to the unit systems supported by the API.
   *
   * @return array
   *   Associative array keyed by Accept-Language header value. Each value is
   *   the name of the units system.
   */
  public function getAcceptLangOptions() {
    return [
      '' => $this->t('Metric'),
      'en_US' => $this->t('US'),
      'en_GB' => $this->t('UK'),
    ];
  }

  /**
   * Ensure that the redirectUri param is set. Way to get around inability to
   * use Url::toString() during a router rebuild.
   */
  protected function ensureRedirectUri() {
    if (!isset($this->redirectUri)) {
      $this->redirectUri = Url::fromRoute('fitbit.authorization', [], ['absolute' => TRUE])->toString();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAuthorizationParameters(array $options) {
    $this->ensureRedirectUri();
    return parent::getAuthorizationParameters($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($grant, array $options = []) {
    $this->ensureRedirectUri();
    return parent::getAccessToken($grant, $options);
  }
}
