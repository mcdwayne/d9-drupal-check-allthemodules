<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/14/2016
 * Time: 2:00 PM
 */

namespace Drupal\Tests\forena\Unit;
use Drupal\forena\Skin;

/**
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\Skin
 */
class SkinTest extends FrxTestCase {

  /**
   * List available skins.
   */
  public function testSkinList() {
    $skins = $this->skins();
    // Check for skin library definition. 
    $this->assertArrayHasKey('default', $skins, 'Skin files detected');
    $this->assertEquals('Default Skin', $skins['default']);
  }

  /**
   * Test library creation
   */
  public function testReplaceMent() {
    // Make sure the skin loads
    $skin = Skin::instance('default');
    $this->assertObjectHasAttribute('info', $skin);

    // Examine the Library
    $info = $skin->replacedInfo();
    $library = $info['library'];
    $css = $library['css']['component'];
    $this->assertArrayNotHasKey('{skin.dir}/default_skin.css', $css);
    $keys = array_keys($css);
    $key = reset($keys);
    $this->assertContains('reports/default_skin.css', $key);
  }

  /**
   * Test Mechanism for replacing text
   */
  public function testMerge() {
    $skin = Skin::instance('default');

    $definition = [
      'libraries' =>
        [
          'core/drupal.dialog',
        ]
    ];

    $skin->merge($definition);

    $new_definition = $skin->info;
    $this->assertContains('core/drupal.dialog', $new_definition['libraries']);
    $this->assertContains('core/drupal.ajax', $new_definition['libraries']);
  }
}