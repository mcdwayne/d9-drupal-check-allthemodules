<?php

namespace Drupal\Tests\shortcut_menu\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\shortcut_menu\Form\ShortcutMenuSetCustomize;

/**
 * Class ShortcutMenuTest to test module functionality.
 *
 * @coversDefaultClass \Drupal\shortcut_menu\Form\ShortcutMenuSetCustomize
 *
 * @group shortcut_menu
 */
class ShortcutMenuTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'link',
    'shortcut',
    'shortcut_menu',
  ];

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Shortcut set entity object.
   *
   * @var \Drupal\shortcut\ShortcutSetInterface
   */
  protected $shortcutSet;

  /**
   * Shortcut entity object.
   *
   * @var \Drupal\shortcut\ShortcutInterface
   */
  protected $shortcut;

  /**
   * Shortcut entity object.
   *
   * @var \Drupal\shortcut\ShortcutInterface
   */
  protected $parentShortcut;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('shortcut_set');
    $this->installEntitySchema('shortcut');
    $this->database = $this->container->get('database');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->shortcutSet = ShortcutSet::create([
      'id' => 'default',
      'label' => 'Default',
    ]);
    $this->shortcutSet->save();
    $this->parentShortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => 'Content',
      'link' => ['uri' => 'internal:/admin/content'],
    ]);
    $this->parentShortcut->save();

    $this->shortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => 'Add Content',
      'link' => ['uri' => 'internal:/node/add'],
    ]);
    $this->shortcut->save();
  }

  /**
   * Test the columns exist in the table.
   */
  public function testShortcutTable() {
    $this->assertTrue($this->database
      ->schema()
      ->fieldExists('shortcut_field_data', 'parent'));
    $this->assertTrue($this->database
      ->schema()
      ->fieldExists('shortcut_field_data', 'depth'));

    $this->container->get('module_installer')->uninstall(['shortcut_menu']);

    $this->assertFalse($this->database
      ->schema()
      ->fieldExists('shortcut_field_data', 'parent'));
    $this->assertFalse($this->database
      ->schema()
      ->fieldExists('shortcut_field_data', 'depth'));
  }

  /**
   * @covers ::buildForm
   * @covers ::save
   * @covers ::sortLinkWeights
   * @covers ::getRootWeight
   */
  public function testShortcutForm() {
    $form_state = new FormState();

    $customize_form = $this->entityTypeManager->getFormObject('shortcut_set', 'customize');
    $this->assertTrue($customize_form instanceof ShortcutMenuSetCustomize);

    $customize_form->setEntity($this->shortcutSet);

    $form = $customize_form->buildForm([], $form_state);

    $submit_values = [
      'shortcuts' => [
        'links' => [
          $this->parentShortcut->id() => [
            'name' => ['parent' => 0, 'depth' => 0],
            'weight' => 0,
          ],
          $this->shortcut->id() => [
            'name' => ['parent' => $this->parentShortcut->id(), 'depth' => 1],
            'weight' => 0,
          ],
        ],
      ],
    ];

    $form_state->setValues($submit_values);
    $customize_form->save($form, $form_state);

    $parent_query = $this->database->select('shortcut_field_data', 's')
      ->fields('s', ['parent'])
      ->condition('id', $this->shortcut->id())
      ->execute()
      ->fetchField();

    $this->assertEquals($this->parentShortcut->id(), $parent_query);
  }

}
