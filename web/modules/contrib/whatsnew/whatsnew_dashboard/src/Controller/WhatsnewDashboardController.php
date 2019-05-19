<?php

namespace Drupal\whatsnew_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\whatsnew_dashboard\Entity\Site;

/**
 * Class WhatsnewController.
 *
 * @package Drupal\Whatsnew\Controller
 */
class WhatsnewDashboardController extends ControllerBase {

  const STATUS_SECURITY = 'security';
  const STATUS_UNKNOWN = 'unknown';
  const STATUS_MAJOR = 'major';
  const STATUS_MINOR = 'minor';
  const STATUS_OK = 'ok';
  const STATUS_UNSUPPORTED = 'unsupported';

  /**
   * Display the main dashboard.
   */
  public function dashboard() {

    $reportForm = \Drupal::formBuilder()->getForm('Drupal\whatsnew_dashboard\Form\ReportForm');

    $build = [];
    $build['form'] = [
      'form' => $reportForm,
    ];

    return $build;

  }

  /**
   * Generate the build[] element for the report.
   *
   * @param \Drupal\whatsnew_dashboard\Entity\Sites[] $sites
   *   All site ConfigEntities.
   *
   * @return array
   *   Sanitized report data
   */
  protected static function fetchReportData(array $sites) {

    // Fetch the latest report from each site.
    $data = [];
    foreach ($sites as $site) {
      if ($report = $site->fetchReport()) {
        $data[$site->id()] = $report;
      }
      else {
        drupal_set_message(t("Unable to fetch report from %site.", ['%site' => $site->id()]), 'warning');
      }
    }

    $projects = [];
    $sites = [];

    // Sanitize the data into a more usable format.
    foreach ($data as $site => $modules) {
      foreach ($modules as $module) {

        $project = isset($module['project']) ? $module['project'] : NULL;
        // Skip modules without a project.
        if (empty($project)) {
          continue;
        }

        $core = isset($module['core']) ? $module['core'] : NULL;
        // Skip modules without a core compatbility flag.
        if (empty($core)) {
          continue;
        }

        $name = isset($module['name']) ? $module['name'] : 'None';
        $package = isset($module['package']) ? $module['package'] : 'None';
        $version = isset($module['version']) ? $module['version'] : 'Unknown';

        // If I've not seen this project/core combination yet,
        // collect verison details for it.
        if (!isset($projects[$project][$core])) {
          $projects[$project][$core] = [
            'version_details' => self::getVersionDetails($project, $core),
          ];
        }

        $sites[$site][$project] = [
          'version' => $version,
          'core' => $core,
        ];

      }
    }

    asort($projects);

    return [
      'projects' => $projects,
      'sites' => $sites,
    ];

  }

  /**
   * Generate the build[] element for the report.
   *
   * @param Array[] $filters
   *   List of filters to be applied to report output.
   * @param bool $use_cache
   *   Use the latest cached version of the report data.
   *
   * @return array
   *   Theme build array.
   */
  static public function buildReport(array $filters, $useCache = TRUE) {

    $cid = 'whatsnew_dashboard_report_cache';
    if ($useCache && $cache = \Drupal::cache()->get($cid)) {
      $reportData = $cache->data;
    }
    else {
      $sites = Site::loadMultiple();
      $reportData = self::fetchReportData($sites);
      \Drupal::cache()->set($cid, $reportData);
    }

    $projects = $reportData['projects'];
    $sites = $reportData['sites'];

    // Count the number of issues on both projects and sites
    // so we can exclude them rows/columns from the display.
    $projectIssues = [];
    $siteIssues = [];

    // Default tablecell.
    $defaultTableCell = [
      '#theme' => 'whatsnew_dashboard_status_cell',
      '#version' => '~',
      '#status' => self::STATUS_UNKNOWN,
      '#details' => NULL,
    ];

    // Perform version comparison for each site.
    foreach ($projects as $project => $cores) {
      foreach ($sites as $site => $siteProjects) {
        if (isset($siteProjects[$project])) {

          $version = $siteProjects[$project]['version'];
          $core = $siteProjects[$project]['core'];

          if (isset($cores[$core]['version_details'])) {
            $versionDetails = $cores[$core]['version_details'];
            $sites[$site][$project]['tablecell'] = $defaultTableCell;
            $status = self::versionComparison($version, $versionDetails, $sites[$site][$project]['tablecell']);
            if (in_array($status, $filters)) {
                $siteIssues[$site] = true;
                $projectIssues[$project] = true;
            }
          }

        }
      }
    }

    // Generate the report table.
    $form = [
      'report' => [
        '#type' => 'table',
        '#attributes' => ['class' => ['whatsnew-dashboard']],
        '#header' => ['name' => 'Site name']
      ]
    ];

    // Render each of the site reports.
    foreach ($sites as $name => $site) {

      if (!isset($siteIssues[$name])) {
        continue;
      }

      $row = array[
        'name' => ['#plain_text' => $name]
      ];

      foreach ($projects as $project => $cores) {

        if (!isset($projectIssues[$project])) {
          continue;
        }
        if (!isset($form['report']['#header'][$project])) {
           $form['report']['#header'][$project] = $project;
        }

        $tableCell = isset($site[$project]['tablecell']) ? $site[$project]['tablecell'] : $defaultTableCell;
        $row[$project] = [
          '#markup' => drupal_render($tableCell),
        ];

      }
      $form['report'][] = $row;
    }

    return $form;
  }

