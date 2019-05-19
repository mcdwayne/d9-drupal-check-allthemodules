<?php

namespace Drupal\skyword;

/**
 * Common Tools that Skyword uses.
 */
class SkywordCommonTools {

  /**
   * Modify the header response and include some header properties.
   *
   * One important thing to note is the pagination.
   */
  public static function pager(&$response, &$query) {

    // @todo: rewrite pager for d8 global $request.
    $currentPage = $_GET['page'] ?: 1;
    $perPage = $_GET['per_page'] ?: 250;

    $firstRecord = ($currentPage - 1) * $perPage;
    $next = $currentPage + 1;
    $prev = $currentPage - 1;
    $total = count($query->execute());

    $last = ceil($total / $perPage);

    $url = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:') . '//' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');

    $response->headers->add(['X-Total-Count' => $total]);

    $headerLink = [];

    if ($next <= $last) {
      $headerLink[] = "<{$url}?page={$next}&per_page={$perPage}>; rel=\"next\"";
    }

    $headerLink[] = "<{$url}?page=$last&per_page={$perPage}>; rel=\"last\"";
    $headerLink[] = "<{$url}?page=1&per_page={$perPage}>; rel=\"first\"";

    if ($prev > 0) {
      $headerLink[] = "<{$url}?page={$prev}&per_page={$perPage}>; rel=\"prev\"";
    }

    $response->headers->add(['Link' => implode(',', $headerLink)]);

    if ($perPage > $total) {
      $query->range(0, $total);
    }
    else {
      $query->range($firstRecord, $perPage);
    }
  }
}
