<?php
namespace Drupal\Tests\linkback\Kernel;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\node\NodeInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a base class for Commerce kernel tests.
 */
abstract class LinkbackKernelTestBase extends EntityKernelTestBase
{
    use ContentTypeCreationTrait;
    /**
   * Modules to enable.
   *
   * Note that when a child class declares its own $modules list, that list
   * doesn't override this one, it just extends it.
   *
   * @var array
   */
    public static $modules = [
    'system',
    'node',
    'linkback',
    'filter',
    'text',
    'user',
    'field',
    'search',
    'theme_test',
    'locale',
    'language',
    'content_translation'
    ];

    /**
   * The Linkback service.
   *
   * @var \Drupal\linkback\LinkbackService
   */
    protected $linkbackService;

    /**
   * {@inheritdoc}
   */
    protected function setUp() 
    {
        parent::setUp();
        $this->installSchema('system', 'router');
        $this->installSchema('search', ['search_index', 'search_dataset', 'search_total']);
        $this->installSchema('node', 'node_access');
        $this->installEntitySchema('user');
        $this->installEntitySchema('node');
        $this->installEntitySchema('linkback');
        $this->installSchema('locale', ['locales_source', 'locales_target', 'locales_location']);
        $this->installConfig('filter');
        $this->installConfig('node');
        $this->installConfig(['search']);

        if ($this->profile != 'standard') {
            $this->createContentType(array('type' => 'article', 'name' => 'Article'));
        };
        \Drupal::service('theme_handler')->install(array('test_theme'));
        $field_storage = FieldStorageConfig::create(
            [
            'field_name' => 'field_linkbacks',
            'entity_type' => 'article',
            'type' => 'linkback_handlers'
            ]
        );
    }
}