  /**
   * Provide a HTML snippet to offer upgrade recommendations.
   *
   * @param string $currentVersion
   *   Active project version.
   * @param string $versionInformation
   *   Project history from updates.drupal.org.
   * @param string $tableCell
   *   Static function versionComparison tablecell.
   *
   * @return string
   *   HTML content for the table cell.
   */
  protected static function versionComparison($currentVersion, $versionInformation, &$tableCell) {

    $status = self::STATUS_UNKNOWN;
    $details = NULL;

    // If we can find the active release in the version history.
    if (isset($versionInformation['releases'][$currentVersion])) {
      $currentRelease = $versionInformation['releases'][$currentVersion];

      // Fill in any blanks on the current release.
      if (empty($currentRelease['version_patch'])) {
        $currentRelease['version_patch'] = 0;
      }

      // Is my versoin still supported.
      if (isset($versionInformation['supported_majors'])) {
        $supportedVersions = explode(',', $versionInformation['supported_majors']);
        $supported = in_array($currentRelease['version_major'], $supportedVersions);
      }
      else {
        $supported = FALSE;
      }

      // Am I running an alpha or beta build.
      $currentAlphaBeta = FALSE;
      if (isset($currentRelease['version_extra'])) {
        if (preg_match('(alpha|beta|rc|dev)', $currentRelease['version_extra']) === 1) {
          $currentAlphaBeta = TRUE;
        }
      }

      $securityUpdateCount = 0;
      $majorUpdateAvailable = FALSE;
      $minorUpdateAvailable = FALSE;
      $patchUpdateAvailable = FALSE;
      $recommendedVersion = NULL;
      $latestVersion = NULL;

      foreach ($versionInformation['releases'] as $version => $release) {

        if ($version == $currentVersion) {
          continue;
        }

        // Don't suggest stable > alpha/beta upgrades.
        if (!$currentAlphaBeta) {
          if (!empty($release['version_extra'])) {
            if (preg_match('(alpha|beta|rc|dev)', $release['version_extra']) === 1) {
              continue;
            }
          }
        }

        if (!empty($release['version_major'])) {
          $majorDif = $release['version_major'] - $currentRelease['version_major'];
        }
        else {
          $majorDif = 0;
        }

        if (!empty($release['version_minor'])) {
          $minorDif = $release['version_minor'] - $currentRelease['version_minor'];
        }
        else {
          $minorDif = 0;
        }

        if (!empty($release['version_patch'])) {
          $patchDif = $release['version_patch'] - $currentRelease['version_patch'];
        }
        else {
          $patchDif = 0;
        }

        if ($majorDif > 0) {
          if (!$latestVersion) {
            $latestVersion = $version;
          }
          $majorUpdateAvailable = TRUE;
        }
        elseif ($majorDif < 0) {
          continue;
        }

        elseif ($minorDif > 0) {
          if (!$recommendedVersion) {
            $recommendedVersion = $version;
          }
          $minorUpdateAvailable = TRUE;
          if (isset($release['terms']['Release type'])) {
            if (in_array('Security update', $release['terms']['Release type'])) {
              $securityUpdateCount++;
            }
          }
        }
        elseif ($minorDif < 0) {
          continue;
        }

        elseif ($patchDif > 0) {
          if (!$recommendedVersion) {
            $recommendedVersion = $version;
          }
          $patchUpdateAvailable = TRUE;
          if (isset($release['terms']['Release type'])) {
            if (in_array('Security update', $release['terms']['Release type'])) {
              $securityUpdateCount++;
            }
          }
        }
        elseif ($patchDif < 0) {
          continue;
        }

      }

      // If it's not supported recommend upgrading.
      if (!$supported) {
        if ($majorUpdateAvailable) {
          $status = self::STATUS_SECURITY;
          $details = "Version {$currentRelease['version_major']}.x not supported, upgrade to {$latestVersion}.";
          $hasRecommendations = TRUE;
        }
        else {
          $status = self::STATUS_UNSUPPORTED;
          $details = "Unsupported";
          $hasRecommendations = TRUE;
        }
      }
      // If it is supported, recommend major updates.
      elseif ($minorUpdateAvailable) {

        if ($securityUpdateCount) {
          $status = self::STATUS_SECURITY;
          $details = "Minor security update from {$currentVersion} to {$recommendedVersion}";
        }
        else {
          $status = self::STATUS_MINOR;
          $details = "Minor update from {$currentVersion} to {$recommendedVersion}";
        }
        $hasRecommendations = TRUE;
      }
      // If it is supported, recommend major updates.
      elseif ($patchUpdateAvailable) {

        if ($securityUpdateCount) {
          $status = self::STATUS_SECURITY;
          $details = "Patch security update from {$currentVersion} to {$recommendedVersion}";
        }
        else {
          $status = self::STATUS_MINOR;
          $details = "Patch update from {$currentVersion} to {$recommendedVersion}";
        }
        $hasRecommendations = TRUE;

      }
      // We're all good.
      else {
        $status = self::STATUS_OK;
      }

    }
    else {

      // Cannot find the version.
      $status = self::STATUS_UNSUPPORTED;
      $details = "Version {$currentVersion} is not an official release";

    }

    // Update the table cell with our findings.
    $tableCell['#status'] = $status;
    $tableCell['#details'] = $details;
    $tableCell['#version'] = $currentVersion;

    return $status;

  }

