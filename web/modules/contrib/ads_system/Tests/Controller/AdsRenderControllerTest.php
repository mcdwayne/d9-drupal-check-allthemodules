<?php

namespace Drupal\ads_system\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the ads_system module.
 */
class AdsRenderControllerTest extends WebTestBase {

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Drupal\Core\Form\FormAjaxResponseBuilder definition.
   *
   * @var Drupal\Core\Form\FormAjaxResponseBuilder
   */
  protected $formAjaxResponseBuilder;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "ads_system AdsRenderController's controller functionality",
      'description' => 'Test Unit for module ads_system and controller AdsRenderController.',
      'group' => 'ads_system',
    ];
  }

  /**
   * Tests ads_system functionality.
   */
  public function testAdsRenderController() {
    // Check that the basic functions of module ads_system.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
