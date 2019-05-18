<?php

namespace Drupal\reltoabs;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware to alter REST responses.
 */
class ReltoabsAlterResponse implements HttpKernelInterface {
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
    $config = \Drupal::config('reltoabs.settings');
    $vals = $config->get('reltoabs_appid');
    if ($_SESSION['_sf2_attributes']['uid'] > 0) {
    }
    else {
      $response = $this->httpKernel->handle($request, $type, $catch);
      $html = $response->getContent();
      $dom = new \DOMDocument();
      $dom->loadHTML($html);
      if ($vals['links'] === 'links') {
        $a = $dom->getElementsByTagName('a');
        foreach ($a as $row) {
          $row->setAttribute('href', $this->rel2abs(trim($row->getAttribute('href'))));
        }
      }
      if ($vals['images'] === 'images') {
        $img = $dom->getElementsByTagName('img');
        foreach ($img as $row) {
          $row->setAttribute('src', $this->rel2abs(trim($row->getAttribute('src'))));
        }
      }
      if ($vals['css'] === 'css') {
        $link = $dom->getElementsByTagName('link');
        foreach ($link as $row) {
          $row->setAttribute('href', $this->rel2abs(trim($row->getAttribute('href'))));
        }
      }
      if ($vals['script'] === 'script') {
        $script = $dom->getElementsByTagName('script');
        foreach ($script as $row) {
          $row->setAttribute('src', $this->rel2abs(trim($row->getAttribute('src'))));
        }
      }
      $html = $dom->saveHTML();
      $response->setContent($html);
      return $response;
    }
  }

  /**
   * Relative to absolute.
   */
  public function rel2abs($rel, $base) {
    $path = "";
    $host = "";
    $scheme = "";
    $base = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    if (strpos($rel, "//") === 0) {
      return "http:" . $rel;
    }
    /* return if  already absolute URL */
    if (parse_url($rel, PHP_URL_SCHEME) != '') {
      return $rel;
    }
    /* queries and  anchors */
    if ($rel[0] == '#' || $rel[0] == '?') {
      return $base . $rel;
    }
    /* parse base URL  and convert to local variables:
    $scheme, $host,  $path */
    extract(parse_url($base));
    /* remove  non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);
    /* destroy path if  relative url points to root */
    if ($rel[0] == '/') {
      $path = '';
    }
    /* dirty absolute  URL */
    $abs = "$host$path/$rel";
    $abs = str_replace("//", "/", $abs);
    $re[] = '#/(?!..)[^/]+/../#';
    for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
    }
    /* absolute URL is  ready! */
    return $scheme . '://' . $abs;
  }

}
