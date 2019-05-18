<?php

namespace Drupal\Tests\config_actions\Unit;

use Drupal\config_actions\ConfigActionsTransform;
use Drupal\Tests\UnitTestCase;
use Drupal\Component\Serialization\Yaml;

/**
 * test the ConfigActionsTransform class
 *
 * @coversDefaultClass \Drupal\config_actions\ConfigActionsTransform
 * @group config_actions
 */
class ConfigActionsTransformTest extends UnitTestCase {

  /**
   * @covers ::replace
   */
  public function testReplace() {
    $source = Yaml::decode(file_get_contents(dirname(__FILE__) . "/node.type.page.yml"));
    $replace = [
      "needs_review" => "approver",
      "workbench" => "workflow",
      "@bundle@" => "page"
    ];

    $output = ConfigActionsTransform::replace($source, $replace);
    $new_source = $source;
    $new_source['dependencies']['module'][1] = 'workflow_moderation';
    // 'workbench' only replaced with 'workflow' in values
    $new_source['third_party_settings']['workbench_moderation']['allowed_moderation_states'][2] = 'approver';
    $new_source['type'] = 'page';
    self::assertEquals($new_source, $output);

    $items = ["workbench_moderation", "state_needs_review", "unaltered"];
    $output = ConfigActionsTransform::replace($items, $replace);
    self::assertEquals(["workflow_moderation", "state_approver", "unaltered"], $output);

    // Now test replacing 'workbench' with 'workflow' in keys.
    $replace_keys = [
      'workbench' => 'workflow',
    ];
    $output = ConfigActionsTransform::replace($source, $replace, $replace_keys);
    $value = $new_source['third_party_settings']['workbench_moderation'];
    $new_source['third_party_settings']['workflow_moderation'] = $value;
    unset($new_source['third_party_settings']['workbench_moderation']);
    self::assertEquals($new_source, $output);
  }

  /**
   * @covers ::add
   */
  public function testAdd() {
    $source = Yaml::decode(file_get_contents(dirname(__FILE__) . "/node.type.page.yml"));
    $path = ["third_party_settings", "workbench_moderation", "test"];
    $value = ["myvalue" => 123];

    $output = ConfigActionsTransform::add($source, $path, $value);
    $source['third_party_settings']['workbench_moderation']['test'] = $value;
    self::assertEquals($source, $output);
  }

  /**
   * @covers ::read
   */
  public function testRead() {
    $source = Yaml::decode(file_get_contents(dirname(__FILE__) . "/node.type.page.yml"));
    $path = ["dependencies", "module"];

    $output = ConfigActionsTransform::read($source, $path);
    self::assertEquals(['menu_ui', 'workbench_moderation'], $output);
  }

  /**
   * @covers ::change
   */
  public function testChange() {
    $source = Yaml::decode(file_get_contents(dirname(__FILE__) . "/node.type.page.yml"));
    $path = ["third_party_settings", "workbench_moderation", "enabled"];

    $output = ConfigActionsTransform::change($source, $path, FALSE);
    $source['third_party_settings']['workbench_moderation']['enabled'] = FALSE;
    self::assertEquals($source, $output);

    $output = ConfigActionsTransform::change($source, $path, 123);
    $source['third_party_settings']['workbench_moderation']['enabled'] = 123;
    self::assertEquals($source, $output);

    $output = ConfigActionsTransform::change($source, $path, 'test');
    $source['third_party_settings']['workbench_moderation']['enabled'] = 'test';
    self::assertEquals($source, $output);

    $output = ConfigActionsTransform::change($source, $path, ['my_key' => 'test']);
    $source['third_party_settings']['workbench_moderation']['enabled'] = ['my_key' => 'test'];
    self::assertEquals($source, $output);
  }

  /**
   * @covers ::delete
   */
  public function testDelete() {
    $source = Yaml::decode(file_get_contents(dirname(__FILE__) . "/node.type.page.yml"));
    $output = ConfigActionsTransform::delete($source, ["dependencies", "module"], true);
    unset($source['dependencies']);
    self::assertEquals($source, $output);

    $output = ConfigActionsTransform::delete($source, ['description'], true);
    $new_source = $source;
    unset($new_source['description']);
    self::assertEquals($new_source, $output);

    // Test clearing string value without pruning.
    $output = ConfigActionsTransform::delete($source, ['description']);
    $new_source = $source;
    $new_source['description'] = '';
    self::assertEquals($new_source, $output);
  }

  /**
   * @covers ::parseWildcards
   */
  public function testParseWildcards() {
    $replace = [
      '@name@' => 'old name',
      '@type@' => 'old type',
      '@other@' => 'existing',
    ];

    // Directly testing the code used in ConfigActionsPluginBase to merge
    // existing replacements with parsed key wildcards.
    $replace = array_merge($replace, ConfigActionsTransform::parseWildcards('@name@.@type@', 'hello.world'));
    $this->assertEquals('hello', $replace['@name@']);
    $this->assertEquals('world', $replace['@type@']);
    $this->assertEquals('existing', $replace['@other@']);

    $result = ConfigActionsTransform::parseWildcards('blog.@name@.@type@', 'hello.world');
    $this->assertEquals([], $result);
  }


}
