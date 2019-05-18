<?php

/**
 * @file
 * Contains \Drupal\field_paywall\Tests\Web\FieldPaywallHiddenFieldsTest.
 */

namespace Drupal\field_paywall\Tests\Web;

/**
 * Tests the paywall field display functionality.
 *
 * @group Paywall
 */
class FieldPaywallHiddenFieldsTest extends FieldPaywallWebTestBase {

  /**
   * Tests whether hidden fields are truly
   */
  function testHiddenFieldsAreHidden() {
    $entity = $this->createEntityWithValues();
    $viewed_entity = entity_view($entity, 'default');

  }

}
