<?php

namespace Drupal\sir_trevor\Tests\Unit;

use Drupal\sir_trevor\LibraryInfoBuilder;
use Drupal\sir_trevor\Plugin\SirTrevorBlock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorBlockMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorMixinMock;
use Drupal\Tests\sir_trevor\Unit\TestDoubles\SirTrevorPluginManagerMock;

/**
 * @group SirTrevor
 */
class LibraryInfoBuilderTest extends \PHPUnit_Framework_TestCase {
  /** @var SirTrevorPluginManagerMock */
  private $blockPluginManager;
  /** @var LibraryInfoBuilder */
  private $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->blockPluginManager = new SirTrevorPluginManagerMock();
    $this->sut = new LibraryInfoBuilder($this->blockPluginManager);
  }

  public function getLibraryInfoTestDataProvider() {
    $data['single instance, no assets'] = [
      'instances' => [
        new SirTrevorBlockMock('some_id'),
      ],
      'expected' => [],
    ];

    $data['single instance, editor js'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorJs', 'path/to/editor/js'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'js' => [
              'path/to/editor/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single mixin instance, editor js'] = [
      'instances' => [
        (new SirTrevorMixinMock('some_id'))
          ->set('editorJs', 'path/to/editor/js'),
      ],
      'expected' => [
        'sir_trevor' => [
          'mixin.some_id.editor' => [
            'js' => [
              'path/to/editor/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, editor js with dependency'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorJs', 'path/to/editor/js')
          ->set('editorDependencies', ['core/jquery']),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'js' => [
              'path/to/editor/js' => [],
            ],
            'dependencies' => [
              'core/jquery',
              'sir_trevor/sir-trevor',
            ],
          ],
        ],
      ],
    ];

    $data['single instance, editor js - multiple files'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorJs', ['path/to/editor/js', 'path/to/second/js']),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'js' => [
              'path/to/editor/js' => [],
              'path/to/second/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, editor css'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorCss', 'path/to/editor/css'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'css' => [
              'theme' => [
                'path/to/editor/css' => [],
              ],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];
    $data['single instance, editor css - multiple files'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorCss', ['path/to/editor/css', 'path/to/second/css']),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'css' => [
              'theme' => [
                'path/to/editor/css' => [],
                'path/to/second/css' => [],
              ],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, editor js + css'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorJs', 'path/to/editor/js')
          ->set('editorCss', 'path/to/editor/css'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'css' => [
              'theme' => [
                'path/to/editor/css' => [],
              ],
            ],
            'js' => [
              'path/to/editor/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, display js'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayJs', 'path/to/display/js'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.display' => [
            'js' => [
              'path/to/display/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, display js with dependency'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayJs', 'path/to/display/js')
          ->set('displayDependencies', ['core/jquery']),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.display' => [
            'js' => [
              'path/to/display/js' => [],
            ],
            'dependencies' => [
              'core/jquery',
              'sir_trevor/sir-trevor',
            ],
          ],
        ],
      ],
    ];

    $data['single instance, display js - multiple files'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayJs', ['path/to/display/js', 'path/to/second/js']),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.display' => [
            'js' => [
              'path/to/display/js' => [],
              'path/to/second/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, display css'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayCss', 'path/to/display/css'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.display' => [
            'css' => [
              'theme' => [
                'path/to/display/css' => [],
              ],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, display css - multiple files'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayCss', ['path/to/display/css', 'path/to/second/css']),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.display' => [
            'css' => [
              'theme' => [
                'path/to/display/css' => [],
                'path/to/second/css' => [],
              ],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, display js + css'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayJs', 'path/to/display/js')
          ->set('displayCss', 'path/to/display/css'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.display' => [
            'css' => [
              'theme' => [
                'path/to/display/css' => [],
              ],
            ],
            'js' => [
              'path/to/display/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['single instance, all js + css'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('displayJs', 'path/to/display/js')
          ->set('displayCss', 'path/to/display/css')
          ->set('editorJs', 'path/to/editor/js')
          ->set('editorCss', 'path/to/editor/css'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'css' => [
              'theme' => [
                'path/to/editor/css' => [],
              ],
            ],
            'js' => [
              'path/to/editor/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
          'block.some_id.display' => [
            'css' => [
              'theme' => [
                'path/to/display/css' => [],
              ],
            ],
            'js' => [
              'path/to/display/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    $data['multiple instances, combined assets'] = [
      'instances' => [
        (new SirTrevorBlockMock('some_id'))
          ->set('editorJs', 'path/to/editor/js'),
        (new SirTrevorBlockMock('some_other_id', 'st_extension'))
          ->set('editorCss', 'path/to/editor/css'),
      ],
      'expected' => [
        'sir_trevor' => [
          'block.some_id.editor' => [
            'js' => [
              'path/to/editor/js' => [],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
        'st_extension' => [
          'block.some_other_id.editor' => [
            'css' => [
              'theme' => [
                'path/to/editor/css' => [],
              ],
            ],
            'dependencies' => ['sir_trevor/sir-trevor'],
          ],
        ],
      ],
    ];

    return $data;
  }

  /**
   * @test
   * @dataProvider getLibraryInfoTestDataProvider
   * @param SirTrevorBlock[] $pluginInstances
   * @param array $expectedResult
   */
  public function getLibraryInfo(array $pluginInstances, array $expectedResult) {
    $this->blockPluginManager->setInstances($pluginInstances);
    $this->assertEquals($expectedResult, $this->sut->getLibraryInfo());
  }
}
