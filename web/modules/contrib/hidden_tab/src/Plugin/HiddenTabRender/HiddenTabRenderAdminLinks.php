<?php

namespace Drupal\hidden_tab\Plugin\HiddenTabRender;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Drupal\hidden_tab\Plugable\Annotation\HiddenTabRenderAnon;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderAdministrativeBase;
use Drupal\hidden_tab\Plugable\Render\HiddenTabRenderSafeTrait;
use Drupal\hidden_tab\Plugable\Template\HiddenTabTemplatePluginManager;
use Drupal\hidden_tab\Utility;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Displays a set of administrative links on a hidden tab page.
 *
 * @HiddenTabRenderAnon(
 *   id = "hidden_tab_admin_links"
 * )
 *
 * @see \Drupal\hidden_tab\Plugin\HiddenTabTemplate\HiddenTabAdminLinksTemplate
 */
class HiddenTabRenderAdminLinks extends HiddenTabRenderAdministrativeBase {

  use HiddenTabRenderSafeTrait;

  /**
   * See id().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::id()
   */
  protected $PID = 'hidden_tab_admin_links';

  /**
   * See label().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::label()
   */
  protected $HTPLabel = 'Admin Links';

  /**
   * See description().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::description()
   */
  protected $HTPDescription = 'Displays a set of administrative links on a hidden tab page.';

  /**
   * See weight().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::weight()
   */
  protected $HTPWeight = -1;

  /**
   * See tags().
   *
   * @var string
   *
   * @see \Drupal\hidden_tab\Plugable\HiddenTabPluginInterfaceBase::tags()
   */
  protected $HTPTags = [];

  /**
   * {@inheritdoc}
   */
  protected function render0(EntityInterface $entity,
                             HiddenTabPageInterface $page,
                             AccountInterface $user,
                             ParameterBag $bag,
                             array &$output) {

    /** @var \Drupal\hidden_tab\Plugable\Template\HiddenTabTemplateInterface $p */
    $p = HiddenTabTemplatePluginManager::instance()
      ->plugin('hidden_tab_admin_links');

    $tab = Link::createFromRoute($this->t('<h2>Tab</h2>'), 'hidden_tab.tab_' . $page->id(), [
      'node' => $entity->id(),
    ])
      ->toRenderable();
    $edit = Link::createFromRoute($this->t('<h2>Page</h2>'), 'entity.hidden_tab_page.edit_form', [
      'hidden_tab_page' => $page->id(),
      'lredirect' => Utility::redirectHere(),
    ])
      ->toRenderable();
    $layout = Link::createFromRoute($this->t('<h2>Layout</h2>'), 'entity.hidden_tab_page.layout_form', [
      'hidden_tab_page' => $page->id(),
      'lredirect' => Utility::redirectHere(),
    ])
      ->toRenderable();

    $admin = $output['admin'];
    unset($output['admin']);
    $output[$this->id()] = [
      '#attached' => $p->attachLibrary(),
      '#theme' => 'hidden_tab_' . $p->id(),
      '#regions' => [
        'reg_0' => $tab,
        'reg_1' => $edit,
        'reg_2' => $layout,
      ],
    ];
    $output['admin'] = $admin;
  }

}
