<?php

/**
 * @file
 * Contains \Drupal\piwik_reports\Controller\PiwikReportsController.
 */

namespace Drupal\piwik_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\piwik_reports\PiwikData;

/**
 * Class PiwikReportsController.
 */
class PiwikReportsController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('messenger')
    );
  }

  /**
   * Constructs a PiwikReportsController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(FormBuilderInterface $form_builder, MessengerInterface $messenger) {
    $this->formBuilder = $form_builder;
    $this->messenger = $messenger;
  }


  /**
   * Reports.
   *
   * @return array
   *   Return Reports render array.
   */
  public function reports($report) {
    $token_auth = PiwikData::getToken();
    if (!$token_auth) {
      $_SESSION['piwik_reports_site'] = '';
      $this->messenger->addWarning($this->t('You cannot access any data on the selected Piwik server. Please check authentication string and permissions with your Piwik server administrator.'));
      return;
    }
    else {
      $sites = PiwikData::getSites($token_auth);
      if (!$sites) {
        $this->messenger->addWarning($this->t('You cannot access any data on the selected Piwik server. Please check with your Drupal administrator for allowed sites.'));
        return;
      }
    }

    $build['reports_form'] = $this->formBuilder->getForm('Drupal\piwik_reports\Form\ReportsForm', $sites = $sites);
    $piwik_site_id = $_SESSION['piwik_reports_site'];
    $period = isset($_SESSION['piwik_reports_period']) ? $_SESSION['piwik_reports_period'] : 0;

    if ($period == 1) {
      // Special handling for "yesterday" = 1.
      // The yesterday date value is required.
      $date = $this->piwik_reports_select_period($period);
    }
    else {
      // Otherwise it returns the today date value.
      $date = $this->piwik_reports_select_period(0);
    }
    $period_name = $this->piwik_reports_get_period_name($period);

    // Create an array of URL parameters for easier maintenance.
    $data_params[0] = [];
    $data_params[0]['idSite'] = $piwik_site_id;
    $data_params[0]['date'] = $date;
    $data_params[0]['period'] = $period_name;
    $data_params[0]['disableLink'] = 1;
    $data_params[0]['module'] = 'Widgetize';
    $data_params[0]['action'] = 'iframe';
    $data_params[0]['disableLink'] = 1;
    $data_params[0]['widget'] = 1;
    // $data_params[0]['loading'] = $this->t('Loading data...');.
    if (!empty($token_auth)) {
      $data_params[0]['token_auth'] = $token_auth;
    }

    switch ($report) {
      case 'visitors_overview':
        $iframe_height[0] = 950;
        $title[0] = '';
        $data_params[0]['moduleToWidgetize'] = 'VisitsSummary';
        $data_params[0]['actionToWidgetize'] = 'index';

        break;

      case 'visitors_times':
        $title[0] = $this->t('Visits by Local Time');
        $data_params[0]['moduleToWidgetize'] = 'VisitTime';
        $data_params[0]['actionToWidgetize'] = 'getVisitInformationPerLocalTime';
        break;

      case 'visitors_settings':
        $data_params[0]['filter_limit'] = 6;

        $data_params[1] = $data_params[0];
        $data_params[2] = $data_params[0];
        $data_params[3] = $data_params[0];
        // Browser families.
        $title[0] = t('Browser families');
        $data_params[0]['moduleToWidgetize'] = 'DevicesDetection';
        $data_params[0]['actionToWidgetize'] = 'getBrowserEngines';
        // Screen resolutions.
        $title[1] = t('Screen resolution');
        $data_params[1]['moduleToWidgetize'] = 'Resolution';
        $data_params[1]['actionToWidgetize'] = 'getConfiguration';
        // Operating systems.
        $title[2] = t('Operating system');
        $data_params[2]['moduleToWidgetize'] = 'DevicesDetection';
        $data_params[2]['actionToWidgetize'] = 'getOsVersions';
        // Client configurations.
        $title[3] = t('Client configuration');
        $data_params[3]['moduleToWidgetize'] = 'Resolution';
        $data_params[3]['actionToWidgetize'] = 'getResolution';
        break;

      case 'visitors_locations':
        $title[0] = $this->t('Visitors Countries');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'UserCountry';
        $data_params[0]['actionToWidgetize'] = 'getCountry';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'visitors_variables':
        $title[0] = $this->t('Custom Variables');
        $iframe_height[0] = 1000;
        $data_params[0]['moduleToWidgetize'] = 'CustomVariables';
        $data_params[0]['actionToWidgetize'] = 'getCustomVariables';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_pages':
        $title[0] = $this->t('Page Visits');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getPageUrls';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_entrypages':
        $title[0] = $this->t('Entry Pages');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getEntryPageUrls';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_exitpages':
        $title[0] = $this->t('Exit Pages');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getExitPageUrls';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_sitesearch':
        $data_params[1] = $data_params[0];
        $data_params[2] = $data_params[0];
        $data_params[3] = $data_params[0];

        $title[0] = $this->t('Site Search Keywords');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getSiteSearchKeywords';
        $data_params[0]['filter_limit'] = 15;
        // Pages following search.
        $title[1] = $this->t('Pages Following a Site Search');
        $data_params[1]['moduleToWidgetize'] = 'Actions';
        $data_params[1]['actionToWidgetize'] = 'getPageUrlsFollowingSiteSearch';
        // No results.
        $title[2] = $this->t('Site Search No Result Keyword');
        $data_params[2]['moduleToWidgetize'] = 'Actions';
        $data_params[2]['actionToWidgetize'] = 'getSiteSearchNoResultKeywords';
        // Categories.
        $title[3] = $this->t('Site Search Categories');
        $data_params[3]['moduleToWidgetize'] = 'Actions';
        $data_params[3]['actionToWidgetize'] = 'getSiteSearchCategories';
        break;

      case 'actions_outlinks':
        $title[0] = $this->t('Outlinks');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getOutlinks';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'actions_downloads':
        $title[0] = $this->t('Downloads');
        $iframe_height[0] = 750;
        $data_params[0]['moduleToWidgetize'] = 'Actions';
        $data_params[0]['actionToWidgetize'] = 'getDownloads';
        $data_params[0]['filter_limit'] = 15;
        break;

      case 'referrers_overview':
        $iframe_height[0] = 550;
        $title[0] = '';
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'index';
        break;

      case 'referrers_allreferrers':
        $data_params[1] = $data_params[0];
        // Types.
        $title[0] = $this->t('Referrer Types');
        $iframe_height[0] = 250;
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getReferrerType';
        // Referrers.
        $title[1] = $this->t('Referrers');
        $data_params[1]['moduleToWidgetize'] = 'Referrers';
        $data_params[1]['actionToWidgetize'] = 'getAll';
        break;

      case 'referrers_search':
        $data_params[1] = $data_params[0];

        $title[0] = $this->t('Search Engines');
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getSearchEngines';

        $title[1] = $this->t('Keywords');
        $data_params[1]['moduleToWidgetize'] = 'Referrers';
        $data_params[1]['actionToWidgetize'] = 'getKeywords';
        break;

      case 'referrers_websites':
        $data_params[1] = $data_params[0];
        $title[0] = $this->t('Websites');
        $iframe_height[0] = 1020;
        $data_params[0]['moduleToWidgetize'] = 'Referrers';
        $data_params[0]['actionToWidgetize'] = 'getWebsites';

        $title[1] = $this->t('Social Networks');
        $data_params[1]['moduleToWidgetize'] = 'Referrers';
        $data_params[1]['actionToWidgetize'] = 'getSocials';
        break;

      case 'goals':
        $goals = $this->piwik_reports_get_goals($token_auth, $_SESSION['piwik_reports_site']);
        if (count($goals) == 0) {
          $empty_text = $this->t('No goals have been set. Check with your Piwik server administrator if you desire some.');
          $title[0] = NULL;
          break;
        }
        $common_data_params = $data_params[0];
        $i = 0;
        foreach ($goals as $goal) {
          $title[$i] = $goal['name'];
          $data_params[$i] = $common_data_params;
          $data_params[$i]['moduleToWidgetize'] = 'Goals';
          $data_params[$i]['actionToWidgetize'] = 'widgetGoalReport';
          $data_params[$i]['idGoal'] = $goal['idgoal'];
          $i++;
        }
        break;
    }
    // Build the data URL with all params and urlencode it.
    foreach ($data_params as $key => $data) {
      $theme_args[] = [
        'url' => PiwikData::getUrl() . 'index.php?' . http_build_query($data),
        'title' => $title[$key],
        'iframe_height' => (isset($iframe_height[$key]) && $iframe_height[$key] > 0 ? $iframe_height[$key] : 400),
        'empty_text' => (isset($empty_text) ? $empty_text : NULL),
      ];
    }
    $build['content'] = [
      '#theme' => 'piwik_reports',
      '#data_url' => $theme_args,
    ];

    return $build;
  }

  /**
   * Return a list of goals active on selected site.
   *
   * @param string $token_auth
   *   Piwik server token auth.
   * @param string $site
   *   Selected site id.
   *
   * @return array|string|bool
   *   Goals returned from Piwik reports API.
   */
  private function piwik_reports_get_goals($token_auth, $site) {
    $piwik_url = PiwikData::getUrl();
    if ($piwik_url) {
      return PiwikData::getResponse($piwik_url . 'index.php?module=API&method=Goals.getGoals&idSite=' . (int) $site . '&format=JSON&token_auth=' . $token_auth);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Helper function to return the starting and ending dates according to the
   * selected period.
   *
   * @param int $period
   *   Selected period.
   *
   * @return string
   *   Formatted date.
   */
  private function piwik_reports_select_period($period) {
    switch ($period) {
      case 0:
        $date = date("Y-m-d");
        break;

      case 1:
        $d = mktime(0, 0, 0, date("m"), date("d") - 1, date("Y"));
        $date = date("Y-m-d", $d);
        break;

      case 2:
        $d = mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"));
        $date = date("Y-m-d", $d);
        break;

      case 3:
        $d = mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"));
        $date = date("Y-m-d", $d);
        break;

      case 4:
        $d = mktime(0, 0, 0, date("m"), date("d"), date("Y") - 1);
        $date = date("Y-m-d", $d);
        break;
    }
    return $date;
  }

  /**
   * Helper function to return the name of the selected period.
   *
   * @param int $period
   *   Selected period.
   *
   * @return string
   *   Name of period.
   */
  private function piwik_reports_get_period_name($period) {
    // Possible periods are day, week, month, year.
    switch ($period) {
      case 0:
        $p = "day";
        break;

      case 1:
        $p = "day";
        break;

      case 2:
        $p = "week";
        break;

      case 3:
        $p = "month";
        break;

      case 4:
        $p = "year";
        break;
    }
    return $p;
  }
}
