<?php

namespace Drupal\ajax_links_api\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ajaxify Drupal Link.
 */
class AjaxLinksApi extends ControllerBase {

  /**
   * Ajax Links Api Service.
   *
   * @var \Drupal\ajax_links_api\Plugin\AjaxLinksApiService
   */
  protected $ajaxLinksApiService;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Class constructor.
   */
  public function __construct($ajaxLinksApiService, AliasManagerInterface $alias_manager) {
    $this->ajaxLinksApiService = $ajaxLinksApiService;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ajax_links_api.ajax_link'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * Ajax links API Demo.
   */
  public function demo() {
    $account = $this->currentUser();
    $uid = $account->id();
    $userpath = $this->aliasManager->getAliasByPath('/user/' . $uid);
    $ajax_links_api_demo_url = Url::fromRoute('ajax_links_api.democontent');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'test',
        ),
        'rel' => '.test1',
      ),
    );
    $ajax_links_api_demo_url->setOptions($link_options);
    $link1 = $this->l('Click here', $ajax_links_api_demo_url);
    $link2 = $this->ajaxLinksApiService->lAjax('load this test page', '/ajax-links-api/test', '#content', array(
      'attributes' => array(
        'class' => array('ajax-links-api'),
      ),
    ));
    $link3 = $this->ajaxLinksApiService->lAjax('User', $userpath, '.test2');

    $ouptut = '<h2>Method 1 : load a test page </h2>(link with class="test" and rel=".test1". You can ajaxify this link by adding this link
    class .test in admin settings):<br />' . $link1 . '<div class="test1"></div>';
    $ouptut .= '<h2>Method 2 : load this test page </h2>(using lAjax):<br />' . $link2 . '';
    $ouptut .= '<h2>Method 3 : Load profile </h2>(link with class="ajax-link" and rel=".test2"):<br />' . $link3 . '<div class="test2"></div>';

    return array(
      '#markup' => $ouptut,
    );
  }

  /**
   * Ajax links API Demo Content.
   */
  public function demoContent() {
    return array(
      '#markup' => '<div>Ajax loaded content!</div>',
    );
  }

}
