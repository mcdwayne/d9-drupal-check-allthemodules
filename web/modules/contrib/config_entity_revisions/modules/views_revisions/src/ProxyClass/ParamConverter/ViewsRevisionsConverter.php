<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\views_revisions\ParamConverter\ViewsRevisionsConverter' "modules/custom/config_entity_revisions/modules/views_revisions/src".
 */

namespace Drupal\views_revisions\ProxyClass\ParamConverter {

    /**
     * Provides a proxy class for \Drupal\views_revisions\ParamConverter\ViewsRevisionsConverter.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class ViewsRevisionsConverter implements \Drupal\Core\ParamConverter\ParamConverterInterface
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
         * @var \Drupal\views_revisions\ParamConverter\ViewsRevisionsConverter
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
        public function convert($value, $definition, $name, array $defaults)
        {
            return $this->lazyLoadItself()->convert($value, $definition, $name, $defaults);
        }

        /**
         * {@inheritdoc}
         */
        public function applies($definition, $name, \Symfony\Component\Routing\Route $route)
        {
            return $this->lazyLoadItself()->applies($definition, $name, $route);
        }

        /**
         * {@inheritdoc}
         */
        public function module_name()
        {
            return $this->lazyLoadItself()->module_name();
        }

        /**
         * {@inheritdoc}
         */
        public function config_entity_name()
        {
            return $this->lazyLoadItself()->config_entity_name();
        }

        /**
         * {@inheritdoc}
         */
        public function revisions_entity_name()
        {
            return $this->lazyLoadItself()->revisions_entity_name();
        }

        /**
         * {@inheritdoc}
         */
        public function setting_name()
        {
            return $this->lazyLoadItself()->setting_name();
        }

        /**
         * {@inheritdoc}
         */
        public function title()
        {
            return $this->lazyLoadItself()->title();
        }

        /**
         * {@inheritdoc}
         */
        public function has_own_content()
        {
            return $this->lazyLoadItself()->has_own_content();
        }

        /**
         * {@inheritdoc}
         */
        public function content_entity_type()
        {
            return $this->lazyLoadItself()->content_entity_type();
        }

        /**
         * {@inheritdoc}
         */
        public function content_parameter_name()
        {
            return $this->lazyLoadItself()->content_parameter_name();
        }

        /**
         * {@inheritdoc}
         */
        public function content_parent_reference_field()
        {
            return $this->lazyLoadItself()->content_parent_reference_field();
        }

        /**
         * {@inheritdoc}
         */
        public function admin_permission()
        {
            return $this->lazyLoadItself()->admin_permission();
        }

        /**
         * {@inheritdoc}
         */
        public function has_canonical_url()
        {
            return $this->lazyLoadItself()->has_canonical_url();
        }

    }

}
