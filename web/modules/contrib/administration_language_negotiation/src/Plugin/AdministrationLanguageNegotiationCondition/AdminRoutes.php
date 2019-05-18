<?php

namespace Drupal\administration_language_negotiation\Plugin\AdministrationLanguageNegotiationCondition;

use Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionBase;
use Drupal\administration_language_negotiation\AdministrationLanguageNegotiationConditionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\Router;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Class for the Blacklisted paths condition plugin.
 *
 * @AdministrationLanguageNegotiationCondition(
 *   id = "admin_routes",
 *   weight = -50,
 *   name = @Translation("Admin Routes"),
 *   description = @Translation("Returns particular language on admin routes.")
 * )
 */
class AdminRoutes extends AdministrationLanguageNegotiationConditionBase implements
    AdministrationLanguageNegotiationConditionInterface
{

    /**
     * The request stack.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected $requestStack;

    /**
     * The router manager.
     *
     * @var \Drupal\Core\Routing\Router
     */
    protected $router;

    /**
     * The admin context.
     *
     * @var \Drupal\Core\Routing\AdminContext
     */
    protected $adminContext;

    /**
     * AdminRoutes constructor.
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
     *   The request stack.
     * @param \Drupal\Core\Routing\Router $router
     *   The router.
     * @param \Drupal\Core\Routing\AdminContext $admin_context
     *   The admin context.
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin_id for the plugin instance.
     * @param array $plugin_definition
     *   The plugin implementation definition.
     */
    public function __construct(
        RequestStack $request_stack,
        Router $router,
        AdminContext $admin_context,
        array $configuration,
        $plugin_id,
        array $plugin_definition
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->requestStack = $request_stack;
        $this->router = $router;
        $this->adminContext = $admin_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $container->get('request_stack'),
            $container->get('router.no_access_checks'),
            $container->get('router.admin_context'),
            $configuration,
            $plugin_id,
            $plugin_definition
        );
    }

    /**
     * {@inheritdoc}
     */
    public function evaluate()
    {
        $active = $this->configuration[$this->getPluginId()];

        return ($active && $this->isAdminRoute()) ? $this->block() : $this->pass();
    }

    /**
     * Checks if the current request is admin route.
     *
     * @return bool
     *   TRUE if the current request is admin route.
     */
    public function isAdminRoute()
    {
        // If called from an event subscriber, the request may not have
        // the route object yet (it is still being built).
        try {
            $match = $this->router->matchRequest($this->requestStack->getCurrentRequest());
        } catch (ResourceNotFoundException $e) {
            return false;
        } catch (AccessDeniedHttpException $e) {
            return false;
        }
        if (($match) && isset($match[RouteObjectInterface::ROUTE_OBJECT])) {
            return $this->adminContext->isAdminRoute($match[RouteObjectInterface::ROUTE_OBJECT]);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form[$this->getPluginId()] = [
            '#title' => $this->t('Enable'),
            '#type' => 'checkbox',
            '#default_value' => $this->configuration[$this->getPluginId()],
            '#description' => $this->t(
                'Detects if the current path is admin route.'
            ),
        ];

        return $form;
    }
}
