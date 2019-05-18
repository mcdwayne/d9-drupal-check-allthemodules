<?php

namespace Drupal\Tests\responsivewrappers\Functional;

use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides a class for responsivewrappers functional tests.
 *
 * @group responsivewrappers
 */
class ResponsiveWrappersTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'responsivewrappers',
    'text',
    'user',
  ];

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The node content.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer filters',
    ]);

    FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
      'weight' => 1,
      'filters' => [],
    ])->save();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic Page',
    ]);

    $this->node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Responsive filter test',
      'body' => [
        'value' => '<img scr="#" /><table></table><iframe src="https://www.youtube.com/embed/"></iframe>',
        'format' => 'full_html',
      ],
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Tests responsive wrappers filter content output.
   */
  public function testsFilterContentOutput() {
    // Tests the node output without responsive wrappers filter enabled.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('<img scr="#" />');
    $this->assertSession()->responseContains('<table></table>');
    $this->assertSession()->responseContains('<iframe src="https://www.youtube.com/embed/"></iframe>');

    // Enable the responsive wrappers filter.
    $this->drupalGet('admin/config/content/formats/manage/full_html');
    $edit = [
      'filters[filter_bootstrap_responsive_wrapper][status]' => TRUE,
      'filters[filter_bootstrap_responsive_wrapper][settings][responsive_iframe]' => TRUE,
      'filters[filter_bootstrap_responsive_wrapper][settings][responsive_table]' => TRUE,
      'filters[filter_bootstrap_responsive_wrapper][settings][responsive_image]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Tests the node output with responsive wrappers filter enabled.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('<img scr="#" class="img-responsive" />');
    $this->assertSession()->responseContains('<div class="table-responsive"><table class="table"></table></div>');
    $this->assertSession()->responseContains('<div class="embed-responsive embed-responsive-16by9"><iframe src="https://www.youtube.com/embed/" class="embed-responsive-item"></iframe></div>');

    // Set Bootstrap 4 output.
    $this->drupalGet('admin/config/content/responsivewrappers');
    $edit = ['responsivewrappers_version' => 4];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Update node to apply new filter settings.
    $this->node->setTitle('Responsive filter test B4');
    $this->node->save();
    // Tests Bootstrap 4 output.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('<img scr="#" class="img-fluid" />');
  }

}
