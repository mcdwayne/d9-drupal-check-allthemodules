<?php

namespace Drupal\Tests\whitelabel\FunctionalJavascript;

/**
 * Tests added style sheets on white labeled pages.
 *
 * @group whitelabel
 */
class WhiteLabelStyleSheetTest extends WhiteLabelJavascriptTestBase {

  /**
   * Holds the second generated white label throughout the different tests.
   *
   * @var \Drupal\whitelabel\WhiteLabelInterface
   */
  private $whiteLabel2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'color',
    'whitelabel_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a secondary white label.
    $this->whiteLabel2 = $this->drupalCreateWhiteLabel([
      'token' => 'token2',
      'uid' => $this->whiteLabelOwner->id(),
      'name' => 'White label 2',
    ]);

    // Make sure everything is enabled by default.
    $this->config('whitelabel.settings')
      ->setData([
        'site_name' => TRUE,
        'site_name_display' => TRUE,
        'site_slogan' => TRUE,
        'site_logo' => TRUE,
        'site_theme' => TRUE,
        'site_colors' => TRUE,
      ])
      ->save();
  }

  /**
   * Test to see if a stylesheet is properly generated.
   */
  public function testStyleSheetGenerated() {
    // Generate a stylesheet.
    $palette_colors = [
      'top' => '#cd2d2d',
      'bottom' => '#d64e4e',
      'bg' => '#ffffff',
      'sidebar' => '#f1f4f0',
      'sidebarborders' => '#ededed',
      'footer' => '#1f1d1c',
      'titleslogan' => '#fffeff',
      'text' => '#888888',
      'link' => '#d6121f',
    ];
    $this->whiteLabel
      ->setTheme('bartik')
      ->setPalette($palette_colors)
      ->save();

    // Ensure CSS file is generated.
    $file_path = 'public://whitelabel/' . $this->whiteLabel->getToken() . '/colors.css';
    $this->assertFileExists($file_path);

    // Assert all defined colors are present in the file.
    $css_file = file_get_contents($file_path);
    foreach ($palette_colors as $region => $color) {
      $this->assertContains($color, $css_file, "Color $color has been applied for $region.");
    }

    // Update the stylesheet.
    $new_palette_colors = [
      'top' => '#000000',
      'bottom' => '#000000',
      'bg' => '#000000',
      'sidebar' => '#000000',
      'sidebarborders' => '#000000',
      'footer' => '#000000',
      'titleslogan' => '#000000',
      'text' => '#000000',
      'link' => '#000000',
    ];
    $this->whiteLabel
      ->setPalette($new_palette_colors)
      ->save();

    // Assert all previous colors are gone.
    $css_file = file_get_contents($file_path);
    foreach ($palette_colors as $region => $color) {
      $this->assertNotContains($color, $css_file, "Color $color has not been applied for $region.");
    }
    // Assert all new colors are present.
    foreach ($new_palette_colors as $region => $color) {
      $this->assertContains($color, $css_file, "Color $color has been applied for $region.");
    }
  }

  /**
   * Test to see if the correct stylesheet is applied to pages.
   */
  public function testStyleSheetApplied() {
    // Install and enable a theme with color support.
    $this->container->get('theme_installer')->install(['bartik']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();

    // Set white label theme and palette.
    $this->whiteLabel
      ->setTheme('bartik')
      ->setPalette([
        'top' => '#cd2d2d',
        'bottom' => '#d64e4e',
        'bg' => '#ffffff',
        'sidebar' => '#f1f4f0',
        'sidebarborders' => '#ededed',
        'footer' => '#1f1d1c',
        'titleslogan' => '#fffeff',
        'text' => '#888888',
        'link' => '#d6121f',
      ])->save();

    // Get path.
    $file_path = 'public://whitelabel/' . $this->whiteLabel->getToken() . '/colors.css';
    $url = file_create_url($file_path);
    $html_path = file_url_transform_relative($url);

    // Assert stylesheet is not on the page.
    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains($html_path);

    // Enable the white label.
    $this->setCurrentWhiteLabel($this->whiteLabel);

    // Assert stylesheet is on page.
    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($html_path);

    // Assert that the element has the right color. By default, not all regions
    // are populated, but the footer is. So we only check the footer color. It
    // is representative for knowing if the stylesheet has been applied or not.
    $get_element_color = <<<JS
      (function() { 
        var elem = document.getElementsByClassName("site-footer")[0];
        var rgb = window.getComputedStyle(elem).getPropertyValue("background-color");
        rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);
        function hex(x) {
          return ("0" + parseInt(x).toString(16)).slice(-2);
        }
        return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
      }());
JS;
    $detected_color = $this->getSession()->evaluateScript($get_element_color);
    $this->assertEquals('#1f1d1c', $detected_color);

    // Reset white label.
    $this->resetWhiteLabel();
    $this->assertSession()->responseNotContains($html_path);

    // Enable another white label.
    $this->setCurrentWhiteLabel($this->whiteLabel2);

    // Assert stylesheet is not page.
    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains($html_path);

    // Update style sheet (white label colors)
    $this->whiteLabel->setPalette([
      'top' => '#d0d0d0',
      'bottom' => '#c2c4c5',
      'bg' => '#ffffff',
      'sidebar' => '#ffffff',
      'sidebarborders' => '#cccccc',
      'footer' => '#016b83',
      'titleslogan' => '#000000',
      'text' => '#4a4a4a',
      'link' => '#019dbf',
    ])->save();

    // Assert stylesheet is still not on page for white label 2.
    $this->drupalGet('<front>');
    $this->assertSession()->responseNotContains($html_path);

    // Switch back to white label 1.
    $this->setCurrentWhiteLabel($this->whiteLabel);

    // Assert again for updated colors.
    $this->drupalGet('<front>');
    $this->assertSession()->responseContains($html_path);

    // Assert that the element has the right color.
    $detected_color = $this->getSession()->evaluateScript($get_element_color);
    $this->assertEquals('#016b83', $detected_color);

    // Reset white label.
    $this->resetWhiteLabel();
    $this->assertSession()->responseNotContains($html_path);
  }

}
