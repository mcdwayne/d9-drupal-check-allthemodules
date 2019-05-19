<?php

/**
 * @file
 * Contains \Drupal\station_playlist\Tests\StationPlaylistUITest.
 */

namespace Drupal\station_playlist\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the UI for station playlists.
 *
 * @group station_playlist
 */
class StationPlaylistUITest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['station_playlist'];

  /**
   * @var \Drupal\node\NodeInterface
   */
  protected $programNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    $this->programNode = Node::create(['type' => 'station_program', 'title' => 'A Program']);
    $this->programNode->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testCreate() {
    $this->drupalGet('node/add');
    $this->clickLink('Playlist');

    $title = 'A Playlist';
    $edit = [
      'title[0][value]' => $title,
      'station_playlist_program[0][target_id]' => $this->programNode->label(),
      'station_playlist_track[0][station_playlist_track_album][0][value]' => 'An Album',
      'station_playlist_track[0][station_playlist_track_artist][0][value]' => 'An Artist',
      'station_playlist_track[0][station_playlist_track_title][0][value]' => 'A Title',
      'station_playlist_track[0][station_playlist_track_label][0][value]' => 'A Label',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and publish');

    $this->assertRaw(new FormattableMarkup('@type %title has been created.', ['@type' => 'Playlist', '%title' => $title]));
    $this->assertText(new FormattableMarkup('Submitted by @username', ['@username' => $this->loggedInUser->getAccountName()]));
    // Check for tracks.
    $this->assertText('An Album');
    $this->assertText('An Artist');
    $this->assertText('A Title');
    $this->assertText('A Label');

    $this->assertLink('A Program');
    $this->assertLink('View program');
  }

  /**
   * {@inheritdoc}
   */
  public function testCreateFromProgram() {
    $this->drupalGet($this->programNode->toUrl());
    $this->clickLink('Add new playlist');
    $title = 'A Playlist';
    $edit = [
      'title[0][value]' => $title,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save and publish');
    $this->assertRaw(new FormattableMarkup('@type %title has been created.', ['@type' => 'Playlist', '%title' => $title]));
  }

}
