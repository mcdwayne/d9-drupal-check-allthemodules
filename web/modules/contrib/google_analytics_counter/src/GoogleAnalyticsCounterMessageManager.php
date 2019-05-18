<?php

namespace Drupal\google_analytics_counter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;

/**
 * Defines the Google Analytics Counter message manager.
 *
 * @package Drupal\google_analytics_counter
 */
class GoogleAnalyticsCounterMessageManager implements GoogleAnalyticsCounterMessageManagerInterface {

  use StringTranslationTrait;

  /**
   * The google_analytics_counter.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The state where all the tokens are saved.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   *
   * Constructs a GoogleAnalyticsCounterMessageManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue collection to use.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, StateInterface $state, DateFormatter $date_formatter, LoggerInterface $logger, MessengerInterface $messenger) {
    $this->config = $config_factory->get('google_analytics_counter.settings');
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
    $this->state = $state;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * Prints a warning message when not authenticated.
   *
   * @param $build
   *
   */
  public function notAuthenticatedMessage($build = []) {
    $t_arg = [
      ':href' => Url::fromRoute('google_analytics_counter.admin_auth_form', [], ['absolute' => TRUE])
        ->toString(),
      '@href' => 'Authentication',
    ];
    $this->messenger->addWarning(t('Google Analytics have not been authenticated! Google Analytics Counter cannot fetch any new data. Please authenticate with Google from the <a href=:href>@href</a> page.', $t_arg));

    // Revoke Google authentication.
    $this->revokeAuthenticationMessage($build);
  }

  /**
   * Revoke Google Authentication Message.
   *
   * @param $build
   * @return mixed
   */
  public function revokeAuthenticationMessage($build) {
    $t_args = [
      ':href' => Url::fromRoute('google_analytics_counter.admin_auth_revoke', [], ['absolute' => TRUE])
        ->toString(),
      '@href' => 'revoking Google authentication',
    ];
    $build['revoke_authentication'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t("If there's a problem with OAUTH authentication, try <a href=:href>@href</a>.", $t_args),
    ];
    return $build;
  }

  /**
   * Returns the link with the Google project name if it is available.
   *
   * @return string
   *   Project name.
   */
  public function googleProjectName() {
    $config = $this->config;
    $project_name = !empty($config->get('general_settings.project_name')) ?
      Url::fromUri('https://console.developers.google.com/apis/api/analytics.googleapis.com/quotas?project=' . $config->get('general_settings.project_name'))
        ->toString() :
      Url::fromUri('https://console.developers.google.com/apis/api/analytics.googleapis.com/quotas')
        ->toString();

    return $project_name;
  }

  /**
   * Get the Profile name of the Google view from Drupal.
   *
   * @param string $profile_id
   *   The profile id used in the google query.
   *
   * @return string mixed
   */
  public function getProfileName($profile_id) {

    $profile_id = $this->state->get('google_analytics_counter.total_pageviews_' . $profile_id);
    if (!empty($profile_id)) {
      $profile_name = '<strong>' . $profile_id[key($profile_id)] . '</strong>';
    }
    else {
      $profile_name = '<strong>' . $this->t('(Profile name to come)') . '</strong>';
    }
    return $profile_name;
  }

  /**
   * Get the the top twenty results for pageviews and pageview_totals.
   *
   * @param string $table
   *   The table from which the results are selected.
   *
   * @return mixed
   */
  public function getTopTwentyResults($table) {
    $query = $this->connection->select($table, 't');
    $query->range(0, 20);
    $rows = [];
    switch ($table) {
      case 'google_analytics_counter':
        $query->fields('t', ['pagepath', 'pageviews']);
        $query->orderBy('pageviews', 'DESC');
        $result = $query->execute()->fetchAll();
        $rows = [];
        foreach ($result as $value) {
          $rows[] = [
            $value->pagepath,
            $value->pageviews,
          ];
        }
        break;
      case 'google_analytics_counter_storage':
        $query->fields('t', ['nid', 'pageview_total']);
        $query->orderBy('pageview_total', 'DESC');
        $result = $query->execute()->fetchAll();
        foreach ($result as $value) {
          $rows[] = [
            $value->nid,
            $value->pageview_total,
          ];
        }
        break;
      default:
        break;
    }

    return $rows;
  }

