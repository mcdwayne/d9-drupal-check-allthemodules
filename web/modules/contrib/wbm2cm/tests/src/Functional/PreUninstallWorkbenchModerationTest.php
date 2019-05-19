<?php

namespace Drupal\Tests\wbm2cm\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that Workbench Moderation settings are removed from config entities
 * _before_ Workbench Moderation is uninstalled.
 *
 * @group wbm2cm
 */
class PreUninstallWorkbenchModerationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'wbm2cm',
    'workbench_moderation',
  ];

  public function testPreUninstall() {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = $this->drupalCreateContentType()
      ->setThirdPartySetting('workbench_moderation', 'enabled', TRUE)
      ->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', ['foo', 'bar', 'baz'])
      ->setThirdPartySetting('workbench_moderation', 'default_moderation_state', 'bar');

    $node_type->save();

    // Actually uninstalling Workbench Moderation would remove the third-party
    // settings by force. So just invoke the hook instead, to prove that our
    // logic actually works.
    $this->container->get('module_handler')
      ->invoke('wbm2cm', 'module_preuninstall', ['workbench_moderation']);

    $node_type = $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->load($node_type->id());

    $this->assertNotContains('workbench_moderation', $node_type->getThirdPartyProviders());
  }

}
