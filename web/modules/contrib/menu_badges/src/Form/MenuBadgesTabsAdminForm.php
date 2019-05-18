<?php


namespace Drupal\menu_badges\Form;

use Drupal\Component\Plugin\Context\ContextInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Entity\Query\Query;
use Drupal\Core\Form\FormBase;
use Drupal\menu_badges\MenuBadgesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

class MenuBadgesTabsAdminForm extends FormBase {


  /**
   * The menu badge manager service.
   *
   * @var \Drupal\menu_badges\MenuBadgesManager
   */
  protected $badgeManager;

  /**
   * Constructs a \Drupal\system\SystemConfigFormBase object.
   */
  public function __construct(MenuBadgesManager $badgeManager) {
    $this->badgeManager = $badgeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu_badges.manager')
    );
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'menu_badges_tabs_admin';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $badges = $this->badgeManager->getLocalBadgesForRoutes();
    kint($badges);
    $form['#tree'] = TRUE;
    //$form['#attached']['css'] = array(drupal_get_path('module', 'menu_badges') . '/menu_badges.css');
    $form['search'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Search'),
    );
    $form['search']['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#size' => 30,
    );
    $form['search']['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Menu Path'),
      '#size' => 30,
    );
    $route_types = array(
      MenuBadgesManager::LOCAL_TASK => 'Tab',
      MenuBadgesManager::LOCAL_ACTION => 'Action Link',
    );
    $form['search']['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Menu Type'),
      '#options' => array(
        '' => '',
      ) + $route_types,
    );
    $form['search']['search'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    );

    $search_types = array();
    $values = $form_state->getValues();
    if (!empty($values['search']['type'])) {
      
      $search_types = array($form_state->getValue(['search', 'type']));
    }
    $search_title = !empty($values['search']['title']) ? $values['search']['title']: NULL;
    $search_path = !empty($values['search']['path']) ? $values['search']['path']: NULL;
    $routes = $this->badgeManager->getLocalRoutes($search_types, $search_title, $search_path);

    $manager = \Drupal::service('plugin.manager.link_badge');
    $definitions = $manager->getDefinitions();
    $menu_badge_options = array('' => t('None'));
    foreach ($definitions as $d) {
      $menu_badge_options[$d['id']] = $d['label'];
    }

    $form['results'] = array();

    foreach ($routes as $route_id => $record) {
      // Make the route ID specific to type
      $route_type_and_id = $record['menu_badges_route_type'] . '||' . str_replace('.', '|', $route_id);
      $route_id = str_replace('.', '|', $route_id);
      $form['results'][$route_type_and_id] = array();

      $form['results'][$route_type_and_id]['path'] = array(
        '#type' => 'value',
        '#value' => $record['menu_badges_route_path'],
      );
      $form['results'][$route_type_and_id]['title'] = array(
        '#type' => 'value',
        '#value' => $record['title'],
      );
      $form['results'][$route_type_and_id]['type'] = array(
        '#type' => 'value',
        '#value' => $route_types[$record['menu_badges_route_type']],
      );
      $form['results'][$route_type_and_id]['menu_badges_id'] = array(
        '#type' => 'select',
        '#options' => $menu_badge_options,
        '#default_value' => !empty($badges[$record['menu_badges_route_type']][$route_id]) ? $badges[$record['menu_badges_route_type']][$route_id]['id'] : '',
      );
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    $form['#theme'] = 'menu_badges_tabs_admin_form';

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $is_search = ($triggering_element['#parents'][0] == 'search' && $triggering_element['#parents'][1] == 'search');
    if ($is_search) {
      $form_state->setRebuild(TRUE);
    }
    else {
      $form_state->setRebuild(TRUE);

      $local_badges = $this->badgeManager->getLocalBadges();
      kint($local_badges);
      foreach ($form_state->getValue('results') as $route_id => $route) {
        $route_desc = explode('||', $route_id);
        if (!empty($route['menu_badges_id'])) {
          $local_badges[$route_desc[0]][$route_desc[1]] = ['id' => $route['menu_badges_id']];
        }
        elseif (!empty($local_badges[$route_desc[0]][$route_desc[1]])) {
          unset($local_badges[$route_desc[0]][$route_desc[1]]);
        }
      }
      $this->badgeManager->setLocalBadges($local_badges);
    }
  }
}
