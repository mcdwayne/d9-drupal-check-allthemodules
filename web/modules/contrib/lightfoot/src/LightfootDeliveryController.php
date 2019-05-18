<?php

namespace Drupal\lightfoot;

use Drupal\Core\Controller\ControllerBase;

use Drupal\Component\Utility\Crypt;
//use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\PrivateKey;
//use Drupal\Core\Site\Settings;
//use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Asset\JsOptimizer;

class LightfootDeliveryController extends ControllerBase {
  use LightfootRoundtripTrait;

  protected function getFarFutureResponse($content, $contentType) {
    $farfuture_headers = [
      // Instead of being powered by PHP, tell the world this resource was
      // powered by the CDN module!
      'X-Powered-By' => 'Pantheon Lightfoot module (https://www.drupal.org/project/lightfoot)',
      // Browsers that implement the W3C Access Control specification might
      // refuse to use certain resources such as fonts if those resources
      // violate the same-origin policy. Send a header to explicitly allow
      // cross-domain use of those resources. (This is called Cross-Origin
      // Resource Sharing, or CORS.)
      // The CDN module allows any domain to access it by default, which means
      // hotlinking of these files is possible. If you want to prevent this,
      // implement a KernelEvents::RESPONSE subscriber that modifies this header
      // for this route.
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'GET, HEAD',
      // Set a far future Cache-Control header (480 weeks), which prevents
      // intermediate caches from transforming the data and allows any
      // intermediate cache to cache it, since it's marked as a public resource.
      // Finally, it's also marked as "immutable", which helps avoid
      // revalidation, see:
      // - https://bitsup.blogspot.be/2016/05/cache-control-immutable.html
      // - https://tools.ietf.org/html/draft-mcmanus-immutable-00
      'Cache-Control' => 'max-age=290304000, no-transform, public, immutable',
      // Set a far future Expires header. The maximum UNIX timestamp is
      // somewhere in 2038. Set it to a date in 2037, just to be safe.
      'Expires' => 'Wed, 11 Mar 2037 00:00:00 GMT',
      // Pretend the file was last modified a long time ago in the past, this
      // will prevent browsers that don't support Cache-Control nor Expires
      // headers to still request a new version too soon (these browsers
      // calculate a heuristic to determine when to request a new version, based
      // on the last time the resource has been modified).
      // Also see http://code.google.com/speed/page-speed/docs/caching.html.
      'Last-Modified' => 'Sun, 11 Mar 1984 00:00:00 GMT',
    ];

    return new Response($content, Response::HTTP_OK, array('Content-Type' => $contentType));
  }

  protected function deliverJs($js_paths) {
    // @TODO: Make pluggable.
    $optimizer = new JsOptimizer();

    // Build aggregate JS response.
    $data = '';
    foreach ($js_paths as $js_path) {
      if ('js' !== pathinfo($js_path, PATHINFO_EXTENSION)) {
        throw new AccessDeniedHttpException('Invalid extension in requested JS path.');
      }

      // @TODO: Preserve byte-order/charset data.
      $js_asset = array('type' => 'file',
                        'preprocess' => TRUE,
                        'data' => $js_path);

      $data .= $optimizer->optimize($js_asset);
    }

    $response = self::getFarFutureResponse($data, 'text/javascript');
    return $response;
  }

  protected function deliverCss($css_paths) {
    // @TODO: Make pluggable.
    $optimizer = new CssOptimizer();

    // Optimize each asset within the group.
    $data = '';
    foreach ($css_paths as $css_path) {
      if ('css' !== pathinfo($css_path, PATHINFO_EXTENSION)) {
        throw new AccessDeniedHttpException('Invalid extension in requested CSS path.');
      }

      $css_asset = array('type' => 'file',
                         'preprocess' => TRUE,
                         'data' => $css_path);
      $data .= $optimizer->optimize($css_asset);
    }

    // Per the W3C specification at
    // http://www.w3.org/TR/REC-CSS2/cascade.html#at-import, @import
    // rules must precede any other style, so we move those to the
    // top.
    $regexp = '/@import[^;]+;/i';
    preg_match_all($regexp, $data, $matches);
    $data = preg_replace($regexp, '', $data);
    $data = implode('', $matches[0]) . $data;

    $response = self::getFarFutureResponse($data, 'text/css');
    return $response;
  }

  /**
   * Serves the requested path with optimal far future expiration headers.
   *
   * @param string $filename
   *   Packed representation of the file paths, type, and signature.
   *
   * @returns \Symfony\Component\HttpFoundation\Response
   *   The response that will efficiently send the requested file.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   Thrown for invalid types and failed parsing.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when an invalid signature is provided.
   */
  public function deliver($filename) {
    $parsed = self::validateAndParseFilename($filename, \Drupal::service('private_key'));

    if (FALSE === $parsed) {
      throw new AccessDeniedHttpException('Parsing or signature verification failed.');
    }

    // @TODO: Check that all paths are relative to Drupal's root.
    // @TODO: Move the extension check here to verify that all bundled files
    //        use the same extension as the aggregated path.

    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    if ('css' === $extension) {
      return $this->deliverCss($parsed);
    } else if ('js' == $extension) {
      return $this->deliverJs($parsed);
    }

    throw new BadRequestHttpException();
  }

}
