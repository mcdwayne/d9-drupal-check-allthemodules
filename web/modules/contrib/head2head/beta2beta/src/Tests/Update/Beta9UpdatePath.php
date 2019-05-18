<?php

/**
 * @file
 * Contains \Drupal\beta2beta\Tests\Update\Beta9UpdatePath.
 */

namespace Drupal\beta2beta\Tests\Update;

use Drupal\beta2beta\Tests\Update\TestTraits\FrontPage;
use Drupal\beta2beta\Tests\Update\TestTraits\NewNode;
use Drupal\views\Entity\View;

/**
 * Tests the beta 9 update path.
 *
 * @group beta2beta
 */
class Beta9UpdatePath extends Beta2BetaUpdateTestBase {

  use FrontPage;
  use NewNode;

  /**
   * Turn off strict config schema checking.
   *
   * This has to be turned off since there are multiple update hooks that update
   * views. Since only the final view save will be compliant with the current
   * schema, an exception would be thrown on the first view to be saved if this
   * were left on.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $startingBeta = 9;

  /**
   * Ensure that update #81004 (issue #2393339) has run properly.
   */
  public function testUpdate81004() {
    $this->runUpdates();

    // Test the `comments_recent` view which has an undefined index exception
    // during the update hook.
    $view = View::load('comments_recent');
    $executable = $view->getExecutable();
    $executable->setDisplay();
    $executable->build();
    $relationships = $executable->getHandlers('relationship');
    $expected = [
      'node' => [
        'field' => 'node',
        'id' => 'node',
        'table' => 'comment_field_data',
        'required' => TRUE,
        'plugin_id' => 'field',
      ],
    ];
    $this->assertIdentical($expected, $relationships, 'The comments_recent view has the proper relationship.');
  }

}
