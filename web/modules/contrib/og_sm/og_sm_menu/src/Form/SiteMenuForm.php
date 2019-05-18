<?php

namespace Drupal\og_sm_menu\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\og_sm_menu\SiteMenuManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the site menu.
 */
class SiteMenuForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The site menu manager service.
   *
   * @var \Drupal\og_sm_menu\SiteMenuManagerInterface
   */
  protected $siteMenuManager;

  /**
   * Constructs a SiteMenuForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param SiteMenuManagerInterface $site_menu_manager
   *   The site menu manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SiteMenuManagerInterface $site_menu_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->siteMenuManager = $site_menu_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og_sm.site_menu_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_sm_site_menu';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $menu = $this->siteMenuManager->getCurrentMenu();
    $form_object = $this->getEntityForm($menu);
    $form_state->setFormObject($form_object);
    $form += $form_object->buildForm([], $form_state);

    // We never want to delete this form from within a site context.
    $form['actions']['delete']['#access'] = FALSE;

    if (!is_array($form['links'])) {
      return $form;
    }

    foreach (Element::children($form['links']) as $key) {
      if (!isset($form['links'][$key]['operations']["#links"])) {
        continue;
      }

      foreach ($form['links'][$key]['operations']["#links"] as &$operation) {
        /* @var \Drupal\Core\Url $url */
        $url = $operation['url'];
        $site = $menu->getGroup();

        if ($url->getRouteName() === 'menu_ui.link_edit') {
          $route_parameters = $url->getRouteParameters() + ['node' => $site->id()];
          $operation['url'] = Url::fromRoute('og_sm.site_menu.edit_link', $route_parameters);
        }
        else {
          $url->setRouteParameter('og_sm_context_site_id', $site->id());
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->getFormObject()->submitForm($form, $form_state);
  }

  /**
   * Gets the entity form for this menu.
   *
   * @param \Drupal\og_menu\OgMenuInstanceInterface $menu
   *   The menu entity.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   The entity form.
   */
  protected function getEntityForm(OgMenuInstanceInterface $menu) {
    $entity_form = $this->entityTypeManager->getFormObject('ogmenu_instance', 'edit');
    $entity_form->setEntity($menu);
    return $entity_form;
  }

}
