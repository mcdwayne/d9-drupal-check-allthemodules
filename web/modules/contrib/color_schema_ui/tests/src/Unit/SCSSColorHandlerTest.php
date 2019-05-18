<?php

namespace Drupal\Tests\color_schema_ui\Unit;

use Drupal\color_schema_ui\SCSSColorHandler;
use Drupal\Tests\UnitTestCase;


class SCSSColorHandlerTest extends UnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider provideSCSSDataForReplacement
   */
  public function testColorVariableReplacement(string $originalSCSS, string $expectedSCSS, string $colorName, string $colorValue): void {
    $scssCompiler = new SCSSColorHandler();

    $computedSCSS = $scssCompiler->replaceColors($originalSCSS, [$colorName => $colorValue]);

    self::assertEquals($expectedSCSS, $computedSCSS);
  }

  /**
   * @dataProvider provideSCSSDateForGet
   */
  public function testGetInitialColor(string $originalSCSS, array $expectedResult) {
    $scssCompiler = new SCSSColorHandler();
    $computedSCSS = $scssCompiler->getInitialColors($originalSCSS);

    $this->assertArrayEquals($computedSCSS, $expectedResult);
  }

  public function provideSCSSDateForGet(): array {
    return [
      [
        '$site-background-color: rgb(63, 255, 40);
$peripheral-color-primary: rgb(92, 166, 255);
$peripheral-color-secondary: rgb(11, 9, 255);
$header-background-color: rgb(248, 255, 46);
$content-color: rgb(177, 176, 171);

$font-content-color: rgb(255,0,0);
$font-header-color: rgb(255, 0, 202);
$font-footer-color: rgb(110, 255, 181);

/*** BACKGROUND COLORS - BEGIN ***/

.main-wrapper,
.header-wrapper,
.footer-wrapper,
.header__upper-menu,
body {
  background-color: $site-background-color;
}

footer {
  .footer__upper,
  .footer__bottom-menu {
    background-color: $peripheral-color-primary;
  }
  .footer__menu {
    background-color: $peripheral-color-secondary;
  }
}

.content-header__text,
.scroll-to-top,
.region-header-region,
.paragraph__header i {
  background-color: $peripheral-color-primary;
}

.full .header {
  background-color: $peripheral-color-primary;
  &:after {
    border-top: 15px solid $peripheral-color-primary;
  }
}

.paragraph__header {
  background-color: $peripheral-color-secondary;
}

.nrw-menu-header__icon.is-active:after {
  border-bottom: 10px solid $peripheral-color-secondary;
  border-bottom-color: $peripheral-color-secondary;
}

.header__upper-menu,
.nrw-menu-header__wrapper,
.nrw-menu-header__content,
.nrw-menu-header__content .nrw-menu__extra,
.nrw-menu-header__col:hover,
.nrw-menu-header__col:focus {
  background-color: $peripheral-color-secondary;
}

.header__branding, .nrw-menu-header, .nrw-menu-header__icons {
  background-color: $header-background-color;
}

.main-container {
  background-color: $content-color;
}

article.preview:nth-child(4n-2) .teaser-image:after,
article.preview:nth-child(4n-3) .teaser-image:after,
article.slim:nth-child(4n-2) .teaser-image:after,
article.slim:nth-child(4n-3) .teaser-image:after {
  border-right: 15px solid $content-color;
}

article.long-text:nth-child(odd) .teaser-image:after {
  border-left: 15px solid $content-color;
}

article.long-text:nth-child(even) .teaser-image:after {
  border-right: 15px solid $content-color;
}

article.preview:nth-child(4n-1) .teaser-image:after,
article.preview:nth-child(4n) .teaser-image:after,
article.slim:nth-child(4n-1) .teaser-image:after,
article.slim:nth-child(4n) .teaser-image:after {
  border-left: 15px solid $content-color;
}

.footer__upper:before {
  background-color: $peripheral-color-secondary;
}
/*** BACKGROUND COLORS - END ***/

/*** FONT COLORS - BEGIN ***/
.faq_question, body {
  color: $font-content-color;
}

.nrw-menu ul li span,
.content-header__text,
.field--name-field-tags .field--item a,
.tags:before,
.region-header-region,
.region-header-region a,
.header__upper-menu .menu ul li a,
.header__upper-menu,
.content-header__text,
.nrw-menu-header__wrapper a {
  color: $font-header-color;
}

.footer {
  color: $font-footer-color;
  a {
    color: $font-footer-color;
  }
}

.footer__upper:before {
  color: $font-footer-color;
}
/*** FONT COLORS - END ***/',
        [
          'site-background-color'      => 'rgb(63, 255, 40)',
          'peripheral-color-primary'   => 'rgb(92, 166, 255)',
          'peripheral-color-secondary' => 'rgb(11, 9, 255)',
          'header-background-color'    => 'rgb(248, 255, 46)',
          'content-color'              => 'rgb(177, 176, 171)',
          'font-content-color'         => 'rgb(255,0,0)',
          'font-header-color'          => 'rgb(255, 0, 202)',
          'font-footer-color'          => 'rgb(110, 255, 181)',
        ],
      ]
    ];
  }

  public function provideSCSSDataForReplacement(): array {
    return [
      ['$site-background-color: rgb(63, 255, 40);
$peripheral-color-primary: rgb(92, 166, 255);
$peripheral-color-secondary: rgb(11, 9, 255);
$header-background-color: rgb(248, 255, 46);
$content-color: rgb(177, 176, 171);

$font-content-color: rgb(255,0,0);
$font-header-color: rgb(255, 0, 202);
$font-footer-color: rgb(110, 255, 181);

/*** BACKGROUND COLORS - BEGIN ***/

.main-wrapper,
.header-wrapper,
.footer-wrapper,
.header__upper-menu,
body {
  background-color: $site-background-color;
}',
      '$site-background-color: rgb(177, 176, 171);
$peripheral-color-primary: rgb(92, 166, 255);
$peripheral-color-secondary: rgb(11, 9, 255);
$header-background-color: rgb(248, 255, 46);
$content-color: rgb(177, 176, 171);

$font-content-color: rgb(255,0,0);
$font-header-color: rgb(255, 0, 202);
$font-footer-color: rgb(110, 255, 181);

/*** BACKGROUND COLORS - BEGIN ***/

.main-wrapper,
.header-wrapper,
.footer-wrapper,
.header__upper-menu,
body {
  background-color: $site-background-color;
}',
       'site_background_color',
       'rgb(177, 176, 171)'
      ],
      ['$site-background-color: rgb(63, 255, 40);
$peripheral-color-primary: rgb(92, 166, 255);
$peripheral-color-secondary: rgb(11, 9, 255);
$header-background-color: rgb(248, 255, 46);
$content-color: rgb(177, 176, 171);

$font-content-color: rgb(255,0,0);
$font-header-color: rgb(255, 0, 202);
$font-footer-color: rgb(110, 255, 181);

/*** BACKGROUND COLORS - BEGIN ***/

.main-wrapper,
.header-wrapper,
.footer-wrapper,
.header__upper-menu,
body {
  background-color: $site-background-color;
}',
       '$site-background-color: rgb(63, 255, 40);
$peripheral-color-primary: rgb(92, 166, 255);
$peripheral-color-secondary: rgb(11, 9, 255);
$header-background-color: rgb(248, 255, 46);
$content-color: rgb(177, 176, 171);

$font-content-color: rgb(255,0,0);
$font-header-color: rgb(255, 0, 202);
$font-footer-color: rgb(110, 255, 181);

/*** BACKGROUND COLORS - BEGIN ***/

.main-wrapper,
.header-wrapper,
.footer-wrapper,
.header__upper-menu,
body {
  background-color: $site-background-color;
}',
       'font_content_color',
       'rgb(255,0,0)'
      ]
    ];
  }

}