  /**
   * Voluminous on screen instructions about authentication.
   *
   * @param $web_properties
   *
   * @return string
   */
  public function authenticationInstructions($web_properties) {
    $t_arg = [
      ':href' => Url::fromRoute('google_analytics_counter.admin_dashboard_form', [], ['absolute' => TRUE])
        ->toString(),
      '@href' => 'Dashboard',
    ];
    $markup_description = ($web_properties === 'unauthenticated') ?
      '<ol><li>' . $this->t('Fill in the Client ID, Client Secret, Authorized Redirect URI, and optionally Google Project Name in the fields below.') .
      '</li><ul><li>' . $this->t('If you don\'t have a Client ID, a Client Secret, an Authorized Redirect URI, and optionally a Google Project Name, follow the instructions in the README.md included with this module or read the <a href="https://www.drupal.org/docs/8/modules/google-analytics-counter" target="_blank">online documentation</a>.') .
      '</li></ul><li>' . $this->t('Save configuration.') .
      '</li><li>' . $this->t('Click Authenticate in "Authenticate with Google Analytics" above.') .
      '</li><li>' . $this->t('If authentication with Google is successful, the ') . '<strong>' . $this->t(' Google View ') . '</strong>' . $this->t('field will list your analytics profiles.') .
      '</li><li>' . $this->t('Select an analytics profile to collect analytics from and click Save configuration.') .
      '</li><ul><li>' . $this->t('If you are not authenticated or if the project you are authenticating to does not have Analytics, no options are available in the') . '<strong>' . $this->t(' Google View.') . '</strong>' .
      '</strong></li></ul></ol></p>' :
      '<p>' . $this->t('Client ID, Client Secret, and Authorized redirect URI can only be changed when not authenticated.') .
      '<ol><li>' . $this->t('Now that you are authenticated with Google Analytics, you MUST select the') . '<strong>' . $this->t(' Google View ') . '</strong>' . $this->t('to collect analytics from.') .
      '</li><li>' . $this->t('Save configuration.') .
      '</li><li>' . $this->t('On the next cron job, analytics from the selected') . '<strong>' . $this->t(' Google View ') . '</strong>' . $this->t('will be saved to Drupal.') .
      '</li><ul><li>' . $this->t('Information on the <a href=:href>@href</a> page is from the', $t_arg) . '<strong>' . $this->t(' Google View') . '</strong>' . $this->t('.') .
      '</li><li>' . $this->t('After cron runs, compare pagepaths and pageview totals on the <a href=:href>@href</a> in the Top Twenty Results section with your Google Analytics.', $t_arg) .
      '</li><li>' . $this->t('If date range is set to one of Google\'s predefined time intervals, the Pageviews in Drupal should match Google exactly.', $t_arg) .
      '</li></ul></ol></p>';

    return $markup_description;
  }

  /**
   * Sets the start and end dates in configuration.
   *
   * @return array
   *   Start and end dates.
   */
  public function setStartDateEndDate() {
    $config = $this->config;

    if (($config->get('general_settings.custom_start_date') != '' & $config->get('general_settings.custom_end_date') != '')) {
      $t_args = [
        '%start_date' => $this->dateFormatter
          ->format(strtotime($config->get('general_settings.custom_start_date')), 'custom', 'M j, Y'),
        '%end_date' => $this->dateFormatter
          ->format(strtotime($config->get('general_settings.custom_end_date')), 'custom', 'M j, Y'),
      ];
      return $t_args;
    }
    else {
      $t_args = [];

      switch ($config->get('general_settings.start_date')) {
        case 'today':
          $t_args = [
            '%start_date' => date('M j, Y'),
            '%end_date' => date('M j, Y'),
          ];
          break;

        case 'yesterday':
          $t_args = [
            '%start_date' => date('M j, Y', time() - 60 * 60 * 24),
            '%end_date' => date('M j, Y', time() - 60 * 60 * 24),
          ];
          break;

        case '-1 week last sunday midnight':
          $previous_week = strtotime('-1 week +1 day');

          $start_week = strtotime('last sunday midnight', $previous_week);
          $end_week = strtotime('next saturday', $start_week);

          $start_week = date('M j, Y', $start_week);
          $end_week = date('M j, Y', $end_week);

          $t_args = [
            '%start_date' => $start_week,
            '%end_date' => $end_week,
          ];
          break;

        case 'first day of previous month':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('first day of previous month')),
            '%end_date' => date('M j, Y', strtotime('last day of previous month')),
          ];
          break;

        case '7 days ago':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('7 days ago')),
            '%end_date' => date('M j, Y', time() - 60 * 60 * 24),
          ];
          break;

        case '30 days ago':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('30 days ago')),
            '%end_date' => date('M j, Y', time() - 60 * 60 * 24),
          ];
          break;

        case '3 months ago':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('3 months ago')),
            '%end_date' => date('M j, Y', time() - 60 * 60 * 24),
          ];
          break;

        case '6 months ago':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('6 months ago')),
            '%end_date' => date('M j, Y', time() - 60 * 60 * 24),
          ];
          break;

        case 'first day of last year':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('first day of last year')),
            '%end_date' => date('M j, Y', strtotime('last day of last year')),
          ];
          break;

        case '14 November 2005':
          $t_args = [
            '%start_date' => date('M j, Y', strtotime('14 November 2005')),
            '%end_date' => date('M j, Y', time() - 60 * 60 * 24),
          ];
          break;

        default:
          $t_args = [
            '%start_date' => 'N/A',
            '%end_date' => 'N/A',
          ];
          break;
      }

      return $t_args;
    }
  }

}
