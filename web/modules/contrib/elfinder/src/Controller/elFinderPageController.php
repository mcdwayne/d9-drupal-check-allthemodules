<?php

namespace Drupal\elfinder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

class elFinderPageController extends ControllerBase {

  public function getContent($scheme, Request $request) {
    $build = array( 
      '#type' => 'markup',
      '#markup' => t('Hello World!'),
    );
    return $build;

  }

  public function getBrowser($scheme, Request $request,  RouteMatchInterface $route_match) {
    

    $build = $this->buildBrowserPage(FALSE);
    
       // '#markup' => '<div id="finder">test</div>',
    $build['#theme'] = 'browser_page';
    /*
     setting alpha1 variable for browser-page template (browser-page.html.twig)
     !!! Clear cache in Admin backend to see template/theme changes (you MUST do it if caching disabled too) !!! 
    */
    $build['#alpha1'] = 'm,6m2mm';
    
    $regions = array(
        
        'sidebar_first' => array(
            //'#theme' => 'elfinder_page',
            '#markup' => 'ttt'
        )
    );
    
    return \Drupal::service('bare_html_page_renderer')->renderBarePage($build, t('File manager'), 'elfinder_page', $regions);
    
  }
  
  public function getBrowserPage($scheme, Request $request,  RouteMatchInterface $route_match) {
    $build = array();

    $build['elfinder-admin-container'] = $this->buildBrowserPage(TRUE);
   // $build['elfinder-admin-container']['#markup'] = t('<div id="finder"></div>');
    $build['elfinder-admin-container']['#theme'] = 'browser_page';

    return $build;
    
  }


  public function checkAccess($scheme) {
    return AccessResult::allowedIf(TRUE);
  }
  
  public function buildBrowserPage($is_page_layout = FALSE) {
  
    global $language;

    $path = drupal_get_path('module', 'elfinder');
    $editorApp = '';
    $langCode = isset($language->language) ? $language->language : 'en';

    if (isset($_GET['app'])) {
      if (preg_match("/^[a-zA-Z]+$/", $_GET['app'])) {
        $editorApp = $_GET['app'];
      }
      elseif (preg_match("/^([a-zA-Z]+)|/", $_GET['app'], $m)) {
        $editorApp = $m[1];
      }
    }

    if (isset($_GET['langCode'])) {
      if (preg_match("/^[a-zA-z]{2}$/", $_GET['langCode'])) {
        $langCode = $_GET['langCode'];
      }
    }
    
    $token_generator = \Drupal::csrfToken();
    
  //  $url = Url::fromRoute('elfinder.connector')->toString();

   //drupal_set_message(var_export($args['url'],true));
    
    $elfinder_js_settings = array(
      'editorApp' => $editorApp,
      'langCode' => $langCode,
      'rememberLastDir' => \Drupal::config('elfinder.settings')->get('misc.rememberlastdir')  == 'true' ? TRUE : FALSE, // remember last opened directory
      'disabledCommands' => elfinder_get_disabled_commands(),
      'requestType' => 'get',
      'browserMode' => $browser_mode,
      'token' => $token_generator->get(),
      'moduleUrl' => ($is_page_layout ? \Drupal::url('elfinder') : \Drupal::request()->getRequestUri()),
      'connectorUrl' => ($is_page_layout ? \Drupal::url('elfinder.connector') : \Drupal::request()->getRequestUri() . '/connector'), // FIXME: \Drupal::url('elfinder.connector') throws exception

      
    );




    $build = array();
    
    $build['#attached']['library'][] = 'elfinder/drupal.elfinder';
    
    $build['#attached']['drupalSettings']['elfinder'] = $elfinder_js_settings;
    
    return $build;
  }


}
