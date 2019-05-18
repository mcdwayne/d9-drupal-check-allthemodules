<?php

namespace Drupal\say_hello_dialogflow;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\system\Entity\Menu;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Prepares the say hello dialogflow.
 */
class SayHelloDialogflow {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * SayHelloDialogflow constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   */
  public function __construct(ConfigFactoryInterface $config_factory, KillSwitch $killSwitch) {
    $this->configFactory = $config_factory;
    $this->killSwitch = $killSwitch;
    $this->config = $this->configFactory->get('say_hello_dialogflow.dialogflow_menu');

  }

  public function getConfig() {
    return $this->config;
  }

  public function getEditableConfig() {
    return $this->configFactory->getEditable('say_hello_dialogflow.dialogflow_menu');
  }

  /**
   * Returns a the Dialogflow render array.
   */
  public function getDialogflowComponent() {
    $this->killSwitch->trigger();

    if(empty($this->config->get('dialogflow_token'))
      || empty($this->config->get('dialogflow_domain'))
      || empty($this->config->get('dialogflow_baseurl'))
      || empty($this->config->get('dialogflow_debug'))
      || empty($this->config->get('dialogflow_default_intent_text'))
      || empty($this->config->get('dialogflow_menu'))
    ) {
      return [
        '#markup' => '',
        '#cache' => [
          'max-age' => 0
        ]
      ];
    }

    $render = [
      '#theme' => 'say_hello_dialogflow',
      '#cache' => [
        'max-age' => 0
      ]
    ];

    $dialogflow_menu = $this->config->get('dialogflow_menu');

    $render['#cache']['tags'] = $this->config->getCacheTags();
    $render['#attached'] = [
      'library' => [
        'say_hello_dialogflow/say_hello_dialogflow'
      ]
    ];
    $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_token'] = $this->config->get('dialogflow_token');
    $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_domain'] = $this->config->get('dialogflow_domain');
    $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_baseurl'] = $this->config->get('dialogflow_baseurl');
    $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_debug'] = $this->config->get('dialogflow_debug');
    $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_defaultintenttext'] = $this->config->get('dialogflow_default_intent_text');

    if (empty($dialogflow_menu) || $dialogflow_menu == "") {
      $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_menu'] = [];
    }

    $trimmed = str_replace(':','', $dialogflow_menu);
    $menu_items = $this->getMenuItems($trimmed);

    $m = Menu::load($trimmed);
    $render['#dialogflow_menu_link'] = $m->link();

    $render['#attached']['drupalSettings']['say_hello_dialogflow']['dialogflow']['dialogflow_menu'] = [
      'menu_items' => $menu_items
    ];

    return $render;
  }

  public function getMenuItems($menu_name) {

    $menu_data = $this->loadMenu($menu_name);

    if(empty($menu_data)) {
      return [];
    }

    $route_names = [];
    $menu = [];

    if(empty($menu_data['#items'])) {
      return $menu;
    }

    foreach ($menu_data['#items'] as $item) {
      if ($item['url']->getRouteName() == '') {
        $route = '';
        if(!in_array($route, $route_names)) {
          $menu[] = [
            'url' => $route,
            'callback' => "dialogflow_open_home_page",
            'title' => $item['title']
          ];

          $route_names[] = $route;
        }
      } else {
        $route = $item['url']->getInternalPath();
        $tolower = strtolower(str_replace(' ', '', $item['title']));
        if(!in_array($route, $route_names)) {
          $menu[] = [
            'url' => $route,
            'callback' => "dialogflow_open_{$tolower}_page",
            'title' => $item['title']
          ];

          $route_names[] = $route;
        }
      }
    }

    return $menu;
  }

  private function loadMenu($menu_name) {

    $menu_tree = \Drupal::menuTree();
    // Build the typical default set of menu tree parameters.
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    // Load the tree based on this set of parameters.
    $tree = $menu_tree->load($menu_name, $parameters);
    // Transform the tree using the manipulators you want.
    $manipulators = array(
      // Only show links that are accessible for the current user.
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      // Use the default sorting of menu links.
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);
    // Finally, build a renderable array from the transformed tree.
    $menu = $menu_tree->build($tree);

    return $menu;
  }
}