<?php

namespace Drupal\removequerystring;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware to alter REST responses.
 */
class RemovequerystringAlterResponse implements HttpKernelInterface {
  /**
   * The wrapped kernel implementation.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  private $httpKernel;

  /**
   * The wrapped kernel implementation.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   Http Kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    $config = \Drupal::config('removequerystring.settings');
    $vals = $config->get('removequerystring_appid');
    if ($_SESSION['_sf2_attributes']['uid'] > 0) {
    }
    else {
      $response = $this->httpKernel->handle($request, $type, $catch);
      $html = $response->getContent();
      $dom = new \DOMDocument();
      $dom->loadHTML($html);
      if ($vals['images'] === 'images') {
        $img = $dom->getElementsByTagName('img');
        foreach ($img as $row) {
          $row->setAttribute('src', $this->removeQuerySring($row->getAttribute('src'), "images"));
        }
      }
      if ($vals['css'] === 'css') {
        $link = $dom->getElementsByTagName('link');
        foreach ($link as $row) {
          if (strtolower(trim($row->getAttribute('rel'))) === "stylesheet" || strtolower(trim($row->getAttribute('rel'))) === "") {
            $row->setAttribute('href', $this->removeQuerySring($row->getAttribute('href'), "css"));
          }
        }
      }
      if ($vals['script'] === 'script') {
        $script = $dom->getElementsByTagName('script');
        foreach ($script as $row) {
          $row->setAttribute('src', $this->removeQuerySring($row->getAttribute('src'), "script"));
        }
      }
      $html = $dom->saveHTML();
      $response->setContent($html);
      return $response;
    }
  }

  /**
   * Remove query strng.
   */
  public function removeQuerySring($url, $type) {
    if ($this->excludeUrl($url, $type) == TRUE) {
      $url = trim($url);
      $tmp1 = explode("?", $url);
      $url = $tmp1[0];
      return $url;
    }
    else {
      return $url;
    }
  }

  /**
   * Exclude query strng.
   */
  public function excludeUrl($url, $type) {
    $config = \Drupal::config('removequerystring.settings');
    $vals = "";
    switch ($type) {
      case "css":
        $vals = $config->get('removequerystring_exclude_css');
        break;

      case "images":
        $vals = $config->get('removequerystring_exclude_image');
        break;

      case "script":
        $vals = $config->get('removequerystring_exclude_javascript');
        break;
    }
    if (trim($vals) == "") {
      return TRUE;
    }
    $vals = trim($vals, ",");
    $vals = trim($vals);
    $tmp = explode(",", $vals);
    $tot_exclude = count($tmp);
    for ($i = 0; $i < $tot_exclude; $i++) {
      $tmp1 = trim($tmp[$i]);
      $first_star = substr($tmp1, 0, 1);
      $last_star = substr($tmp1, -1);
      $tmp1 = strtolower(trim($tmp1, "*"));
      $url = strtolower(trim($url));
      $tmp_len = strlen($tmp1);
      $url_len = strlen($url);
      if ($first_star === "*" && $last_star === "*") {
        if (strpos($url, $tmp1) !== FALSE) {
          return FALSE;
        }
      }
      elseif ($first_star === "*") {
        if ($url_len >= $tmp_len) {
          $tmp2 = substr($url, ($tmp_len * (-1)));
          if ($tmp2 === $tmp1) {
            return FALSE;
          }
        }
      }
      elseif ($last_star === "*") {
        if ($url_len >= $tmp_len) {
          $tmp2 = substr($url, 0, $tmp_len);
          if ($tmp2 === $tmp1) {
            return FALSE;
          }
        }
      }
      else {
        if ($url === $tmp1) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

}
