<?php

namespace Drupal\Tests\mass_contact\Kernel;

use Drupal\mass_contact\MassContactInterface;
use Drupal\simpletest\UserCreationTrait;

/**
 * Kernel tests for the opt-out service.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\OptOut
 */
class OptOutTest extends MassContactTestBase {

  use CategoryCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'options'];

  /**
   * Mass contact categories.
   *
   * @var \Drupal\mass_contact\Entity\MassContactCategoryInterface[]
   */
  protected $categories;

  /**
   * Users that have opted out of nothing.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $recipients;

  /**
   * Global opt-out users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $globalOptOut;

  /**
   * Opted out of category 1.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $categoryOptOut1;

  /**
   * Opted out of category 2.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $categoryOptOut2;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installConfig('mass_contact');

    // Add 3 categories.
    foreach (range(1, 3) as $i) {
      $this->categories[$i] = $this->createCategory();
    }

    // Add 20 users.
    foreach (range(1, 20) as $i) {
      $account = $this->createUser();

      // Users 2, 3, and 5 opt out globally.
      if (in_array($i, [2, 3, 5])) {
        $account->{MassContactInterface::OPT_OUT_FIELD_ID} = 1;
        $account->save();
        $this->globalOptOut[$account->id()] = $account;
      }
      // Users 4 and 19 opt out of category 1.
      elseif (in_array($i, [4, 19])) {
        $account->{MassContactInterface::OPT_OUT_FIELD_ID} = $this->categories[1]->id();
        $account->save();
        $this->categoryOptOut1[$account->id()] = $account;
      }
      // Users 8 and 16 opt out of category 2.
      elseif (in_array($i, [8, 16])) {
        $account->{MassContactInterface::OPT_OUT_FIELD_ID} = $this->categories[2]->id();
        $account->save();
        $this->categoryOptOut2[$account->id()] = $account;
      }
      // Remainder are recipients.
      else {
        $this->recipients[$account->id()] = $account;
      }
    }
  }

  /**
   * Tests opt-out disabled.
   *
   * @covers ::getOptOutAccounts
   */
  public function testOptOutDisabled() {
    // Disable opt out.
    $this->config('mass_contact.settings')
      ->set('optout_enabled', MassContactInterface::OPT_OUT_DISABLED)
      ->save();
    /** @var \Drupal\mass_contact\OptOutInterface $opt_out */
    $opt_out = \Drupal::service('mass_contact.opt_out');
    $this->assertEmpty($opt_out->getOptOutAccounts($this->categories));
  }

  /**
   * Tests opt-out set to global.
   *
   * @covers ::getOptOutAccounts
   */
  public function testOptOutGlobal() {
    // Global opt out.
    $this->config('mass_contact.settings')->set('optout_enabled', MassContactInterface::OPT_OUT_GLOBAL)->save();
    /** @var \Drupal\mass_contact\OptOutInterface $opt_out */
    $opt_out = \Drupal::service('mass_contact.opt_out');
    $expected = array_merge(
      array_keys($this->globalOptOut),
      array_keys($this->categoryOptOut1),
      array_keys($this->categoryOptOut2)
    );
    $expected = array_combine($expected, $expected);
    $this->assertEquals($expected, $opt_out->getOptOutAccounts($this->categories));
  }

  /**
   * Tests per-category opt-out.
   */
  public function testOptOutCategory() {
    $this->config('mass_contact.settings')->set('optout_enabled', MassContactInterface::OPT_OUT_CATEGORY)->save();
    /** @var \Drupal\mass_contact\OptOutInterface $opt_out */
    $opt_out = \Drupal::service('mass_contact.opt_out');

    // Check category 1 and 3.
    $expected = array_merge(
      array_keys($this->globalOptOut),
      array_keys($this->categoryOptOut1)
    );
    $expected = array_combine($expected, $expected);
    $this->assertEquals($expected, $opt_out->getOptOutAccounts([$this->categories[1], $this->categories[3]]));
  }

}
