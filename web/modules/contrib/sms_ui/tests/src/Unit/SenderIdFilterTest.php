<?php

namespace Drupal\Tests\sms_ui\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sms_ui\Utility\SenderIdFilter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sms_ui\Utility\SenderIdFilter
 * @group SMS UI
 */
class SenderIdFilterTest extends UnitTestCase  {

  protected $settings = [];

  /**
   * Simulates the user id of the currently tested user.
   */
  protected $accountInfo;

  /**
   * @covers ::isAllowed
   * @dataProvider providerTestIsAllowedSingle
   */
  public function testIsAllowedSingle($test, $matches, $non_matches) {
    $filter = $this->getMockSenderIdFilter($test, NULL, FALSE);
    $user = $this->getMockTestUser('test_user', 5);

    $this->assertNotAllowed($matches, $filter, $user);
    $this->assertAllowed($non_matches, $filter, $user);
  }

  /**
   * @dataProvider providerTestIsAllowedIncluded
   */
  public function testIsAllowedExcludedIncluded($test, $included, $matches, $non_matches) {
    // Confirm the expected behavior for non-included user.
    $filter = $this->getMockSenderIdFilter($test, $included, FALSE);
    $user = $this->getMockTestUser('test_user', 5);
    $this->assertNotAllowed($matches, $filter, $user);
    $this->assertAllowed($non_matches, $filter, $user);

    // Verify that the inclusions now allow the 'allowed_user' to use those IDs.
    $user = $this->getMockTestUser('allowed_user', 5);
    $this->assertAllowed($matches, $filter, $user);
    $this->assertAllowed($non_matches, $filter, $user);
  }

  /**
   * @dataProvider providerTestIsAllowedSingle
   */
  public function testIsAllowedGlobalInclusion($test, $matches, $non_matches) {
    $filter = $this->getMockSenderIdFilter($test, '*:' . implode(',', $matches), FALSE);
    $user = $this->getMockTestUser('test_user', 5);
    $this->assertAllowed($matches, $filter, $user);
    $this->assertAllowed($non_matches, $filter, $user);
  }

  /**
   * @dataProvider providerTestIsAllowedSingle
   */
  public function testIsAllowedSuperUserIncluded($test, $matches, $non_matches) {
    $filter = $this->getMockSenderIdFilter($test, NULL, FALSE);
    $user = $this->getMockTestUser('superuser', 1);
    $this->assertAllowed($matches, $filter, $user);
    $this->assertAllowed($non_matches, $filter, $user);

    // Include superuser in sender ID filtering.
    $filter = $this->getMockSenderIdFilter($test, NULL, TRUE);
    $this->assertNotAllowed($matches, $filter, $user);
    $this->assertAllowed($non_matches, $filter, $user);
  }

  public function testCombined() {
    $filter = $this->getMockSenderIdFilter($this->getBlockedSenders(), $this->getAllowedSenderIds());
    $user = $this->getMockTestUser('test_user', 5);
    $this->assertNotAllowed([
      'no wildcard', 'short', 'very-very-very-long',
      'cutleft', 'rightwards', 'obothe', 'char', 'full',
      'chow', 'diamond', 'globalism', 'congrats', 'successful',
      'tom_dick_harry',
    ], $filter, $user);
    $this->assertAllowed(['glory', 'gloria', 'glorious', 'glorify'], $filter, $user);

    $allowed = [
      'short' => ['short', 'long'],
      'long' => ['long', 'very-very-very-long'],
      'left' => ['left', 'from-left'],
      'right' => ['right', 'rightwards'],
      'congrats' => ['congrats', 'success', 'successful'],
      'tom' => ['tom_dick_harry'],
      'dick' => ['tom_dick_harry'],
      'harry' => ['tom_dick_harry'],
    ];
    foreach ($allowed as $user_name => $sender_ids) {
      $user = $this->getMockTestUser($user_name);
      $this->assertAllowed($sender_ids, $filter, $user);
    }
  }

  /**
   * Tests invalid values for the included and excluded checks.
   *
   * @covers ::isAllowed
   * @dataProvider providerTestInvalidValues
   */
  public function testInvalidValues($excluded, $included, $user_name, $allowed, $not_allowed) {
    $user = $this->getMockTestUser($user_name);
    $filter = $this->getMockSenderIdFilter($excluded, $included, FALSE);
    $this->assertAllowed($allowed, $filter, $user);
    $this->assertNotAllowed($not_allowed, $filter, $user);
  }

