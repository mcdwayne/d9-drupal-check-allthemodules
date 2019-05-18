<?php

namespace Drupal\funnelback;

/**
 * Funnelback query string process class.
 */
class FunnelbackQueryString {

  /**
   * Get facet query string from raw query string.
   *
   * @param array $rawQueries
   *   List of raw queries.
   *
   * @return array
   *   List of facet queries.
   */
  public static function funnelbackFilterFacetQueryString(array $rawQueries = []) {
    $facetQuery = [];
    foreach ($rawQueries as $param) {
      if (substr($param, 0, 2) == 'f.') {
        // Compose query string array.
        $strQuery = explode('=', $param);
        $facetQuery[$strQuery[0]][] = str_replace(' ', '+', strip_tags(urldecode($strQuery[1])));
      }
    }

    return $facetQuery;
  }

  /**
   * Get facet query string from raw query string.
   *
   * @param array $rawQueries
   *   List of raw queries.
   *
   * @return array
   *   List of facet queries.
   */
  public static function funnelbackFilterContextualQueryString(array $rawQueries = []) {
    $contextualQuery = [];
    foreach ($rawQueries as $param) {
      if (substr($param, 0, 7) == 'cluster' ||
        substr($param, 0, 15) == 'clicked_fluster') {
        // Compose query string array.
        $strQuery = explode('=', $param);
        $contextualQuery[$strQuery[0]] = str_replace(' ', '+', strip_tags(urldecode($strQuery[1])));
      }
    }

    return $contextualQuery;
  }

  /**
   * Remove system default query strings from link.
   *
   * @param string $strQuery
   *   Query string.
   *
   * @return string
   *   Filtered query string.v
   */
  public static function filterQueryString($strQuery) {
    $strQuery = str_replace('?', '', $strQuery);
    $strQuerys = explode('&', $strQuery);
    foreach ($strQuerys as $key => $segment) {
      if (strpos($segment, 'remote_ip=') === 0 ||
        strpos($segment, 'profile=') === 0 ||
        strpos($segment, 'collection=') === 0 ||
        strpos($segment, 'form=') === 0) {
        // Remove system query strings.
        unset($strQuerys[$key]);
      }
    }

    return '?' . implode('&', $strQuerys);
  }

  /**
   * Normalise query.
   *
   * @param string $strQuery
   *   Query string.
   *
   * @return mixed|string
   *   Normalised query string.
   */
  public static function funnelbackQueryNormaliser($strQuery) {
    // Covert f_ to f. for facet query format in funnelback.
    $strQuery = str_replace('f_', 'f.', $strQuery);

    // Decode query string for later replacement.
    $strQuery = urldecode($strQuery);

    // Remove '[]' from facet query for funnelback.
    $strQuery = preg_replace("/\\[(.*?)\\]/", NULL, $strQuery);

    // For search query.
    $strQuery = str_replace("`", '', $strQuery);
    $strQuery = str_replace(' ', '+', $strQuery);

    // Remove tags.
    $strQuery = strip_tags($strQuery);

    return $strQuery;
  }

  /**
   * Find redirect url from curator link and decode it.
   *
   * @param string $linkUrl
   *   Requested url.
   *
   * @return string
   *   Decoded url.
   */
  public static function filterCuratorLink($linkUrl) {
    $url = '';
    $querySegments = explode('&', $linkUrl);
    foreach ($querySegments as $segment) {
      if (strpos($segment, 'url=') === 0) {
        $url = urldecode(substr($segment, 4, strlen($segment)));
      }
    }

    return $url;
  }

  /**
   * Remove specific query string from the raw query string array.
   *
   * @param string $strQuery
   *   Query string to remove.
   * @param array $query
   *   Raw query string array.
   *
   * @return array
   *   Array with removed query string.
   */
  public static function funnelbackQueryRemove($strQuery, array &$query) {
    foreach ($query as $key => $value) {
      if (strpos($value, $strQuery) !== FALSE) {
        unset($query[$key]);
      }
    }

    return $query;
  }

}