  /**
   * Fetch the latest version information for a project.
   *
   * @param string $project
   *   Project name.
   * @param string $core
   *   Drupal core version (e.g. 7.x).
   *
   * @return array
   *   Array of parsed data about releases for a given project, or NULL if there
   *   was an error parsing the string.
   */
  protected static function getVersionDetails($project, $core) {

    $client = \Drupal::httpClient();

    $url = 'https://updates.drupal.org/release-history/' . $project . '/' . $core;
    try {
      $data = (string) $client
        ->get($url, ['headers' => ['Accept' => 'text/xml']])
        ->getBody();
      return self::parseVersionXml($data);
    }
    catch (RequestException $exception) {
      watchdog_exception('update', $exception);
      return NULL;
    }

  }

  /**
   * Parses the XML of the Drupal release history info files.
   *
   * @param string $raw_xml
   *   A raw XML string of available release data for a given project.
   *
   * @return array
   *   Array of parsed data about releases for a given project, or NULL if there
   *   was an error parsing the string.
   */
  protected static function parseVersionXml($raw_xml) {
    try {
      $xml = new \SimpleXMLElement($raw_xml);
    }
    catch (\Exception $e) {
      // SimpleXMLElement::__construct produces an E_WARNING error message for
      // each error found in the XML data and throws an exception if errors
      // were detected. Catch any exception and return failure (NULL).
      return NULL;
    }
    // If there is no valid project data, the XML is invalid, so return failure.
    if (!isset($xml->short_name)) {
      return NULL;
    }
    $data = [];
    foreach ($xml as $k => $v) {
      $data[$k] = (string) $v;
    }
    $data['releases'] = [];
    if (isset($xml->releases)) {
      foreach ($xml->releases->children() as $release) {
        $version = (string) $release->version;
        $data['releases'][$version] = [];
        foreach ($release->children() as $k => $v) {
          $data['releases'][$version][$k] = (string) $v;
        }
        $data['releases'][$version]['terms'] = [];
        if ($release->terms) {
          foreach ($release->terms->children() as $term) {
            if (!isset($data['releases'][$version]['terms'][(string) $term->name])) {
              $data['releases'][$version]['terms'][(string) $term->name] = [];
            }
            $data['releases'][$version]['terms'][(string) $term->name][] = (string) $term->value;
          }
        }
      }
    }
    return $data;
  }

}
