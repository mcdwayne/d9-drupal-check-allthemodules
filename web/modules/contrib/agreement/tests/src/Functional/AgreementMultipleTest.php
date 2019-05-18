<?php

namespace Drupal\Tests\agreement\Functional;

use Drupal\agreement\Entity\Agreement;

/**
 * Tests multiple agreements.
 *
 * @group agreement
 */
class AgreementMultipleTest extends AgreementTestBase {

  /**
   * A second agreement.
   *
   * @var \Drupal\agreement\Entity\Agreement
   */
  protected $newAgreement;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->newAgreement = Agreement::create([
      'id' => 'new_agreement',
      'label' => 'New Agreement',
      'path' => '/new-agreement',
      'agreement' => '',
      'settings' => [
        'title' => $this->randomGenerator->sentences(1),
        'format' => 'plain_text',
        'frequency' => -1,
        'submit' => 'Submit',
        'checkbox' => 'I agree',
        'success' => 'Success',
        'revoked' => 'Revoked',
        'failure' => 'Failure',
        'roles' => ['authenticated'],
        'recipient' => '',
        'reset_date' => 0,
        'destination' => '',
        'visibility' => [
          'settings' => 1,
          'pages' => ['/node/' . $this->node->id()],
        ],
      ],
    ]);
    $this->newAgreement->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Asserts that an user can use multiple agreements.
   */
  public function testAgreement() {
    $account = $this->createUnprivilegedUser();
    $this->drupalLogin($account);

    // Go to front page, no agreement.
    $this->drupalGet('/node');
    $this->assertNotAgreementPage($this->agreement);
    $this->assertNotAgreementpage($this->newAgreement);

    // Go anywhere else, open agreement.
    $this->drupalGet('/user');
    $this->assertAgreementPage($this->agreement);
    $this->assertNotAgreementPage($this->newAgreement);

    // Agreement with visibility settings for all pages displays instead of
    // agreement with explicity visibility page settings.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertAgreementPage($this->agreement);
    $this->assertNotAgreementPage($this->newAgreement);

    // Accept the agreement.
    $this->assertAgreed($this->agreement);

    // Go to the node again, which is second agreement page.
    $this->drupalGet('/node/' . $this->node->id());
    $this->assertAgreementPage($this->newAgreement);
    $this->assertNotAgreementPage($this->agreement);

    // Accept the second agreement.
    $this->assertAgreed($this->newAgreement);
    $this->assertNotAgreementPage($this->agreement);
  }

}
