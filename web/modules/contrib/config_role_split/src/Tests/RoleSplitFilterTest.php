<?php

namespace Drupal\config_role_split\Tests;

use Drupal\config_role_split\Plugin\ConfigFilter\RoleSplitFilter;
use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Class SplitFilterTest.
 *
 * @group config_role_split
 */
class RoleSplitFilterTest extends UnitTestCase {

  /**
   * Test that the filter writes correctly.
   *
   * @dataProvider filterWriteProvider
   */
  public function testFilterWrite($name, $roles, $data, $existing, $all_expected) {
    foreach ($all_expected as $mode => $expected) {
      $filter = new RoleSplitFilter(['mode' => $mode, 'roles' => $roles], '', []);
      // Set the source storage to let the filter read the existing data.
      $storage = $this->prophesize(StorageInterface::class);
      $storage->read($name)->willReturn($existing);
      $filter->setSourceStorage($storage->reveal());
      $this->assertEquals($expected, $filter->filterWrite($name, $data), 'Write in ' . $mode . ' mode');
    }
  }

  // @codingStandardsIgnoreStart
  public function filterWriteProvider() {
    return [
      [
        'user.role.test',
        ['test' => ['can test', 'something']],
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        NULL,
        [
          'split' => ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
          'fork' => ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
          'exclude' => ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        ],
      ],
      [
        'user.role.test',
        ['test' => ['can test', 'something']],
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'something', 'without a doubt', 'without problems']],
        [
          'split' => ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
          'fork' => ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
          'exclude' => ['id' => 'test', 'permissions' => ['a tester', 'can test', 'something', 'without a doubt']],
        ],
      ],
      [
        'user.role.test',
        ['test' => ['can test', 'something'], 'other' => ['other']],
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        ['id' => 'test', 'permissions' => ['a tester', 'something', 'without a doubt', 'without problems']],
        [
          'split' => ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
          'fork' => ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
          'exclude' => ['id' => 'test', 'permissions' => ['a tester', 'can test', 'something', 'without a doubt']],
        ],
      ],
      [
        'user.role.other',
        ['test' => ['can test', 'something']],
        ['id' => 'other', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        ['id' => 'other', 'permissions' => ['other']],
        [
          'split' => ['id' => 'other', 'permissions' => ['a tester', 'can test', 'without a doubt']],
          'fork' => ['id' => 'other', 'permissions' => ['a tester', 'can test', 'without a doubt']],
          'exclude' => ['id' => 'other', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        ],
      ],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Test that the filter reads correctly.
   *
   * @dataProvider filterReadProvider
   */
  public function testFilterRead($mode, $roles, $name, $data, $expected) {
    $filter = new RoleSplitFilter(['mode' => $mode, 'roles' => $roles], '', []);
    $this->assertEquals($expected, $filter->filterRead($name, $data));

    // Test that the mode and roles are read from the storage first.
    $storage = $this->prophesize(StorageInterface::class);
    $storage->read('role_split.test')->willReturn(['mode' => $mode, 'roles' => $roles]);
    $filter = new RoleSplitFilter(['config_name' => 'role_split.test'], '', []);
    $filter->setFilteredStorage($storage->reveal());
    $this->assertEquals($expected, $filter->filterRead($name, $data));
  }

  /**
   * Test that the filter reads multiple objects correctly.
   */
  public function testFilterReadMultiple() {
    foreach (['split', 'fork', 'exclude'] as $mode) {
      $names = [];
      $all_data = [];
      $all_expected = [];

      // Test by filtering the test data from the read provider.
      foreach ($this->filterReadProvider() as $row) {
        list($row_mode, $roles, $name, $data, $expected) = $row;
        if ($row_mode == $mode) {
          $names[] = $name;
          $all_data[$name] = $data;
          $all_expected[$name] = $expected;
        }
      }
      // The roles are from the last provided example.
      $filter = new RoleSplitFilter(['mode' => $mode, 'roles' => $roles], '', []);
      $this->assertEquals($all_expected, $filter->filterReadMultiple($names, $all_data));
    }
  }

  // @codingStandardsIgnoreStart
  public function filterReadProvider() {
    return [
      [
        'split',
        ['test' => ['can test']],
        'user.role.test',
        ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
      ],
      [
        'fork',
        ['test' => ['can test']],
        'user.role.test',
        ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
      ],
      [
        'exclude',
        ['test' => ['can test']],
        'user.role.test',
        ['id' => 'test', 'permissions' => ['a tester', 'can test', 'without a doubt']],
        ['id' => 'test', 'permissions' => ['a tester', 'without a doubt']],
      ],
      [
        'split',
        ['other' => ['can test too'], 'yet' => ['no']],
        'user.role.other',
        ['id' => 'other', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'other', 'permissions' => ['a tester', 'can test too', 'without a doubt']],
      ],
      [
        'fork',
        ['other' => ['can test too'], 'yet' => ['no']],
        'user.role.other',
        ['id' => 'other', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'other', 'permissions' => ['a tester', 'can test too', 'without a doubt']],
      ],
      [
        'exclude',
        ['other' => ['can test too'], 'yet' => ['no']],
        'user.role.other',
        ['id' => 'other', 'permissions' => ['a tester', 'can test too', 'without a doubt']],
        ['id' => 'other', 'permissions' => ['a tester', 'without a doubt']],
      ],
      [
        'split',
        ['test' => ['can test'], 'other' => ['can test too'], 'yet' => ['no']],
        'user.role.unrelated',
        ['id' => 'unrelated', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'unrelated', 'permissions' => ['a tester', 'without a doubt']],
      ],
      [
        'fork',
        ['test' => ['can test'], 'other' => ['can test too'], 'yet' => ['no']],
        'user.role.unrelated',
        ['id' => 'unrelated', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'unrelated', 'permissions' => ['a tester', 'without a doubt']],
      ],
      [
        'exclude',
        ['test' => ['can test'], 'other' => ['can test too'], 'yet' => ['no']],
        'user.role.unrelated',
        ['id' => 'unrelated', 'permissions' => ['a tester', 'without a doubt']],
        ['id' => 'unrelated', 'permissions' => ['a tester', 'without a doubt']],
      ],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Test that the filter deletes correctly.
   *
   * @dataProvider filterDeleteProvider
   */
  public function testFilterDelete($roles, $name, $mode, $true, $false) {
    $filter = new RoleSplitFilter(['mode' => $mode, 'roles' => $roles], '', []);
    $this->assertEquals($true, $filter->filterDelete($name, TRUE));
    $this->assertEquals($false, $filter->filterDelete($name, FALSE));
  }

  // @codingStandardsIgnoreStart
  public function filterDeleteProvider() {
    return [
      [['test' => ['can test']], 'user.role.test', 'split', TRUE, FALSE],
      [['test' => ['can test']], 'user.role.test', 'fork', FALSE, FALSE],
      [['test' => ['can test']], 'user.role.test', 'exclude', FALSE, FALSE],
      [['test' => ['can test']], 'user.role.other', 'split', TRUE, FALSE],
      [['test' => ['can test']], 'user.role.other', 'fork', TRUE, FALSE],
      [['test' => ['can test']], 'user.role.other', 'exclude', TRUE, FALSE],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Test that the filter deletes all correctly.
   *
   * @dataProvider filterDeleteAllProvider
   */
  public function testFilterDeleteAll($mode, $true, $false) {
    $filter = new RoleSplitFilter(['mode' => $mode, 'roles' => (array) $this->getRandomGenerator()->object()], '', []);
    $this->assertEquals($true, $filter->filterDeleteAll($this->randomMachineName(), TRUE));
    $this->assertEquals($false, $filter->filterDeleteAll($this->randomMachineName(), FALSE));
  }

  // @codingStandardsIgnoreStart
  public function filterDeleteAllProvider() {
    return [
      ['split', TRUE, FALSE],
      ['fork', FALSE, FALSE],
      ['exclude', FALSE, FALSE],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Test that methods that should continue to work do so.
   *
   * @dataProvider noOpProvider
   */
  public function testNoOp($method, $arguments, $returnValue) {
    $modes = ['split', 'fork', 'exclude'];
    foreach ($modes as $mode) {
      // Create a random split.
      $filter = new RoleSplitFilter(['mode' => $mode, 'roles' => (array) $this->getRandomGenerator()->object()], '', []);
      $actual = call_user_func_array([$filter, $method], $arguments);
      $this->assertEquals($actual, $returnValue);
    }
  }

  /**
   * Provide the methods that should continue to work.
   */
  public function noOpProvider() {
    $data = (array) $this->getRandomGenerator()->object();
    $name = $this->randomMachineName();
    // @codingStandardsIgnoreStart
    return [
      ['filterExists', [$this->randomMachineName(), TRUE], TRUE],
      ['filterExists', [$this->randomMachineName(), FALSE], FALSE],
      ['filterRename', [$this->randomMachineName(), $this->randomMachineName(), TRUE], TRUE],
      ['filterRename', [$this->randomMachineName(), $this->randomMachineName(), FALSE], FALSE],
      ['filterListAll', [$this->randomMachineName(), $data], $data],
      ['filterGetAllCollectionNames', [$data], $data],
      ['filterGetCollectionName', [$name], $name],
    ];
    // @codingStandardsIgnoreEnd
  }

}
