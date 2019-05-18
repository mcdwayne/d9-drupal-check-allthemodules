<?php

namespace Drupal\Tests\paragraphs_collection_bootstrap_demo\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the demo module for Paragraphs Collection Bootstrap.
 *
 * @group paragraphs_collection_bootstrap_demo
 */
class ParagraphsCollectionBootstrapDemoTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = [
    'paragraphs_collection_bootstrap_demo',
    'link',
    'text',
    'node',
    'menu_ui',
  ];

  /**
   * Asserts demo content have been created.
   */
  public function testDemoModule() {
    $account = $this->drupalCreateUser(['access content overview']);
    $this->drupalLogin($account);
    // Assert we have node created and we can visit node page.
    $node = $this->getNodeByTitle('Paragraphs Collection Bootstrap demo example');
    $this->drupalGet('node/' . $node->id());
    // Assert we have progress bars created.
    $this->assertSession()->responseContains('class="progress-bar progress-bar-striped bg-danger paragraph paragraph--type--pcb-progress-bar paragraph--view-mode--default"');
    $this->assertSession()->responseContains('class="progress-bar bg-info paragraph paragraph--type--pcb-progress-bar paragraph--view-mode--default"');
    $this->assertSession()->responseContains('class="progress-bar progress-bar-striped progress-bar-animated bg-warning paragraph paragraph--type--pcb-progress-bar paragraph--view-mode--default"');
    $this->assertSession()->responseContains('class="progress-bar paragraph paragraph--type--pcb-progress-bar paragraph--view-mode--default"');
    // Assert we have accordion created.
    $this->assertSession()->responseContains('id="heading--0"');
    $this->assertSession()->responseContains('id="heading--1"');
    $this->assertSession()->responseContains('id="heading--2"');
    $this->assertSession()->responseContains('id="collapse--0"');
    $this->assertSession()->responseContains('id="collapse--1"');
    $this->assertSession()->responseContains('id="collapse--2"');
    // Assert titles.
    $this->assertSession()->pageTextContains('Accordion title #1');
    $this->assertSession()->pageTextContains('Accordion title #2');
    $this->assertSession()->pageTextContains('Accordion title #3');
    // Assert content texts.
    $this->assertSession()->pageTextContains('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');
    $this->assertSession()->pageTextContains('Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?');
    $this->assertSession()->pageTextContains('On the other hand, we denounce with righteous indignation and dislike men who are so beguiled and demoralized by the charms of pleasure of the moment, so blinded by desire, that they cannot foresee the pain and trouble that are bound to ensue; and equal blame belongs to those who fail in their duty through weakness of will, which is the same as saying through shrinking from toil and pain. These cases are perfectly simple and easy to distinguish. In a free hour, when our power of choice is untrammelled and when nothing prevents our being able to do what we like best, every pleasure is to be welcomed and every pain avoided. But in certain circumstances and owing to the claims of duty or the obligations of business it will frequently occur that pleasures have to be repudiated and annoyances accepted. The wise man therefore always holds in these matters to this principle of selection: he rejects pleasures to secure other greater pleasures, or else he endures pains to avoid worse pains.');
    // Assert we have tabs created.
    $tabs_0_xpath = '//div[@id="bootstrap-tabs"]/ul[contains(@class, "nav-tabs")]/li/a[@href="#bootstrap-tabs-0"]';
    $this->assertSession()->elementExists('xpath', $tabs_0_xpath);
    $tabs_1_xpath = '//div[@id="bootstrap-tabs"]/ul[contains(@class, "nav-tabs")]/li/a[@href="#bootstrap-tabs-1"]';
    $this->assertSession()->elementExists('xpath', $tabs_1_xpath);
    $tabs_2_xpath = '//div[@id="bootstrap-tabs"]/ul[contains(@class, "nav-tabs")]/li/a[@href="#bootstrap-tabs-2"]';
    $this->assertSession()->elementExists('xpath', $tabs_2_xpath);
    $this->assertSession()->responseContains('id="bootstrap-tabs-0"');
    $this->assertSession()->responseContains('id="bootstrap-tabs-1"');
    $this->assertSession()->responseContains('id="bootstrap-tabs-2"');
    // Assert titles.
    $this->assertSession()->pageTextContains('Tabs title #1');
    $this->assertSession()->pageTextContains('Tabs title #2');
    $this->assertSession()->pageTextContains('Tabs title #3');
    // Assert content texts.
    $this->assertSession()->pageTextContains('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');
    $this->assertSession()->pageTextContains('Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?');
    $this->assertSession()->pageTextContains('At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.');
    // Assert we have node created and we can visit node page.
    $node = $this->getNodeByTitle('Paragraphs Collection Bootstrap demo example');
    $this->drupalGet('node/' . $node->id());
    // Assert we have carousel created.
    $this->assertSession()->responseContains('class="carousel-indicators"');
    $this->assertSession()->responseContains('class="carousel-inner"');
    $this->assertSession()->responseContains('class="carousel-control-prev"');
    $this->assertSession()->responseContains('class="carousel-control-next"');
    // Check if images are uploaded.
    $this->assertSession()->responseContains('red-background-min.png');
    $this->assertSession()->responseContains('blue-background-min.png');
    $this->assertSession()->responseContains('grey-background-min.png');
    $this->assertSession()->responseContains('brown-background-min.png');
    // Check for caption.
    $this->assertSession()->pageTextContains('Red background');
    $this->assertSession()->pageTextContains('Blue background');
    $this->assertSession()->pageTextContains('Grey background');
    $this->assertSession()->pageTextContains('Brown background');
    // Check for alternative title.
    $this->assertSession()
      ->responseContains('Red background alternative image title');
    $this->assertSession()
      ->responseContains('Blue background alternative image title');
    $this->assertSession()
      ->responseContains('Grey background alternative image title');
    $this->assertSession()
      ->responseContains('Brown background alternative image title');

    // Assert that we have a popover created.
    $this->assertSession()->pageTextContains('Popover text example.');
    $this->assertSession()->pageTextContains('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut vitae enim dui. Suspendisse neque velit, scelerisque eget sagittis non, ullamcorper in metus. Nulla cursus ipsum sit amet consequat accumsan. Mauris nec magna lacus. Sed vitae felis dictum, vestibulum quam vitae, tempor felis. Duis a ornare augue. Fusce fermentum purus quis metus aliquam accumsan. Morbi eleifend tincidunt rutrum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer a ex aliquam, interdum nisi in, venenatis nunc.');

    // Assert that we have created a tooltip.
    $this->assertSession()->responseContains('title="Tooltip text example."');
    $this->assertSession()->pageTextContains('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut vitae enim dui. Suspendisse neque velit, scelerisque eget sagittis non, ullamcorper in metus. Nulla cursus ipsum sit amet consequat accumsan. Mauris nec magna lacus. Sed vitae felis dictum, vestibulum quam vitae, tempor felis. Duis a ornare augue. Fusce fermentum purus quis metus aliquam accumsan. Morbi eleifend tincidunt rutrum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer a ex aliquam, interdum nisi in, venenatis nunc.');
  }

  public function testDemoUninstall() {
    $account = $this->drupalCreateUser(['administer modules']);
    $this->drupalLogin($account);
    // Uninstall module.
    $this->drupalGet('admin/modules/uninstall');
    $this->getSession()->getPage()->checkField('uninstall[paragraphs_collection_bootstrap_demo]');
    $this->getSession()->getPage()->pressButton('op');
    // Confirm uninstalling module.
    $this->getSession()->getPage()->pressButton('op');
    // Assert that content is deleted.
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextNotContains('Paragraphs Collection Bootstrap demo example');
  }

}
