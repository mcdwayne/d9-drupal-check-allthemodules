<?php
/**
 * This file was generated via php core/scripts/generate-proxy-class.php
 * 'Drupal\admin_language_negotiation\AdminLanguageNegotiationUninstallValidator' "modules/custom/admin_language_negotiation/src".
 */

namespace Drupal\admin_language_negotiation\ProxyClass {

  /**
   * Provides a proxy class for \Drupal\admin_language_negotiation\AdminLanguageNegotiationUninstallValidator.
   *
   * @see \Drupal\Component\ProxyBuilder
   * @codingStandardsIgnoreFile
   */
  class AdminLanguageNegotiationUninstallValidator implements \Drupal\Core\Extension\ModuleUninstallValidatorInterface {

    use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

    /**
     * The id of the original proxied service.
     *
     * @var string
     */
    protected $drupalProxyOriginalServiceId;

    /**
     * The real proxied service, after it was lazy loaded.
     *
     * @var \Drupal\admin_language_negotiation\AdminLanguageNegotiationUninstallValidator
     */
    protected $service;

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructs a ProxyClass Drupal proxy object.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container.
     * @param string                                                    $drupal_proxy_original_service_id
     *   The service ID of the original service.
     */
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id) {
      $this->container = $container;
      $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
    }

    /**
     * Lazy loads the real service from the container.
     *
     * @return object
     *   Returns the constructed real service.
     */
    protected function lazyLoadItself() {
      if (!isset($this->service)) {
        $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
      }

      return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($module_name) {
      return $this->lazyLoadItself()->validate($module_name);
    }

    /**
     * {@inheritdoc}
     */
    public function setStringTranslation(\Drupal\Core\StringTranslation\TranslationInterface $translation) {
      return $this->lazyLoadItself()->setStringTranslation($translation);
    }

  }

}
