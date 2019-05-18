<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\httpbl\Logger\HttpblLogTrapper' "modules/httpbl/src".
 *
 * IMPORTANT! This file was also edited because the generated script turned the
 * the constants into strings, which resulted in logic failures.
 *
 * For instance, HTTPBL_LOG_VERBOSE became 'HTTPBL_LOG_VERBOSE'.
 */

namespace Drupal\httpbl\ProxyClass\Logger {

    /**
     * Provides a proxy class for \Drupal\httpbl\Logger\HttpblLogTrapper.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class HttpblLogTrapper implements \Drupal\httpbl\Logger\HttpblLogTrapperInterface
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
         * @var \Drupal\httpbl\Logger\HttpblLogTrapper
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
        public function trapEmergency($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_QUIET)
        {
            return $this->lazyLoadItself()->trapEmergency($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapAlert($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_QUIET)
        {
            return $this->lazyLoadItself()->trapAlert($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapCritical($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_QUIET)
        {
            return $this->lazyLoadItself()->trapCritical($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapError($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_QUIET)
        {
            return $this->lazyLoadItself()->trapError($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapWarning($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_MIN)
        {
            return $this->lazyLoadItself()->trapWarning($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapNotice($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_MIN)
        {
            return $this->lazyLoadItself()->trapNotice($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapInfo($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_VERBOSE)
        {
            return $this->lazyLoadItself()->trapInfo($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function trapDebug($message, array $context = array (
        ), $logVolume = HTTPBL_LOG_VERBOSE)
        {
            return $this->lazyLoadItself()->trapDebug($message, $context, $logVolume);
        }

        /**
         * {@inheritdoc}
         */
        public function log($level, $message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->log($level, $message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function emergency($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->emergency($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function alert($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->alert($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function critical($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->critical($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function error($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->error($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function warning($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->warning($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function notice($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->notice($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function info($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->info($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function debug($message, array $context = array (
        ))
        {
            return $this->lazyLoadItself()->debug($message, $context);
        }

        /**
         * {@inheritdoc}
         */
        public function __sleep()
        {
            return $this->lazyLoadItself()->__sleep();
        }

        /**
         * {@inheritdoc}
         */
        public function __wakeup()
        {
            return $this->lazyLoadItself()->__wakeup();
        }

    }

}
