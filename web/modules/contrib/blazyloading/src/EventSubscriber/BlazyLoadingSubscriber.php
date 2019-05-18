<?php

namespace Drupal\blazyloading\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Event subscriber class BlazyLoadingSubscriber.
 */
class BlazyLoadingSubscriber implements EventSubscriberInterface {

  /**
   * Default class of the blazy loading.
   *
   * @var string
   */
  protected $blazyClass = 'b-lazy lazy_load_image';

  /**
   * Default Image url.
   *
   * @var string
   */
  protected $defaultImageUrl;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $blazyConfigFact;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new DynamicPageCacheSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Object data.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Object data.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity type manager service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityManager
  ) {
    $this->blazyConfigFact = $configFactory->get('blazyloading_configuration.settings');
    $this->currentUser = $currentUser;
    $this->entityManager = $entityManager;

    // Load the loading image form the database.
    $this->defaultImageUrl = '/' . drupal_get_path('module', 'blazyloading') . '/images/loader.gif';
    if ($this->blazyConfigFact->get('loading_icon_file')) {
      $file = $this->entityManager->getStorage('file')->load($this->blazyConfigFact->get('loading_icon_file'));
      if ($file) {
        $url = ImageStyle::load('medium')->buildUrl($file->getFileUri());
        $this->defaultImageUrl = $url;
      }
    }

    // Add the config css class to the blazy class.
    $this->blazyClass = $this->blazyClass . " " . $this->blazyConfigFact->get('css_class');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 150];
    return $events;
  }

  /**
   * Replace all legacy,node/{nodeid} url to Drupal alias.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Object of Response Event.
   */
  public function onResponse(FilterResponseEvent $event) {
    // Check the data in dynamic cache.
    $response = $event->getResponse();
    // Dynamic Page Cache only works with cacheable responses. It does not work
    // with plain Response objects. (Dynamic Page Cache needs to be able to
    // access and modify the cacheability metadata associated with the
    // response.)
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // Check the lazy loading configuration cache tag is set in
    // the drupal or not.
    $response_cacheability = $response->getCacheableMetadata()->getCacheTags();
    if (in_array('config:blazyloading.configuration', $response_cacheability)) {
      return;
    }

    // Check the lazy loading status.
    $blazy_loading_status = $this->blazyConfigFact->get('blazy_loading_status');
    $lazy_loading_status = FALSE;
    $image_url = [];
    if ($blazy_loading_status) {
      // Get the roles for which lazy loading should be work.
      $blazy_roles = $this->blazyConfigFact->get('blazy_roles');
      // Get the status of loading icon status.
      $loading_icon_status = $this->blazyConfigFact->get("loading_icon_status");
      // Get the image urls which should be removed from the blazy loading.
      $image_url = array_map('trim', explode("\n", $this->blazyConfigFact->get('image_urls')));

      if (empty($blazy_roles)) {
        $lazy_loading_status = TRUE;
      }
      else {
        foreach ($this->currentUser->getRoles() as $value) {
          if (in_array($value, $blazy_roles) && $blazy_roles[$value]) {
            $lazy_loading_status = TRUE;
            break;
          }
        }
      }
    }

    // Check that lazy loading status and apply blazy loading.
    if ($lazy_loading_status) {

      // Get the response of the all page.
      $content = $response->getContent();

      // Get all the image tag from the data.
      preg_match_all('/<img[^>]+>/i', $content, $result);
      foreach ($result[0] as $img_tags) {
        preg_match_all('/(class|src)=("[^"]*")/i', $img_tags, $all_image_url);
        if (!empty($all_image_url)) {
          $new_image_tag = $img_tags;
          // Get the key of the SRC attribute.
          $src_key = array_search("src", $all_image_url[1]);
          $src = (isset($all_image_url[1][$src_key]) && $all_image_url[1][$src_key] == 'src') ? TRUE : FALSE;

          // Get the key of the class attribute.
          $class_key = array_search("class", $all_image_url[1]);
          $class = (isset($all_image_url[1][$class_key]) && $all_image_url[1][$class_key] == 'class') ? TRUE : FALSE;

          $class_value = "";
          if (!$class) {
            $class_value = "class='" . $this->blazyClass . "'";
          }

          if ($src) {
            $old_src = str_replace('"', "", $all_image_url[2][$src_key]);
            if (in_array($old_src, $image_url)) {
              continue;
            }
            else {
              if ($loading_icon_status) {
                $src_value = "src='" . $this->defaultImageUrl . "'";
              }
              else {
                $src_value = "src=''";
              }

              $data_src_value = 'data-src="' . $old_src . '"';
              $new_image_tag = str_replace($all_image_url[0][$src_key], $src_value . " " . $data_src_value . " " . $class_value, $new_image_tag);
              if ($class) {
                $new_class_value = 'class="' . $this->blazyClass . ' ' . str_replace('"', "", $all_image_url[2][$class_key]) . '"';
                $new_image_tag = str_replace($all_image_url[0][$class_key], $new_class_value, $new_image_tag);
              }
              $content = str_replace($img_tags, $new_image_tag, $content);
            }
          }
        }
      }
      $response->setContent($content);
      $per_permissions_response_for_anon = new CacheableMetadata();
      $per_permissions_response_for_anon->setCacheTags(['config:blazyloading.configuration']);
      $response->addCacheableDependency($per_permissions_response_for_anon);
      $event->setResponse($response);
    }
  }

}
