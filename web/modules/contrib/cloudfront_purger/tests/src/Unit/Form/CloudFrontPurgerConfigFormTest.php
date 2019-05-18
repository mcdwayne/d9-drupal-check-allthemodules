<?php

namespace Drupal\Tests\cloudfront_purger\Unit\Form;

use Drupal\cloudfront_purger\Form\CloudFrontPurgerConfigForm;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for CloudFrontPurgerConfigForm.
 *
 * @coversDefaultClass \Drupal\cloudfront_purger\Form\CloudFrontPurgerConfigForm
 * @group cloudfront_purger
 */
class CloudFrontPurgerConfigFormTest extends UnitTestCase {

  /**
   * Tests form submission sets correct config values.
   */
  public function testFormSubmit() {

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'cloudfront_purger.settings' => [
        'distribution_id' => 'wizzbanger',
        'aws_key' => 'ABCD1234',
        'aws_secret' => 'WXYZ5678',
      ],
    ]);
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Config\Config $config */
    $config = $configFactory->getEditable('cloudfront_purger.settings');

    $config->expects($this->any())
      ->method('set')
      ->withConsecutive(
        [$this->equalTo('distribution_id'), $this->equalTo('foobarbaz')],
        [$this->equalTo('aws_key'), $this->equalTo('')],
        [$this->equalTo('aws_secret'), $this->equalTo('')]
      );

    $config->expects($this->once())
      ->method('save');

    $configForm = new CloudFrontPurgerConfigForm($configFactory);

    $form = [];
    $form_state = (new FormState())
      ->setValues([
        'distribution_id' => 'foobarbaz',
        'aws_key' => '',
        'aws_secret' => '',
      ]);

    $configForm->submitFormSuccess($form, $form_state);

  }

}
