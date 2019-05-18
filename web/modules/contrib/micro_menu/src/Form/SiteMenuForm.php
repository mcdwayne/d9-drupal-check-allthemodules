<?php

namespace Drupal\micro_menu\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\menu_ui\MenuForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base form for site menu edit forms.
 * @Todo deleted this class as it is not used anymore. We use instead a RouteProcessor for altering link menu edit.
 */
class SiteMenuForm extends MenuForm {

  /**
   * The site entity.
   *
   * @var \Drupal\micro_site\Entity\SiteInterface|NULL
   */
  protected $site;


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\system\MenuInterface $menu */
    $menu = $this->entity;
    // We check if we are in a site entity context, and so get the site
    // parameter into the route, which is the site id.
    $site_route = $this->getRouteMatch()->getParameter('site');
    // And we check too if the menu is well a site entity menu.
    $site_id = $menu->getThirdPartySetting('micro_menu', 'site_id');
    $this->site = ($site_id && $site_route) ? $this->entityTypeManager->getStorage('site')->load($site_id) : NULL;

    $form = parent::form($form, $form_state);

    if ($this->site) {
      // Alter the operations link for for the menu entity content, and alter
      // the default Add link if menu empty. Because we want to stay in the site
      // path and not go to path /admin/structure/menu.
      $this->alterMenuLinkOperation($form);
    }



    return $form;
  }


  protected function alterMenuLinkOperation(&$form) {
    // Alter the default Add link if menu empty.
    $form['links']['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
      ':url' => $this->url('site.menu.add_link_form', ['site' => $this->site->id(), 'menu' => $this->entity->id()], [
        'query' => ['destination' => $this->url('entity.site.edit_menu', ['site' => $this->site->id(), 'menu' => $this->entity->id()])],
      ]),
    ]);
    // Alter the operations link for for the menu entity content.
    $elements = &$form['links']['links'];
    foreach (Element::children($elements) as $key) {
      if (isset($elements[$key]['#item'])) {
        /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menu_link_plugin */
        $menu_link_plugin = $elements[$key]['#item']->link;
        $uuid = $menu_link_plugin->getDerivativeId();
        $entity = $this->entityManager->loadEntityByUuid('menu_link_content', $uuid);
        foreach ($elements[$key]['operations']['#links'] as $operation => $link) {
          switch ($operation) {
            case 'edit':
              $elements[$key]['operations']['#links'][$operation]['url'] = Url::fromRoute('site.menu_link_content.canonical', ['site' => $this->site->id(), 'menu' => $this->entity->id(), 'menu_link_content' => $entity->id()]);
              break;
            case 'delete':
              $elements[$key]['operations']['#links'][$operation]['url'] = Url::fromRoute('site.menu_link_content.delete_form', ['site' => $this->site->id(), 'menu' => $this->entity->id(), 'menu_link_content' => $entity->id()]);
              break;
            //@TODO case translate.
          }
        }
      }
    }

  }


}
