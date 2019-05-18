<?php

/**
 * @file
 * Contains \Drupal\Tests\menu_link_weight\FunctionalJavascript\MenuLinkWeightJavascriptTestBase.
 */

namespace Drupal\Tests\menu_link_weight\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Base class for menu_link_weight javascript tests.
 */
abstract class MenuLinkWeightJavascriptTestBase extends JavascriptTestBase {

  use MenuLinkWeightTestTrait;

   /**
    * {@inheritdoc}
    */
   protected static $modules = ['block', 'menu_link_weight'];

   /**
    * The menu link tree service.
    *
    * @var \Drupal\Core\Menu\MenuLinkTreeInterface
    */
   protected $menuLinkTree;

   /**
    * The menu link manager.
    *
    * @var \Drupal\Core\Menu\MenuLinkManagerInterface
    */
   protected $menuLinkManager;

   /**
    * The content menu link storage.
    *
    * @var \Drupal\Core\Entity\EntityStorageInterface
    */
   protected $contentMenuLinkStorage;

   /**
    * The module installer.
    *
    * @var \Drupal\Core\Extension\ModuleInstallerInterface
    */
   protected $moduleInstaller;

   /**
    * The state service.
    *
    * @var \Drupal\Core\State\StateInterface
    */
   protected $state;

   /**
    * The node type used in this test.
    *
    * @var string
    */
   protected $nodeType = 'page';

   /**
    * Set up.
    */
   protected function setUp() {
     parent::setUp();

     $this->menuLinkTree = $this->container->get('menu.link_tree');
     $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
     /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
     $entity_type_manager = $this->container->get('entity_type.manager');
     $this->contentMenuLinkStorage = $entity_type_manager->getStorage('menu_link_content');
     $this->moduleInstaller = $this->container->get('module_installer');
     $this->state = $this->container->get('state');

     $this->drupalPlaceBlock('system_menu_block:tools');

     NodeType::create([
       'type' => $this->nodeType,
       'name' => $this->nodeType,
       'third_party_settings' => [
         'menu_ui' => [
           // Enable the Navigation menu as available menu.
           'available_menus' => ['tools'],
           // Change default parent item to Navigation menu, so we can assert
           // more easily.
           'parent' => 'tools:',
         ]
       ]
     ])->save();

     $permissions = array(
       'administer menu',
       "create {$this->nodeType} content",
       "edit own {$this->nodeType} content",
     );

     // Create user.
     $user = $this->drupalCreateUser($permissions);
     // Log in user.
     $this->drupalLogin($user);
   }

}
