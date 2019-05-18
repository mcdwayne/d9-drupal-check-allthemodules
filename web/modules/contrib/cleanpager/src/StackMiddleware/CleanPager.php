<?php

namespace Drupal\cleanpager\StackMiddleware;

use Drupal\cleanpager\EventSubscriber\CleanPagerSubscriber;
use Drupal\Core\Path\PathMatcher;
use \Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Provides a HTTP middleware.
 */
class CleanPager implements HttpKernelInterface {

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a new TestMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The decorated kernel.
   * @param mixed $optional_argument
   *   (optional) An optional argument.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  public function cleanPagerGetQ($request) {
    $q = isset($_GET['q']) ? $_GET['q'] : '';
    $current_path = $request->getPathInfo();
    $path = explode('/', $current_path);
    if ($path[1] == 'views' && $path[2] == 'ajax' && !empty($_POST['view_path'])) {
      $q = rtrim($_POST['view_path'], '/');
    }
    return $q ? $q : $current_path;
  }

  public function rewriteUrl(Request $request, $q) {
    global $_cleanpager_rewritten;
    $q_array = explode('/', $q);
    if (\Drupal::configFactory()->get('cleanpager.settings')->get('cleanpager_add_trailing')) {
      array_pop($q_array);
    }
    if ($this->cleanPagerIsPagerElement(end($q_array))) {
      $_cleanpager_rewritten = FALSE;
      $p = array_pop($q_array);
      if (end($q_array) == 'page') {
        array_pop($q_array);
        $_cleanpager_rewritten = TRUE;
        $q = implode('/', $q_array);
        $current_path = $request->getPathInfo();
        $path_args = explode('/', $current_path);
        if ($path_args[1] == 'views' && $path_args[2] == 'ajax' && !empty($_POST['view_path'])) {
          $q = '/views/ajax';
        }
        else {
          $_REQUEST['q'] = $_GET['q'] = $q;
          $request->query->set('q', $q);
        }
        $_REQUEST['page'] = $_GET['page'] = $p;
        $request->server->set('REQUEST_URI', $q);
        $request->server->set('REDIRECT_URL', $q);
        $query_string = $request->server->get('QUERY_STRING', $q);
        $request->server->set('QUERY_STRING', $query_string . '&page=' . $p);
        $request->query->add(['page' => $p]);
        $request->initialize($request->query->all(), $request->request->all(), $request->attributes->all(), $request->cookies->all(), $request->files->all(), $request->server->all(), $request->getContent());
      }
    }
    return $request;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    global $_cleanpager_pagination;
    $path = $this->cleanPagerGetQ($request);
    if ($path_length = strpos($path, '/page/')) {
      $path_test_part = substr($path, 0, $path_length);
    }
    else {
      $path_test_part = $path;
    }
    $pages = \Drupal::configFactory()->get('cleanpager.settings')->get('cleanpager_pages');
    if (\Drupal::service('path.matcher')->matchPath($path_test_part, $pages)) {
      $_cleanpager_pagination = TRUE;
      $result = $this->rewriteUrl($request, $path);
    }
    else {
      $_cleanpager_pagination = FALSE;
      $result = $request;
    }
    return $this->httpKernel->handle($result, $type, $catch);
  }


  public function cleanPagerIsPagerElement($value) {
    if (is_numeric($value)) {
      return TRUE;
    }
    // Handle multiple pagers (i.e. 0,0,1,0);
    $parts = explode(',', $value);
    foreach ($parts as $p) {
      if (!is_numeric($p)) {
        return FALSE;
      }
    }
    return TRUE;
  }

}
