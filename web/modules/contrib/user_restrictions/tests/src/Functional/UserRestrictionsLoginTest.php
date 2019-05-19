<?php

namespace Drupal\Tests\user_restrictions\Functional;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests login restrictions.
 *
 * @group user_restrictions
 */
class UserRestrictionsLoginTest extends UserRestrictionsTestBase {

  /**
   * Ensure a user cannot log in if their name is on the blacklist.
   */
  public function testUserRestrictionsCheckNameBlacklist() {
    $this->drupalGet('user/register');

    $name = 'lol' . $this->randomMachineName();
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $this->randomMachineName() . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $text = (string) new FormattableMarkup('The name <em class="placeholder">@name</em> is reserved, and cannot be used.', ['@name' => $name]);
    $this->assertRaw((string) $text, 'User "name" restricted.');
  }

  /**
   * Ensure whitelists override blacklists for name patterns.
   */
  public function testUserRestrictionsCheckNameWhitelist() {
    $this->drupalGet('user/register');

    $edit = [];
    $edit['name'] = 'lolcats';
    $edit['mail'] = $this->randomMachineName() . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'), 'User registered successfully.');
  }

  /**
   * Ensure a user cannot log in if their email is on the blacklist.
   */
  public function testUserRestrictionsCheckMailBlacklist() {
    $this->drupalGet('user/register');

    $mail = $this->randomMachineName() . '@' . $this->randomMachineName() . '.ru';
    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $mail;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $text = (string) new FormattableMarkup('The email <em class="placeholder">@mail</em> is reserved, and cannot be used.', ['@mail' => $mail]);
    $this->assertRaw((string) $text, 'User "email" restricted.');
  }

  /**
   * Ensure whitelists override blacklists for email patterns.
   */
  public function testUserRestrictionsCheckMailWhitelist() {
    $this->drupalGet('user/register');

    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = 'typhonius@mail.ru';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'), 'User registered successfully.');
  }

  /**
   * Tests wildcards for name patterns that should not match.
   */
  public function testUserRestrictionsCheckWildcardNotMatch() {
    $this->drupalGet('user/register');

    $mail = $this->randomMachineName() . '@' . $this->randomMachineName() . '.com';
    $edit = [];
    $edit['name'] = 'ilikecoffee';
    $edit['mail'] = $mail;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'), 'User registered successfully.');
  }

  /**
   * Tests wildcards for name patterns that should match.
   */
  public function testUserRestrictionsCheckWildcardMatch() {
    $this->drupalGet('user/register');

    $name = 'coffeelover';
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $this->randomMachineName() . '@' . $this->randomMachineName() . '.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $text = (string) new FormattableMarkup('The name <em class="placeholder">@name</em> is reserved, and cannot be used.', ['@name' => $name]);
    $this->assertRaw((string) $text, 'User "name" restricted.');
  }

}
