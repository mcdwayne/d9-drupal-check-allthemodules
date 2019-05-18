<?php

namespace Drupal\Tests\mm_media\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\editor\Entity\Editor;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\media\Entity\MediaType;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\media\MediaTypeInterface;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Tests access permissions on media nodes.
 *
 * @group media
 */
class DownloadTest extends KernelTestBase {

  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }
  use UserCreationTrait {
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'image',
    'user',
    'field',
    'system',
    'text',
    'filter',
    'block',
    'node',
    'file',
    'media',
    'monster_menus',
    'mm_media',
    'mm_media_fileref_type',
    'editor',
    'editor_test',
  ];

  /**
   * UID of the test media's and node's owner.
   *
   * @var int
   */
  private $ownerUid = 9999;

  private $users = [];

  private $cleanupFilenames = [];

  const ACCESS_ALLOWED = 'allowed';
  const ACCESS_DENIED = 'denied';
  const ACCESS_UNSET = 'unset';
  const NOT_FOUND = 'not found';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setInstallProfile('standard');
    $this->installSchema('file', 'file_usage');
    $this->installSchema('system', 'sequences');
    // We need almost all of MM's tables because we are calling its hook_install().
    $this->installSchema('monster_menus', ['mm_vgroup_query', 'mm_tree_parents', 'mm_node_write', 'mm_node_info', 'mm_node2tree', 'mm_recycle', 'mm_node_schedule', 'mm_node_reorder', 'mm_tree_flags', 'mm_tree_block', 'mm_group', 'mm_tree_access', 'mm_cascaded_settings', 'mm_archive', 'mm_virtual_group']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('mm_tree');
    $this->installEntitySchema('media');
    $this->installEntitySchema('node');
    $this->installEntitySchema('block');
    $this->installConfig(['field', 'system', 'image', 'file', 'text', 'filter', 'node', 'media', 'mm_media', 'mm_media_fileref_type']);
    $this->container->get('module_installer')->install(['mm_media_fileref_type']);

    // The tests we are doing require a real file on disk, so we can't use vfs.

    // Add file_private_path setting.
    $request = Request::create('/');
    $site_path = DrupalKernel::findSitePath($request);
    $privatePath = $site_path . '/private';
    $this->setSetting('file_private_path', $privatePath);

    // Ensure that the private files directory exists.
    $fs = $this->container->get('file_system');
    $fs->mkdir($privatePath, NULL, TRUE);

    $this->currentUser = $this->container->get('current_user');

    // Call MM's hook_install().
    \Drupal::moduleHandler()->invoke('monster_menus', 'install');

    // Add text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Set up text editor.
    $editor = Editor::create([
      'format' => 'filtered_html',
      'editor' => 'unicorn',
    ]);
    $editor->save();

    // Create a node type for testing.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();
    node_add_body_field($type);
  }

  /**
   * @inheritDoc
   */
  protected function tearDown() {
    $privatePath = Settings::get('file_private_path');
    foreach ($this->cleanupFilenames as $fn) {
      unlink($privatePath . '/' . $fn);
    }
    rmdir($privatePath);
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * Tests private file download access.
   */
  public function testPrivateDownloadAccess() {
    $testDownload = function ($target, $scheme = 'private') {
      $uri = $scheme . '://' . $target;

      if (\Drupal::service('file_system')->validScheme($scheme) && file_exists($uri)) {
        try {
          $headers = \Drupal::moduleHandler()
              ->invoke('mm_media', 'file_download', [$uri]) ?? [];

          if (is_array($headers)) {
            if (count($headers)) {
              return $this::ACCESS_ALLOWED;
            }
          }
          else if ($headers === -1) {
            return $this::ACCESS_DENIED;
          }

          return $this::ACCESS_UNSET;
        }
        catch (HttpException $e) {
          if ($e->getStatusCode() == 304) {
            // Not Modified
            return $this::ACCESS_ALLOWED;
          }
        }

        return $this::ACCESS_DENIED;
      }

      return $this::NOT_FOUND;
    };

    $setupUserWithRole = function($access_modes, $can) {
      $label = join('+', $access_modes);
      $role = Role::create(['id' => $this->randomMachineName(8), 'label' => "$label Role"]);
      foreach ($access_modes as $access_mode) {
        $role->grantPermission($access_mode);
      }
      $role->save();
      $user = User::create(['name' => "Can $label", 'status' => 1, 'roles' => [$role->id()]]);
      $user->save();
      $this->users[$label] = ['user' => $user, 'can' => $can];
    };

    $mediaType = $this->createMediaType('file');
    $setupNodeWithMedia = function($filename, $catlist = []) use ($mediaType) {
      $media = $this->generateMedia($filename, $mediaType);
      return $this->createNode([
        'type' => 'media_test',
        'uid' => $this->ownerUid,
        'field_media_field' => [['target_id' => $media->id()]],
        'mm_catlist' => $catlist,
      ]);
    };

    $setupNodeBodyWithMedia = function($filename, $catlist = []) {
      $file = $this->generateFile($filename);
      $body_value = '<img src="test.jpg" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" />';
      $body = [[
        'value' => $body_value,
        'format' => 'filtered_html',
      ]];
      $this->createNode([
        'type' => 'page',
        'title' => 'test',
        'uid' => $this->ownerUid,
        'body' => $body,
        'mm_catlist' => $catlist,
      ]);
    };

    $user = User::create(['name' => 'Media owner', 'status' => 1, 'uid' => $this->ownerUid]);
    $user->save();
    $this->users = [
      'anonymous'   => [
        'user' => User::getAnonymousUser(),
        'can' => [
          'no usage' => $this::ACCESS_UNSET,
          'no longer used' => $this::ACCESS_UNSET,
          'no longer used temp' => $this::ACCESS_DENIED,
          'no node' => $this::ACCESS_DENIED,
          'on orphan node' => $this::ACCESS_DENIED,
          'on public node' => $this::ACCESS_ALLOWED,
          'on unreadable node' => $this::ACCESS_DENIED,
          'same node both places' => $this::ACCESS_ALLOWED,
          'same file both places' => $this::ACCESS_ALLOWED,
          'public and in bin' => $this::ACCESS_ALLOWED,
          'same node, public in bin' => $this::ACCESS_DENIED,
          'in bin' => $this::ACCESS_DENIED,
          'in body of public node' => $this::ACCESS_ALLOWED,
          'in body of unreadable node' => $this::ACCESS_DENIED,
        ]],
      'media owner' => [
        'user' => $user,
        'can' => [
          'no usage' => $this::ACCESS_ALLOWED,
          'no longer used' => $this::ACCESS_ALLOWED,
          'no longer used temp' => $this::ACCESS_ALLOWED,
          'no node' => $this::ACCESS_ALLOWED,
          'on orphan node' => $this::ACCESS_ALLOWED,
          'on public node' => $this::ACCESS_ALLOWED,
          'on unreadable node' => $this::ACCESS_ALLOWED,
          'same node both places' => $this::ACCESS_ALLOWED,
          'same file both places' => $this::ACCESS_ALLOWED,
          'public and in bin' => $this::ACCESS_ALLOWED,
          'same node, public in bin' => $this::ACCESS_ALLOWED,
          'in bin' => $this::ACCESS_ALLOWED,
          'in body of public node' => $this::ACCESS_ALLOWED,
          'in body of unreadable node' => $this::ACCESS_ALLOWED,
        ]],
    ];
    $this->drupalCreateAdminRole('administrator');
    Role::create([
      'id' => RoleInterface::ANONYMOUS_ID,
      'label' => 'Anonymous user',
    ])->save();
    Role::create([
      'id' => RoleInterface::AUTHENTICATED_ID,
      'label' => 'Authenticated user',
    ])->save();
    $setupUserWithRole(['administer all menus'], [
      'no usage' => $this::ACCESS_UNSET,
      'no longer used' => $this::ACCESS_UNSET,
      'no longer used temp' => $this::ACCESS_DENIED,
      'no node' => $this::ACCESS_DENIED,
      'on orphan node' => $this::ACCESS_ALLOWED,
      'on public node' => $this::ACCESS_ALLOWED,
      'on unreadable node' => $this::ACCESS_ALLOWED,
      'same node both places' => $this::ACCESS_ALLOWED,
      'same file both places' => $this::ACCESS_ALLOWED,
      'public and in bin' => $this::ACCESS_ALLOWED,
      'same node, public in bin' => $this::ACCESS_ALLOWED,
      'in bin' => $this::ACCESS_ALLOWED,
      'in body of public node' => $this::ACCESS_ALLOWED,
      'in body of unreadable node' => $this::ACCESS_ALLOWED,
    ]);
    $setupUserWithRole(['bypass node access'], [
      'no usage' => $this::ACCESS_UNSET,
      'no longer used' => $this::ACCESS_UNSET,
      'no longer used temp' => $this::ACCESS_DENIED,
      'no node' => $this::ACCESS_DENIED,
      'on orphan node' => $this::ACCESS_DENIED,
      'on public node' => $this::ACCESS_ALLOWED,
      'on unreadable node' => $this::ACCESS_DENIED,
      'same node both places' => $this::ACCESS_ALLOWED,
      'same file both places' => $this::ACCESS_ALLOWED,
      'public and in bin' => $this::ACCESS_ALLOWED,
      'same node, public in bin' => $this::ACCESS_ALLOWED,
      'in bin' => $this::ACCESS_ALLOWED,
      'in body of public node' => $this::ACCESS_ALLOWED,
      'in body of unreadable node' => $this::ACCESS_DENIED,
    ]);
    $user = User::create(['name' => 'No roles', 'roles' => [], 'status' => 1]);
    $user->save();
    $this->users['no roles'] = ['user' => $user, 'can' => [
      'no usage' => $this::ACCESS_UNSET,
      'no longer used' => $this::ACCESS_UNSET,
      'no longer used temp' => $this::ACCESS_DENIED,
      'no node' => $this::ACCESS_DENIED,
      'on orphan node' => $this::ACCESS_DENIED,
      'on public node' => $this::ACCESS_ALLOWED,
      'on unreadable node' => $this::ACCESS_DENIED,
      'same node both places' => $this::ACCESS_ALLOWED,
      'same file both places' => $this::ACCESS_ALLOWED,
      'public and in bin' => $this::ACCESS_ALLOWED,
      'same node, public in bin' => $this::ACCESS_DENIED,
      'in bin' => $this::ACCESS_DENIED,
      'in body of public node' => $this::ACCESS_ALLOWED,
      'in body of unreadable node' => $this::ACCESS_DENIED,
    ]];
    $user = User::create(['name' => 'admin', 'roles' => ['administrator'], 'status' => 1]);
    $user->save();
    $this->users['admin'] = ['user' => $user, 'can' => [
      'no usage' => $this::ACCESS_UNSET,
      'no longer used' => $this::ACCESS_UNSET,
      'no longer used temp' => $this::ACCESS_DENIED,
      'no node' => $this::ACCESS_DENIED,
      'on orphan node' => $this::ACCESS_ALLOWED,
      'on public node' => $this::ACCESS_ALLOWED,
      'on unreadable node' => $this::ACCESS_ALLOWED,
      'same node both places' => $this::ACCESS_ALLOWED,
      'same file both places' => $this::ACCESS_ALLOWED,
      'public and in bin' => $this::ACCESS_ALLOWED,
      'same node, public in bin' => $this::ACCESS_ALLOWED,
      'in bin' => $this::ACCESS_ALLOWED,
      'in body of public node' => $this::ACCESS_ALLOWED,
      'in body of unreadable node' => $this::ACCESS_ALLOWED,
    ]];

    $this->generateFile($filename['no usage'] = 'no_usage.txt');

    $this->generateMedia($filename['no longer used'] = 'no_longer_used.txt', $mediaType)->delete();

    // Mark unused managed files as temporary.
    $this->config('file.settings')
      ->set('make_unused_managed_files_temporary', TRUE)
      ->save();
    $this->generateMedia($filename['no longer used temp'] = 'no_longer_used_temp.txt', $mediaType)->delete();

    $this->generateMedia($filename['no node'] = 'no_node.txt', $mediaType);

    $setupNodeWithMedia($filename['on orphan node'] = 'on_orphan_node.txt');

    $publicPage = MMTree::create(['parent' => mm_home_mmtid(), 'name' => 'Public', 'alias' => 'public', 'default_mode' => Constants::MM_PERMS_READ]);
    $publicPage->save();
    $publicCatlist = [$publicPage->id() => $publicPage->label()];
    $setupNodeWithMedia($filename['on public node'] = 'on_public_node.txt', $publicCatlist);

    $unreadablePage = MMTree::create(['parent' => mm_home_mmtid(), 'name' => 'Unreadable', 'alias' => 'unreadable']);
    $unreadablePage->save();
    $unreadableCatlist = [$unreadablePage->id() => $unreadablePage->label()];
    $setupNodeWithMedia($filename['on unreadable node'] = 'on_unreadable_node.txt', $unreadableCatlist);

    $setupNodeWithMedia($filename['same node both places'] = 'same_node_both_places.txt', $publicCatlist + $unreadableCatlist);

    $node = $setupNodeWithMedia($filename['same file both places'] = 'same_file_both_places.txt', $publicCatlist);
    $this->createNode([
      'type' => 'media_test',
      'uid' => $this->ownerUid,
      'field_media_field' => $node->field_media_field,
      'mm_catlist' => $unreadableCatlist,
    ]);

    $node = $setupNodeWithMedia($filename['public and in bin'] = 'public_and_in_bin.txt', $publicCatlist);
    mm_content_move_to_bin(NULL, $this->createNode([
      'type' => 'media_test',
      'uid' => $this->ownerUid,
      'field_media_field' => $node->field_media_field,
      'mm_catlist' => $publicCatlist,
    ])->id());

    $node = $setupNodeWithMedia($filename['same node, public in bin'] = 'same_node_public_binned.txt', $publicCatlist + $unreadableCatlist);
    mm_content_move_to_bin(NULL, [$node->id() => [$publicPage->id()]]);

    $node = $setupNodeWithMedia($filename['in bin'] = 'in_bin.txt', $publicCatlist);
    mm_content_move_to_bin(NULL, $node->id());

    $setupNodeBodyWithMedia($filename['in body of public node'] = 'in_public_body.txt', $publicCatlist);
    $setupNodeBodyWithMedia($filename['in body of unreadable node'] = 'in_unreadable_body.txt', $unreadableCatlist);

    $mm_media_settings = $this->config('mm_media.settings');
    foreach ($this->users as $user_data) {
      \Drupal::currentUser()->setAccount($user_data['user']);
      foreach ([FALSE, TRUE] as $cache_public_media) {
        $mm_media_settings->set('cache_public_media', $cache_public_media)->save();
        foreach ($user_data['can'] as $mode => $can) {
          $name = $user_data['user']->getDisplayName() ?? 'anonymous';
          $message = sprintf('mode = [%s], user = [%s (%d)], cache_public_media = %d', $mode, $name, $user_data['user']->id(), $cache_public_media);
          $this->assertEquals($can, $testDownload($filename[$mode]), $message);
        }
      }
    }
  }

  /**
   * Create a media type for a source plugin.
   *
   * @param string $media_source_name
   *   The name of the media source.
   *
   * @return \Drupal\media\MediaTypeInterface
   *   A media type.
   */
  protected function createMediaType($media_source_name) {
    $id = strtolower($this->randomMachineName());
    $media_type = MediaType::create([
      'id' => __FUNCTION__ . $id,
      'label' => $id,
      'source' => $media_source_name,
      'new_revision' => FALSE,
    ]);
    $media_type->save();
    $source_field = $media_type->getSource()->createSourceField($media_type);
    // The media type form creates a source field if it does not exist yet. The
    // same must be done in a kernel test, since it does not use that form.
    // @see \Drupal\media\MediaTypeForm::save()
    $source_field->getFieldStorageDefinition()->save();
    // The source field storage has been created, now the field can be saved.
    $source_field->save();
    $source_configuration = $media_type->getSource()->getConfiguration();
    $source_configuration['source_field'] = $source_field->getName();
    $media_type->set('source_configuration', $source_configuration)->save();

    return $media_type;
  }

  /**
   * Helper to generate file entity.
   *
   * @param string $filename
   *   String filename with extension.
   *
   * @return File
   *   A file entity.
   */
  protected function generateFile($filename) {
    $uri = 'private://' . $filename;
    if ($fp = fopen($uri, 'c+')) {
      $this->cleanupFilenames[] = $filename;
      fwrite($fp, str_repeat('a', 3000));
      fclose($fp);
    }
    else {
      throw new \Exception("Can't create $uri");
    }

    $file = File::create([
      'uri' => $uri,
      'uid' => $this->ownerUid,
    ]);
    $file->setPermanent();
    $file->save();

    return $file;
  }

  /**
   * Helper to generate media entity.
   *
   * @param string $filename
   *   String filename with extension.
   * @param MediaTypeInterface $media_type
   *   The the media type.
   *
   * @return Media
   *   A media entity.
   */
  protected function generateMedia($filename, MediaTypeInterface $media_type) {
    $file = $this->generateFile($filename);
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Mr. Jones',
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
    ]);
    $media->save();
    return $media;
  }

}
