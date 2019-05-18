<?php

namespace Drupal\ajax_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception as Exception;

/**
 * Class DefaultController.
 */
class DefaultController extends ControllerBase {

  /**
   * Load.
   *
   * @return string
   *   Return Hello string.
   */
  public function load() {
    
    $ajax_path = $_SERVER['REDIRECT_SCRIPT_URL'];
    $post_url = $_POST['requestUrl'];

    foreach ($_SERVER as $key => $value) {
      if (strpos($value, $ajax_path) !== FALSE) {
        $_SERVER[$key] = str_replace($ajax_path, $post_url, $value);
      }
    }

    $parsed_url = parse_url($post_url);
    if (isset($parsed_url['query']) && !empty($parsed_url['query'])) {
      $_SERVER['QUERY_STRING'] = $parsed_url['query'];
    }

    if (!empty($_SERVER['QUERY_STRING'])) {
      parse_str(html_entity_decode($_SERVER['QUERY_STRING']), $_GET);
    }

    $new_request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

    $replace_request = \Drupal::requestStack()->pop();

    $path_alias = \Drupal::service('path.current')->getPath();
    if(preg_match('/node\/(\d+)/', $post_url, $matches)) {
      if (strpos($post_url, '/') !== 0) {
         $post_url = '/' . $post_url;
      }
      $system_path = $post_url;
      
    }
    else {
      $system_path = \Drupal::service('path.alias_manager')->getPathByAlias($post_url);
    }
    $new_request->attributes = $replace_request->attributes;

    $router = \Drupal::service('router.no_access_checks');
    $route = $router->match($system_path);

    $new_request->attributes->replace($route);


    \Drupal::requestStack()->push($new_request);

    $params = \Drupal\Core\Url::fromUserInput($system_path)->getRouteParameters();
    if (isset($params['node'])) {
      $entity_type = 'node';
      $view_mode = 'default';
      $node = \Drupal::entityTypeManager()->getStorage($entity_type)->load((int)$params['node']);

      $user = \Drupal::currentUser();

      if ($node->isPublished() && $user->hasPermission('access content')) {

        $loaded_node = \Drupal::entityTypeManager()->getViewBuilder($entity_type)->view($node, $view_mode);
        $bare_html_page_renderer = \Drupal::service('bare_html_page_renderer');

        $rendered_page = $bare_html_page_renderer->renderBarePage($loaded_node, 'preview-title', 'page');
        $rendered_page_markup = $rendered_page->getContent();

        // JS
        preg_match_all('#<script(.*?)<\/script>#is', $rendered_page_markup, $matches);
        // Remove all js from response to avoid useless requests
        foreach ($matches[0] as $value) {
          $pos = strpos($rendered_page_markup, $value);
          if ($pos !== false) {
            $rendered_page_markup = substr_replace($rendered_page_markup, '', $pos, strlen($value));
          }
        }

        // CSS
        preg_match_all('#<style(.*?)<\/style>#is', $rendered_page_markup, $css_style_matches);
        // Remove all css from response to avoid useless requests
        foreach ($css_style_matches[0] as $value) {
          $pos = strpos($rendered_page_markup, $value);
          if ($pos !== false) {
            $rendered_page_markup = substr_replace($rendered_page_markup, '', $pos, strlen($value));
          }
        }

        // Inline CSS
        preg_match_all('#<link rel="stylesheet"(.*?) \/>#is', $rendered_page_markup, $css_matches);
        // Remove all inline css from response to avoid useless requests
        foreach ($css_matches[0] as $value) {
          $pos = strpos($rendered_page_markup, $value);
          if ($pos !== false) {
            $rendered_page_markup = substr_replace($rendered_page_markup, '', $pos, strlen($value));
          }
        }

        // Body
        preg_match_all('#<body(.*?)<\/body>#is', $rendered_page_markup, $body_matches);

        // Title Site name
        $config = \Drupal::config('system.site');
        $site_variables['site']['name'] = $config->get('name');
        
        $response_markup = [
          'inline_css' => $css_style_matches,
          'title' => $node->getTitle() . ' | ' . $site_variables['site']['name'],
          'js' => $matches[0],
          'css' => $css_matches[0],
          'content' => $body_matches[0][0],
        ];

        return new Response(json_encode($response_markup));
      }
    }
    throw new Exception\NotFoundHttpException();
  }
}
