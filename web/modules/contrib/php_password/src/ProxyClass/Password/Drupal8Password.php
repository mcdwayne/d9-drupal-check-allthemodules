<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\php_password\Password\Drupal8Password' "web/modules/contrib/php_password/src".
 */

namespace Drupal\php_password\ProxyClass\Password {

    /**
     * Provides a proxy class for \Drupal\php_password\Password\Drupal8Password.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class Drupal8Password implements \Drupal\Core\Password\PasswordInterface
    {

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
         * @var \Drupal\php_password\Password\Drupal8Password
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
         * @param string $drupal_proxy_original_service_id
         *   The service ID of the original service.
         */
        public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function hash($password)
        {
            return $this->lazyLoadItself()->hash($password);
        }

        /**
         * {@inheritdoc}
         */
        public function check($password, $hash)
        {
            return $this->lazyLoadItself()->check($password, $hash);
        }

        /**
         * {@inheritdoc}
         */
        public function needsRehash($hash)
        {
            return $this->lazyLoadItself()->needsRehash($hash);
        }

    }

}
