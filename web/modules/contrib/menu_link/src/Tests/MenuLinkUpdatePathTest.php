<?php

namespace Drupal\menu_link\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests the menu_link module install/update path.
 * @group menu_link
 */
class MenuLinkUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../tests/fixtures/database-dump-multilingual.php'
    ];
  }

  public function testInstall() {
    // The tree structure looks like the following:
    // - Home
    // - non multi-lang 1
    // --- non multi-lang 2
    // - multi-lang 1 (EN|FR)
    // --- multi-lang 2 (EN|FR)

    // Testing the structure before the update script.
    $tree = array_values(\Drupal::menuTree()->load('main', new MenuTreeParameters()));
    $this->assertEqual(3, count($tree));

    $this->assertEqual(1, count($tree[1]->subtree));
    $this->assertEqual('non multi-lang 1', $tree[1]->link->getTitle());
    $this->assertEqual('', $tree[1]->link->getParent());

    $child = array_values($tree[1]->subtree)[0];
    $this->assertEqual('non multi-lang 2', $child->link->getTitle());
    $this->assertEqual($tree[1]->link->getPluginId(), $child->link->getParent());

    $this->assertEqual(1, count($tree[2]->subtree));
    $this->assertEqual('multi lang 1 - EN', $tree[2]->link->getTitle());
    $this->assertEqual('', $tree[2]->link->getParent());

    $child = array_values($tree[2]->subtree)[0];
    $this->assertEqual('multi lang 2 - EN', $child->link->getTitle());
    $this->assertEqual($tree[2]->link->getPluginId(), $child->link->getParent());

    \Drupal::service('module_installer')->install(['menu_link']);
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    // Testing the structure after the update script.
    $tree2 = array_values(\Drupal::menuTree()->load('main', new MenuTreeParameters()));
    $this->assertEqual(3, count($tree2));

    $this->assertEqual(1, count($tree2[1]->subtree));
    $this->assertEqual('non multi-lang 1', $tree2[1]->link->getTitle());
    $this->assertTrue(strpos($tree2[1]->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual('', $tree2[1]->link->getParent());

    $child = array_values($tree2[1]->subtree)[0];
    $this->assertEqual('non multi-lang 2', $child->link->getTitle());
    $this->assertTrue(strpos($child->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual($tree2[1]->link->getPluginId(), $child->link->getParent());

    $this->assertEqual(1, count($tree2[2]->subtree));
    $this->assertEqual('multi lang 1 - EN', $tree2[2]->link->getTitle());
    $this->assertTrue(strpos($tree2[2]->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual('', $tree2[2]->link->getParent());

    $child = array_values($tree2[2]->subtree)[0];
    $this->assertEqual('multi lang 2 - EN', $child->link->getTitle());
    $this->assertTrue(strpos($child->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual($tree2[2]->link->getPluginId(), $child->link->getParent());

    // Check the translated variants as well.
    // Therefore switch the current language back to EN.
    $reflection = new \ReflectionClass(\Drupal::languageManager());
    $property = $reflection->getProperty('negotiatedLanguages');
    $property->setAccessible(TRUE);
    $property->setValue(\Drupal::languageManager(), [LanguageInterface::TYPE_CONTENT => ConfigurableLanguage::load('fr')]);
    $this->assertEqual(\Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(), 'fr');
    \Drupal::service('entity_type.manager')->getStorage('node')->resetCache();

    $tree2_en = array_values(\Drupal::menuTree()->load('main', new MenuTreeParameters()));
    // The home and one art
    $this->assertEqual(3, count($tree2_en));

    $this->assertEqual(1, count($tree2_en[1]->subtree));
    $this->assertEqual($tree2_en[1]->link->getPluginId(), $tree2[1]->link->getPluginId());
    $this->assertEqual('non multi-lang 1', $tree2_en[1]->link->getTitle());
    $this->assertTrue(strpos($tree2_en[1]->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual('', $tree2_en[1]->link->getParent());

    $child_en = array_values($tree2_en[1]->subtree)[0];
    $this->assertEqual($child_en->link->getPluginId(), array_values($tree2_en[1]->subtree)[0]->link->getPluginId());
    $this->assertEqual('non multi-lang 2', array_values($tree2_en[1]->subtree)[0]->link->getTitle());
    $this->assertTrue(strpos($child_en->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual($tree2_en[1]->link->getPluginId(), $child_en->link->getParent());

    $this->assertEqual(1, count($tree2_en[2]->subtree));
    $this->assertEqual($tree2_en[2]->link->getPluginId(), $tree2[2]->link->getPluginId());
    $this->assertEqual('multi lang 1 - FR', $tree2_en[2]->link->getTitle());
    $this->assertTrue(strpos($tree2_en[2]->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual('', $tree2_en[2]->link->getParent());

    $child_en = array_values($tree2_en[2]->subtree)[0];
    $this->assertEqual($child_en->link->getPluginId(), array_values($tree2_en[2]->subtree)[0]->link->getPluginId());
    $this->assertEqual('multi lang 2 - FR', $child_en->link->getTitle());
    $this->assertTrue(strpos($child_en->link->getPluginId(), 'menu_link_content:') === FALSE);
    $this->assertEqual($tree2_en[2]->link->getPluginId(), $child_en->link->getParent());
  }

}