  /**
   * Creates a mock user account.
   *
   * @param string $user_name
   *   The user name to mock.
   * @param int $uid
   *   The user ID to mock.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function getMockTestUser($user_name, $uid = 0) {
    $mockTestUser = $this->prophesize(AccountInterface::class);
    $mockTestUser->id()->willReturn($uid);
    $mockTestUser->getAccountName()->willReturn($user_name);
    return $mockTestUser->reveal();
  }

  /**
   * Creates a mock sender ID filter.
   *
   * @param string $excluded
   *   Comma-separated list of sender IDs to filter.
   * @param string|null $included
   *   Formatted list of user names and allowed sender IDs for those users.
   * @param bool $include_superuser
   *   Flag to include the super user in the filter checks.
   *
   * @return \Drupal\sms_ui\Utility\SenderIdFilter
   */
  public function getMockSenderIdFilter($excluded, $included = NULL, $include_superuser = FALSE) {
    $configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $config = $this->prophesize(Config::class);
    $config->get('sender_id_filter')->willReturn([
      'include_superuser' => $include_superuser,
      'excluded' => $excluded,
      'included' => $included,
    ]);
    $configFactory->get('sms_ui.settings')->willReturn($config->reveal());
    return new SenderIdFilter($configFactory->reveal());
  }

  /**
   * Asserts that the specified sender IDs are NOT allowed for the user.
   *
   * @param array $sender_ids
   * @param \Drupal\sms_ui\Utility\SenderIdFilter $filter
   * @param \Drupal\Core\Session\AccountInterface $user
   */
  protected function assertNotAllowed(array $sender_ids, SenderIdFilter $filter, AccountInterface $user) {
    $word = '';
    foreach ($sender_ids as $sender_id) {
      $this->assertFalse($filter->isAllowed($sender_id, $user, $word),
        sprintf('sender id "%s" not allowed for user "%s"', $sender_id, $user->getAccountName()));
    }
  }

  /**
   * Asserts that the specified sender IDs are allowed for the user.
   *
   * @param array $sender_ids
   * @param \Drupal\sms_ui\Utility\SenderIdFilter $filter
   * @param \Drupal\Core\Session\AccountInterface $user
   */
  protected function assertAllowed(array $sender_ids, SenderIdFilter $filter, AccountInterface $user) {
    $word = '';
    foreach ($sender_ids as $sender_id) {
      $this->assertTrue($filter->isAllowed($sender_id, $user, $word),
        sprintf('sender id "%s" allowed for user "%s"', $sender_id, $user->getAccountName()));
    }
  }

  /**
   * @return array
   */
  public function providerTestIsAllowedSingle() {
    return [
      ['kiar', ['kiar', 'kiar ho'], ['kiarmlo']],
      ['coca', ['coca', 'coca cola'], ['cocacola']],
      ['coca%', ['cocakol', 'coca', 'coca cola'], ['coka', 'coco']],
      ['oceanic%', ['oceanic place', 'oceanicplace'], ['oceanc']],
      ['%bluh%', ['hubluh', 'bluhoh', 'obluhuar'], ['blah', 'hooblah']],
      ['with space', ['with space', 'some with space'], ['withspace', 'with', 'space']],
      ['with space%', ['with space', 'some with spaces'], ['withspaces', 'with', 'spaces']],
      ['with-hyphen', ['with-hyphen', 'some with-hyphen'], ['with hyphen', 'with', 'hyphen']],
      ['under_score', ['under_score', 'so under_score'], ['_underscore', 'underscore_']],
      // Wierd character substitutions. @todo: the algorithm is wrong.
      ['hello pal', ['he110 p@l', 'hello pal'], ['hello friend']],
    ];
  }

  /**
   * @return array
   */
  public function providerTestIsAllowedIncluded() {
    return [
      ['kiar', 'allowed_user:kiar ho, kiar;', ['kiar', 'kiar ho'], ['kiarmlo']],
      ['coca', 'allowed_user:coca,coca cola;', ['coca', 'coca cola'], ['cocacola']],
      ['coca%', 'allowed_user:cocakol, coca, coca cola', ['cocakol', 'coca', 'coca cola'], ['coka', 'coco']],
      ['oceanic%', 'allowed_user:oceanic place, oceanicplace', ['oceanic place', 'oceanicplace'], ['oceanc']],
      ['%bluh%', 'allowed_user:hubluh,bluhoh,obluhuar', ['hubluh', 'bluhoh', 'obluhuar'], ['blah', 'hooblah']],
    ];
  }

  public function providerTestInvalidValues() {
    return [
      ['testing, trying', 'kabloski; test_user: testing', 'test_user', ['testing'], ['trying']],
      ['testing, trying', '', 'test_user', [], ['testing', 'trying']],
    ];
  }

  protected function getBlockedSenders() {
    return <<<EOF
no wildcard, short, very-very-very-long,
%left, right%, %both%,
char, full, chow,
diamond%, glo%,
congrats%, succes%, suces%, %reward%,
tom_dick_harry,
EOF;
  }

  protected function getAllowedSenderIds() {
    return <<<EOF
short: short;
long: long, very-very-very-long;
left: left, from-left;
right:right,rightwards;
congrats:congrats,success, successful;
tom,dick,harry: tom_dick_harry;
*:glory,gloria, glorious,glorify;
EOF;
  }

}
