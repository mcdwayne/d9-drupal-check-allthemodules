<?php

namespace Drupal\show_as_expanded_always;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ShowAsExpandedAlwaysManager that checks and sets the expanded field.
 *
 * @package Drupal\show_as_expanded_always
 */
class ShowAsExpandedAlwaysManager implements ContainerInjectionInterface {

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  protected $configFactory;

  /**
   * ShowAsExpandedAlwaysManager Constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Current route match service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Current config factory.
   */
  public function __construct(RouteMatchInterface $route_match, ConfigFactoryInterface $configFactory) {
    $this->routeMatch = $route_match;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('config.factory')
    );
  }

  /**
   * Set the default value of "show as expanded" - checkbox to true.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if ('menu_link_content_menu_link_content_form' === $form_id
      && $this->routeMatch->getRouteName() == 'entity.menu.add_link_form') {

      /** @var \Drupal\system\Entity\Menu $menu */
      $menu = $this->routeMatch->getParameter('menu');

      $enableShowAsExpanded = TRUE;

      if ($menu) {
        $config = $this->configFactory->get('show_as_expanded_always.configuration');
        if (NULL !== $config->get('enable_' . $menu->id())) {
          $enableShowAsExpanded = $config->get('enable_' . $menu->id());
        }
      }

      if (isset($form['expanded']['widget']['value']['#default_value'])) {
        $form['expanded']['widget']['value']['#default_value'] = $enableShowAsExpanded;
      }
    }
  }

}
