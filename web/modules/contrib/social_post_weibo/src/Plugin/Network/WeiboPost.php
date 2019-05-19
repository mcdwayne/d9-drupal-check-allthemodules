<?php

namespace Drupal\social_post_weibo\Plugin\Network;

use SaeTOAuthV2;
use SaeTClientV2;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\social_api\SocialApiException;
use Drupal\social_post\Plugin\Network\SocialPostNetwork;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines Social Post Weibo Network Plugin.
 *
 * @Network(
 *   id = "social_post_weibo",
 *   social_network = "Weibo",
 *   type = "social_post",
 *   handlers = {
 *     "settings": {
 *        "class": "\Drupal\social_post_weibo\Settings\WeiboPostSettings",
 *        "config_id": "social_post_weibo.settings"
 *      }
 *   }
 * )
 */
class WeiboPost extends SocialPostNetwork implements WeiboPostInterface {

  use LoggerChannelTrait;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Weibo connection.
   *
   * @var \Abraham\WeiboOAuth\WeiboOAuth
   */
  protected $connection;

  /**
   * The tweet text.
   *
   * @var string
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('url_generator'),
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * WeiboPost constructor.
   *
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   Used to generate a absolute url for authentication.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(MetadataBubblingUrlGenerator $url_generator,
                              array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConfigFactoryInterface $config_factory) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $config_factory);

    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  protected function initSdk() {
    $class_name = 'SaeTOAuthV2';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The PHP SDK for Weibo could not be found. Class: %s.', $class_name));
    }

    /* @var \Drupal\social_post_weibo\Settings\WeiboPostSettings $settings */
    $settings = $this->settings;

    return new SaeTOAuthV2($settings->getConsumerKey(), $settings->getConsumerSecret());
  }

  /**
   * {@inheritdoc}
   */
  public function post($weibo_post_parms = array()) {
    if (!$this->connection) {
      throw new SocialApiException('Call post() method from its wrapper doPost()');
    }

    if($weibo_post_parms['image'] !== false){
      $file_local = $weibo_post_parms['image'];
    }

    // 拼接'http://weibosdk.sinaapp.com/'是因为这个share接口至少要带上一个【安全域名】下的链接。
    $post = $this->connection->share($this->status.'http://www.xmt.ren/node/'.$weibo_post_parms['nid'], $file_local);

    if (isset($post['error'])) {
      $this->getLogger('social_post_weibo')->error($post['error']);
      drupal_set_message('微博平台内容（nid '.$weibo_post_parms['nid'].'）, 发布失败！原因：'.$post['error'], 'error');
      return FALSE;
    }else {
      drupal_set_message('微博平台内容（nid '.$drupal_post_parms['nid'].'）, 发布成功！');
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function doPost($access_token, $status, $weibo_post_parms) {
    $this->connection = $this->getSdk2($access_token['access_token']);
    $this->status = $status;
    return $this->post($weibo_post_parms);
  }

  /**
   * {@inheritdoc}
   */
  public function getOauthCallback() {
    return $this->urlGenerator->generateFromRoute('social_post_weibo.callback', [], ['absolute' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSdk2($access_token) {
    /* @var \Drupal\social_post_weibo\Settings\WeiboPostSettings $settings */
    $settings = $this->settings;

    return new SaeTClientV2($settings->getConsumerKey(), $settings->getConsumerSecret(), $access_token);
  }

}
