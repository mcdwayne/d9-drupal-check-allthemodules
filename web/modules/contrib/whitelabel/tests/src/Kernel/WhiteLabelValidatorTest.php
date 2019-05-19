<?php

namespace Drupal\Tests\whitelabel\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\whitelabel\Entity\WhiteLabel;

/**
 * Tests that the white label not user name constraint works.
 *
 * @group whitelabel
 */
class WhiteLabelValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'text',
    'options',
    'user',
    'file',
    'image',
    'whitelabel',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installSchema('file', ['file_usage']);
    $this->installConfig(['system', 'whitelabel']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installEntitySchema('whitelabel');
  }

  /**
   * @covers \Drupal\whitelabel\Plugin\Validation\Constraint\WhiteLabelNotUsernameConstraint
   * @covers \Drupal\whitelabel\Plugin\Validation\Constraint\WhiteLabelNotUsernameConstraintValidator
   * @dataProvider getWhiteLabelData
   */
  public function testWhiteLabelNotUsernameConstraint($username, $token) {
    $user = User::create([
      'name' => $username,
      'status' => 1,
    ]);
    $user->save();

    // Create white label.
    $whitelabel = WhiteLabel::create(['token' => $token, 'uid' => $user->id()]);

    $result = $whitelabel->validate();

    if ($username === $token) {
      // Assert Fail.
      $this->assertCount(1, $result);
      $this->assertEquals('Due to security concerns this value cannot be the same as your user name.', (string) $result->get(0)->getMessage());
    }
    else {
      // Assert no error.
      $this->assertCount(0, $result);
    }
  }

  /**
   * Provides a list of file types to test.
   */
  public function getWhiteLabelData() {
    return [
      [
        'username' => 'same',
        'token' => 'same',
      ],
      [
        'username' => 'other',
        'token' => 'different',
      ],
    ];
  }

  /**
   * Tests the unique token validator.
   */
  public function testWhitelabelTokenUnique() {
    // Create and store a white label.
    $whitelabel1 = WhiteLabel::create(['token' => 'one']);
    $whitelabel1->save();

    $whitelabel2 = WhiteLabel::create(['token' => 'one']);
    $result = $whitelabel2->validate();

    $this->assertEquals(1, count($result), 'Violation found when token is already in use.');
    $this->assertEquals('The token <em class="placeholder">one</em> is already in use.', $result->get(0)->getMessage());
  }

}
