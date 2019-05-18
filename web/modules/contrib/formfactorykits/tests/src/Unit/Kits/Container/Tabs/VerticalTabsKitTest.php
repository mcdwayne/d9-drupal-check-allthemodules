<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Container\Tabs;

use Drupal\Tests\formfactorykits\Unit\Kits\Traits\StringTranslationTrait;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Container\Tabs\VerticalTabsKit
 * @group kit
 */
class VerticalTabsKitTest extends KitTestBase {
  use StringTranslationTrait;

  /**
   * @inheritdoc
   */
  public function getServices() {
    return [
      'string_translation' => $this->getTranslationManager(),
    ];
  }

  public function testDefaults() {
    $tabs = $this->k->verticalTabs();
    $this->assertArrayEquals([
      'vertical_tabs' => [
        '#parents' => ['vertical_tabs'],
        '#type' => 'vertical_tabs',
      ],
    ], [
      $tabs->getID() => $tabs->getArray(),
    ]);
  }

  public function testCustomID() {
    $verticalTabs = $this->k->verticalTabs('foo');
    $this->assertEquals('foo', $verticalTabs->getID());
  }

  public function testDefaultTab() {
    $tabs = $this->k->verticalTabs()
      ->setDefaultTab('foo');
    $this->assertArrayEquals([
      'vertical_tabs' => [
        '#parents' => ['vertical_tabs'],
        '#type' => 'vertical_tabs',
        '#default_tab' => 'edit-foo',
      ],
    ], [
      $tabs->getID() => $tabs->getArray(),
    ]);
  }

  public function testChildrenGrouped() {
    $tabs = $this->k->verticalTabs();
    $this->assertEquals(TRUE, $tabs::IS_CHILDREN_GROUPED);
  }

  public function testCreateTabs() {
    $tabs = $this->k->verticalTabs();
    $tabs->createTab('dogs')
      ->setTitle($this->t('Dogs'));
    $tabs->createTab('cats')
      ->setTitle($this->t('Cats'));

    $expected = [
      'vertical_tabs' => [
        '#parents' => ['vertical_tabs'],
        '#type' => 'vertical_tabs',
      ],
      'cats' => [
        '#group' => 'vertical_tabs',
        '#type' => 'details',
        '#title' => $this->t('Cats'),
      ],
      'dogs' => [
        '#group' => 'vertical_tabs',
        '#type' => 'details',
        '#title' => $this->t('Dogs'),
      ],
    ];
    $actual = [
      $tabs->getID() => $tabs->getArray(),
    ] + $tabs->getChildrenArray();
    $this->assertArrayEquals($expected, $actual);
  }

  // TODO: enable this test once related drupal.org class auto-loading issue has been resolved
  //  public function testCreateTabsWithOtherKits() {
  //    $tabs = $this->k->verticalTabs();
  //    $tabs->createTab('dogs')
  //      ->setTitle($this->t('Dogs'))
  //      ->append($this->k->image('dogs_image')
  //        ->setTitle($this->t('Image')))
  //      ->append($this->k->textarea('dogs_description')
  //        ->setTitle($this->t('Description')))
  //      ->append($this->k->checkboxes('dogs_attributes')
  //        ->setTitle($this->t('Attributes'))
  //        ->appendOption(['a' => $this->t('A')])
  //        ->appendOption(['b' => $this->t('B')])
  //        ->appendOption(['c' => $this->t('C')])
  //        ->setDefaultValue(['b']));
  //    $tabs->createTab('cats')
  //      ->setTitle($this->t('Cats'))
  //      ->append($this->k->image('cats_image')
  //        ->setTitle($this->t('Image')))
  //      ->append($this->k->textarea('cats_description')
  //        ->setTitle($this->t('Description')));
  //
  //    $expected = [
  //      'vertical_tabs' => [
  //        '#parents' => ['vertical_tabs'],
  //        '#type' => 'vertical_tabs',
  //      ],
  //      'cats' => [
  //        '#group' => 'vertical_tabs',
  //        '#type' => 'details',
  //        '#title' => $this->t('Cats'),
  //        'cats_image' => [
  //          '#type' => 'managed_file',
  //          '#title' => $this->t('Image'),
  //          '#upload_validators' => [
  //            'file_validate_extensions' => [
  //              'png gif jpg jpeg',
  //            ],
  //          ],
  //        ],
  //        'cats_description' => [
  //          '#type' => 'textarea',
  //          '#title' => $this->t('Description'),
  //        ],
  //      ],
  //      'dogs' => [
  //        '#group' => 'vertical_tabs',
  //        '#type' => 'details',
  //        '#title' => $this->t('Dogs'),
  //        'dogs_image' => [
  //          '#type' => 'managed_file',
  //          '#title' => $this->t('Image'),
  //          '#upload_validators' => [
  //            'file_validate_extensions' => [
  //              'png gif jpg jpeg',
  //            ],
  //          ],
  //        ],
  //        'dogs_description' => [
  //          '#type' => 'textarea',
  //          '#title' => $this->t('Description'),
  //        ],
  //        'dogs_attributes' => [
  //          '#type' => 'checkboxes',
  //          '#title' => $this->t('Attributes'),
  //          '#default_value' => ['b'],
  //          '#options' => [
  //            'a' => $this->t('A'),
  //            'b' => $this->t('B'),
  //            'c' => $this->t('C'),
  //          ]
  //        ],
  //      ],
  //    ];
  //    $actual = [
  //      $tabs->getID() => $tabs->getArray(),
  //    ] + $tabs->getChildrenArray();
  //    $this->assertArrayEquals($expected, $actual);
  //  }
}
