<?php

namespace Drupal\applenews;

/**
 * Provides data related to Apple News request.
 */
trait AppleNewsRequestDataTrait {

  /**
   * Fonts available.
   *
   * @see https://developer.apple.com/documentation/apple_news/apple_news_format/text_styles_and_effects/choosing_fonts_for_your_article
   *
   * @return array
   *   An array of font details.
   */
  protected function getFontData() {
    return [
      'Academy Engraved LET' => [],
      'Al Nile' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'American Typewriter' => [
        'fontWidth' => [
          'condensed' => [
            'fontWeight' => [
              'regular',
              'medium',
              'extra-bold',
            ],
          ],
          'normal' => [
            'fontWeight' => [
              'regular',
              'medium',
              'bold',
              'extra-bold',
            ],
          ],
        ],
      ],
      'Apple Color Emoji' => [],

      'Apple SD Gothic Neo' => [
        'fontWeight' => [
          'extra-light',
          'light',
          'regular',
          'medium',
          'semi-bold',
          'bold',
          'extra-bold',
        ],
      ],

      'Arial' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Arial Hebrew' => [
        'fontWeight' => [
          'regular',
          'medium',
          'extra-bold',
        ],
      ],
      'Arial Rounded MT Bold' => [],

      'Avenir' => [
        'fontWeight' => [
          'regular',
          'medium',
          'semi-bold',
          'extra-bold',
          'heavy',
        ],
      ],
      'Avenir Next' => [
        'fontWeight' => [
          'light',
          'medium',
          'semi-bold',
          'bold',
          'extra-bold',
          'heavy',
        ],
      ],
      'Avenir Next Condensed' => [
        'fontWeight' => [
          'light',
          'medium',
          'semi-bold',
          'bold',
          'extra-bold',
          'heavy',
        ],
      ],

      'Baskerville' => [
        'fontWeight' => [
          'medium',
          'bold',
          'extra-bold',
        ],
      ],
      'Bodoni 72' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Bodoni 72 Oldstyle' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Bodoni 72 Smallcaps' => [],

      'Bodoni Ornaments' => [],
      'Bradley Hand' => [],
      'Chalkboard SE' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Cochin' => [
        'fontWeight' => [
          'semi-bold',
          'extra-bold',
        ],
      ],
      'Copperplate' => [
        'fontWeight' => [
          'regular',
          'medium',
          'extra-bold',
        ],
      ],
      'Courier' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Courier New' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'DIN Alternate' => [],

      'DIN Condensed' => [],

      'Didot' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Euphemia UCAS' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Farah' => [],
      'Futura' => [
        'fontWidth' => [
          'condensed' => [
            'fontWeight' => [
              'semi-bold',
              'extra-bold',
            ],
          ],
          'normal' => [
            'fontWeight' => [
              'semi-bold',
              'extra-bold',
            ],
          ],
        ],
      ],
      'Georgia' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Gill Sans' => [
        'fontWeight' => [
          'regular',
          'medium',
          'bold',
          'extra-bold',
          'heavy',
        ],
      ],
      'Heiti SC' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Heiti TC' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Helvetica' => [
        'fontWeight' => [
          'regular',
          'medium',
          'extra-bold',
        ],
      ],
      'Helvetica Neue' => [
        'fontWidth' => [
          'condensed' => [
            'fontWeight' => [
              'extra-bold',
              'heavy',
            ],
          ],
          'normal' => [
            'fontWeight' => [
              'extra-light',
              'light',
              'regular',
              'medium',
              'semi-bold',
              'extra-bold',
            ],
          ],
        ],
      ],
      'Hiragino Maru Gothic ProN' => [],

      'Hiragino Mincho ProN' => [
        'fontWeight' => [
          'regular',
          'bold',
        ],
      ],
      'Hiragino Sans' => [
        'fontWeight' => [
          'regular',
          'bold',
        ],
      ],
      'Hoefler Text' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'ITC Franklin Gothic Std' => [],

      'Iowan Old Style' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Marion' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Marker Felt' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Menlo' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Noteworthy' => [
        'fontWeight' => [
          'regular',
          'extra-bold',
        ],
      ],
      'Optima' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
          'heavy',
        ],
      ],
      'Palatino' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Papyrus' => [
        'fontWidth' => [
          'condensed' => [],
          'normal' => [],
        ],
      ],
      'Party LET' => [],
      'PingFang HK' => [
        'fontWeight' => [
          'extra-light',
          'light',
          'regular',
          'medium',
          'semi-bold',
          'bold',
        ],
      ],
      'PingFang SC' => [
        'fontWeight' => [
          'extra-light',
          'light',
          'regular',
          'medium',
          'semi-bold',
          'bold',
        ],
      ],
      'PingFang TC' => [
        'fontWeight' => [
          'extra-light',
          'light',
          'regular',
          'medium',
          'semi-bold',
          'bold',
        ],
      ],
      'Savoye LET' => [],

      'Snell Roundhand' => [
        'fontWeight' => [
          'semi-bold',
          'extra-bold',
          'heavy',
        ],
      ],
      'Superclarendon' => [
        'fontWeight' => [
          'regular',
          'medium',
          'extra-bold',
          'heavy',
        ],
      ],
      'Thonburi' => [],

      'Times New Roman' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Trebuchet MS' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Verdana' => [
        'fontWeight' => [
          'medium',
          'extra-bold',
        ],
      ],
      'Zapf Dingbats' => [],
      'Zapfino' => [],
    ];
  }

}
