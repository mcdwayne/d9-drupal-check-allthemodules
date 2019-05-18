<?php

namespace Drupal\packages_example_page\Plugin\Package;

use Drupal\packages\Plugin\PackageBase;

/**
 * Provides an example package which exposes a page to the user when enabled.
 *
 * The page is located at /packages-example-page.
 *
 * The additional permission is optional. Packages will also check that the user
 * has the 'access packages' permission. For a more complex example that includes
 * a configurable package, see packages_example_login_greeting.
 *
 * 'enabled' determines if the package is enabled by default without the user
 * having to enable it. This package is not enabled by default.
 *
 * @Package(
 *   id = "example_page",
 *   label = @Translation("Package example: Page"),
 *   description = @Translation("This example package exposes a page to users located at /packages-example-page."),
 *   enabled = FALSE,
 *   permission = "access packages example page",
 *   configurable = FALSE,
 *   default_settings = {}
 * )
 */
class ExamplePage extends PackageBase {

  /**
   * Build the custom page.
   *
   * @see Drupal\packages_example_page\Controller\PackageExamplePageController::index()
   *
   * @return array
   *   A renderable array.
   */
  public function page() {
    return [
      '#markup' => $this->t('This page is only accessible to users that have access to this package (with the "access packages example page") and have enabled it on the Packages form.'),
    ];
  }

}
