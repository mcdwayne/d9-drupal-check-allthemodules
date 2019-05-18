<?php

namespace Drupal\Tests\panels_everywhere\Functional;

use Drupal\page_manager\Entity\PageVariant;
use Drupal\Tests\BrowserTestBase;

/**
 * Make sure that PE can be enabled.
 *
 * @group panels_everywhere
 */
class PanelsEverywhereTest extends PanelsEverywhereBrowserTestBase {

  /**
   * Verify the front page still loads while site_template is disabled.
   */
  public function testFrontPage() {
    $siteTemplate = $this->loadSiteTemplate();
    $this->assertEquals(FALSE, $siteTemplate->status(), 'Expect the site_template to be disabled by default');

    $this->checkFrontPageWorks();
  }

  /**
   * Verify that other pages load before and after enabling site_template.
   */
  public function testOtherPages() {
    // Check that 404 pages loads properly by default.
    $this->drupalGet('/some/page/that/should/not/exist');
    $this->assertSession()->statusCodeEquals(404);

    // Check that the login page load properly by default.
    $this->drupalGet('/user/login');
    $this->assertSession()->statusCodeEquals(200);

    // Enable site template & clear page-cache.
    $this->loadSiteTemplate()
      ->setStatus(TRUE)
      ->save();
    drupal_flush_all_caches();

    // Check that 404 pages loads properly.
    $this->drupalGet('/some/page/that/should/not/exist');
    $this->assertSession()->statusCodeEquals(404);

    // Check that the login page load properly.
    $this->drupalGet('/user/login');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Verify that enabling and disabling site_template.
   */
  public function testSiteTemplate() {
    $pageText = 'No front page content has been created yet.';

    $siteTemplate = $this->loadSiteTemplate();
    $this->assertEquals(FALSE, $siteTemplate->status(), 'Expect the site_template to be disabled by default');
    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains($pageText);

    $this->enableSiteTemplate();
    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextNotContains($pageText);

    $this->loadSiteTemplate()
      ->setStatus(FALSE)
      ->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();
    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains($pageText);
  }

  /**
   * Verify that placed blocks actually show up.
   */
  public function testBlockPlacement() {
    $this->enableSiteTemplate();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextNotContains('Powered by');

    $siteTemplate = $this->loadSiteTemplate();
    $defaultVariant = $siteTemplate->getVariant('panels_everywhere');

    $this->placeBlockOnVariant($defaultVariant, 'system_powered_by_block', 'content');
    $defaultVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains('Powered by');
    $this->assertSession()->pageTextNotContains('No front page content has been created yet.');

    $siteTemplate = $this->loadSiteTemplate();
    $defaultVariant = $siteTemplate->getVariant('panels_everywhere');

    $this->placeBlockOnVariant($defaultVariant, 'system_main_block', 'content');
    $defaultVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains('No front page content has been created yet.');
  }

  /**
   * Verify that using site_template for only part of the page breaks nothing.
   */
  public function testMixingRegularAndPanelsEverywherePages() {
    $this->enableSiteTemplate();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextNotContains('No front page content has been created yet.');

    $siteTemplate = $this->loadSiteTemplate();
    $defaultVariant = $siteTemplate->getVariant('panels_everywhere');

    $this->addPathCondition($defaultVariant, '<front>', TRUE);
    $defaultVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains('No front page content has been created yet.');

    $this->drupalGet('/user/login');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Create new account');
  }

  /**
   * Verify that multiple variants work.
   */
  public function testCustomVariants() {
    $this->enableSiteTemplate();

    $siteTemplate = $this->loadSiteTemplate();
    $defaultVariant = $siteTemplate->getVariant('panels_everywhere');
    $this->addPathCondition($defaultVariant, '<front>', TRUE);
    $defaultVariant->save();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains('No front page content has been created yet.');

    $customVariant = $this->pageVariantStorage->create([
      'id' => 'this-is-a-custom-variant',
      'variant' => 'panels_everywhere_variant',
      'variant_settings' => [
        'id' => 'panels_everywhere_variant',
        'layout' => 'layout_onecol',
        'builder' => 'standard',
      ],
    ]);
    $customVariant->setPageEntity($siteTemplate);
    $customVariant->save();
    $this->addPathCondition($customVariant, '<front>');
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextNotContains('No front page content has been created yet.');

    $this->pageVariantStorage->delete([$customVariant]);
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->checkFrontPageWorks();
    $this->assertSession()->pageTextContains('No front page content has been created yet.');
  }

  /**
   * Verify that visiting the path of site_template does not break anything.
   */
  public function testCallingSiteTemplateConfigurationPath() {
    $this->enableSiteTemplate();

    $siteTemplate = $this->loadSiteTemplate();
    $this->drupalGet($siteTemplate->getPath());
    $this->assertSession()->statusCodeEquals(404);

    $defaultVariant = $siteTemplate->getVariant('panels_everywhere');
    $this->placeBlockOnVariant($defaultVariant, 'system_main_block', 'content');
    $defaultVariant->save();
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();

    $this->drupalGet($siteTemplate->getPath());
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Retrieves an un-cached version of the site_template from storage.
   *
   * @return \Drupal\page_manager\PageInterface
   *   The site_template.
   */
  protected function loadSiteTemplate() {
    $this->pageStorage->resetCache(['site_template']);
    $site_template = $this->pageStorage->load('site_template');
    return $site_template;
  }

  /**
   * Enables the site_template & flush caches.
   */
  protected function enableSiteTemplate() {
    $this->loadSiteTemplate()
      ->setStatus(TRUE)
      ->save();

    // This ensures that the page-cache is empty for any test that follows.
    // @todo: Remove once cache info is setup correctly
    drupal_flush_all_caches();
  }


  /**
   * Visits the front page and checks for a 200 status code.
   */
  protected function checkFrontPageWorks() {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);
  }

}
