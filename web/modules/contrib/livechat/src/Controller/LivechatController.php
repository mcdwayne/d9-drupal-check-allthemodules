<?php

namespace Drupal\livechat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * LivechatController class.
 */
class LivechatController extends ControllerBase {

  /**
   * The curerent request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Request $request) {
    $this->configFactory = $config_factory;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Method getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ['livechat.settings'];
  }

  /**
   * Method adminForm().
   */
  public function adminForm() {
    $settings = $this->config('livechat.settings');

    $livechat_props['licence_number'] = $settings->get('licence_number');
    $livechat_props['login'] = $settings->get('livechat_login');
    $livechat_props['mobile'] = $settings->get('livechat_mobile');

    $host = $this->request->getSchemeAndHttpHost();

    $render = [
      '#theme' => 'livechat_settings',
    ];

    $render['#attached'] = [
      'library' => [
        'livechat/livechat_css',
        'livechat/livechat_admin',
      ],
    ];

    $url_saveLicense = Url::fromUri('internal:/admin/config/services/livechat/saveLicense');
    $url_saveProps = Url::fromUri('internal:/admin/config/services/livechat/saveProperties');
    $url_reset = Url::fromUri('internal:/admin/config/services/livechat/reset');

    $render['#attached']['drupalSettings']['livechat']['livechat_admin']['livechat_props'] = $livechat_props;
    $render['#attached']['drupalSettings']['livechat']['livechat_admin']['save_license_url']
      = $host . $url_saveLicense->toString();
    $render['#attached']['drupalSettings']['livechat']['livechat_admin']['reset_properties_url']
      = $host . $url_reset->toString();
    $render['#attached']['drupalSettings']['livechat']['livechat_admin']['save_properties_url']
      = $host . $url_saveProps->toString();

    return $render;
  }

  /**
   * Method saveLicense().
   */
  public function saveLicense(Request $request) {
    $settings = $this->configFactory->getEditable('livechat.settings');

    $settings->set('licence_number', filter_var($request->request->get('license'),
        FILTER_SANITIZE_NUMBER_INT))->save();
    $settings->set('livechat_login', filter_var($request->request->get('email'),
        FILTER_SANITIZE_EMAIL))->save();
    $settings->set('livechat_mobile', 'No')->save();
    drupal_flush_all_caches();

    return new JsonResponse(['save_license' => 'success']);
  }

  /**
   * Method saveProperties().
   */
  public function saveProperties(Request $request) {
    $settings = $this->configFactory->getEditable('livechat.settings');

    $settings->set('livechat_mobile', filter_var($request->request->get('mobile'),
        FILTER_SANITIZE_STRING))->save();
    drupal_flush_all_caches();

    return new JsonResponse(['save_properties' => 'success']);
  }

  /**
   * Method resetProps().
   */
  public function resetProps(Request $request) {
    $settings = $this->configFactory->getEditable('livechat.settings');

    $settings->set('licence_number', '0')->save();
    $settings->set('livechat_login', '0')->save();
    $settings->set('livechat_mobile', '0')->save();
    drupal_flush_all_caches();

    return new JsonResponse(['settings_reset' => 'success']);
  }

}
