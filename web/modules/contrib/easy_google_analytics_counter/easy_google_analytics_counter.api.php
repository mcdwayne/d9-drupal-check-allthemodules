<?php

/**
 * @file
 * easy_google_analytics_counter.api.php
 *
 * author: dj
 * created: 2019.01.04. - 15:48:16
 *
 * The easy_google_analytics_counter api file.
 */

/**
 * Altering Report Request.
 *
 * @param \Google_Service_AnalyticsReporting_ReportRequest $request
 *   The request object.
 */
function hook_easy_google_analytics_counter_request_alter(\Google_Service_AnalyticsReporting_ReportRequest $request) {
  $dimensions = $request->getDimensions();
  $dimension = new \Google_Service_AnalyticsReporting_Dimension();
  $dimension->setName('ga:pageTitle');
  $dimensions[] = $dimension;
  $request->setDimensions($dimensions);
}

/**
 * Altering Report Body.
 *
 * @param \Google_Service_AnalyticsReporting_GetReportsRequest $body
 */
function functionName(\Google_Service_AnalyticsReporting_GetReportsRequest $body) {
  // No example.
}
