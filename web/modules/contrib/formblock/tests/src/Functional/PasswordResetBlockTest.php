<?php

namespace Drupal\Tests\formblock\Functional;

/**
 * Tests the password reset form block.
 *
 * Verifies that the password reset form block display correctly.
 *
 * @group formblock
 */
class PasswordResetBlockTest extends FormblockTestBase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test that the password reset form appears correctly.
   */
  public function testPasswordResetForm() {
    $this->drupalGet('/admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->linkByHrefExists('/admin/structure/block/add/formblock_user_password/classy', 0,
      'Did not find the search block in block candidate list.');

    $block = $this->drupalPlaceBlock('formblock_user_password');

    $this->drupalGet('');
    $this->assertSession()->responseContains($block->label());

    // Check that button is present
    $pattern = '//*[(@id = "user-pass")]//*[(@id = "edit-submit")]';
    $elements = $this->xpath($pattern);
    $this->assertTrue(!empty($elements), t('The reset password button is present.'));

    // Check the field and button are present when logged out
    $this->drupalLogout();
    $this->drupalGet('');

    $pattern = '//*[(@id = "edit-name")]';
    $elements = $this->xpath($pattern);
    $this->assertTrue(!empty($elements), t('The username/email field is present.'));

    $pattern = '//*[(@id = "edit-submit--2")]';
    $elements = $this->xpath($pattern);
    $this->assertTrue(!empty($elements), t('The reset password button is present.'));
  }


}
