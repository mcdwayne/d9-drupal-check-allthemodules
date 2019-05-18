<?php

namespace Drupal\eform\Tests;

use Drupal\Core\Url;

/**
 * The submission listing pages should be restricted.
 *
 * @group eform
 */
class EFormSubmissionPagesTest extends EFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Checks access on the listing pages.
   */
  function testSubmissionListingPage() {
    // We require an eform_type to continue the rest of the tests.
    $eformType = \Drupal::entityTypeManager()->getStorage('eform_type')->create(array(
      'type' => strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
      'roles' => array(
        'anonymous'
      ),
      'disallow_text' => array('value' => '', 'format' => 'restricted_html'),
      'submission_text' => array('value' => '', 'format' => 'restricted_html'),
    ));
    $eformType->save();


    // Build the submissions URL.
    $url = Url::fromRoute('entity.eform_type.submissions', array(
      'eform_type' => $eformType->id(),
    ));

    // Anonymous user should not be able to access the page.
    $this->drupalGet($url);
    $this->assertResponse(403);

    // Authenticated user should not be able to access the page.
    $user_1 = $this->createUser(array());
    $this->drupalLogin($user_1);
    $this->drupalGet($url);
    $this->assertResponse(403);
    $this->drupalLogout();

    $user_2 = $this->createUser(array('administer eform submissions'));
    $this->drupalLogin($user_2);
    $this->drupalGet($url);
    $this->assertResponse(200);
  }
}
