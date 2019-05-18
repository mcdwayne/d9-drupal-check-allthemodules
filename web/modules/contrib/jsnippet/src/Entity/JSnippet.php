<?php

namespace Drupal\jsnippet\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\jsnippet\JSnippetInterface;
use Drupal\file\Entity\File;

/**
 * Defines the JSnippet entity.
 *
 * @ConfigEntityType(
 *   id = "jsnippet",
 *   label = @Translation("Snippet"),
 *   handlers = {
 *     "list_builder" = "Drupal\jsnippet\Controller\JSnippetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jsnippet\Form\JSnippetAddForm",
 *       "edit" = "Drupal\jsnippet\Form\JSnippetEditForm",
 *       "delete" = "Drupal\jsnippet\Form\JSnippetDeleteForm",
 *     },
 *        "access" = "Drupal\jsnippet\JSnippetAccessControlHandler",
 *   },
 *   config_prefix = "jsnippet",
 *   admin_permission = "administer snippets",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/snippet/{jsnippet}",
 *     "delete-form" = "/admin/structure/snippet/{jsnippet}/delete",
 *   }
 * )
 */
class JSnippet extends ConfigEntityBase implements JSnippetInterface {

  const SNIPPET_DIR = 'public://snippets/';

  /**
   * The Snippet ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Snippet label.
   *
   * @var string
   */
  public $label;

  /**
   * The type of snippet.
   * 
   * @var string
   */
  public $type;

  /**
   * The Snippet.
   *
   * @var string
   */
  public $snippet;

  /**
   * The Snippet Scope.
   *
   * @var string
   */
  public $scope;

  /**
   * Wrap as a Behavior.
   * 
   * @var string
   */
  public $behavior;

  /**
   * Save the JSnippet Entity and generate JS files to be used as libraries.
   *
   * {@inheritdoc}.
   */
  public function save() {
    // Check snippet and evaluate if it is a URL.
    $external = filter_var($this->snippet, FILTER_VALIDATE_URL);

    // Write snippet to a library file if it's not flagged as external.
    if (!$external) {
      // Fetch an existing file, or build a new file entity.
      if ($files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $this->getUri()])) {
        $file = end($files);
      }
      else {
        $file = File::create([
          'uid' => 1,
          'filename' => $this->getFileName(),
          'uri' => $this->getUri(),
          'status' => 1,
        ]);
        $file->save();
      }

      // Write out the behavior to an appropriate file.
      $directory = $this::SNIPPET_DIR . $this->get('type');
      if ($this->scope == 'footer' && $this->behavior == TRUE) {
        $contents = $this->getBehavior();
      }
      else {
        $this->set('behavior', FALSE);
        $contents = $this->snippet;
      }
      if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
        file_put_contents($file->getFileUri(), $contents);
        $file->save();
      }
    }
    else {
      // Make sure behavior is not checked if we're using an external source.
      $this->set('behavior', FALSE);
      
      // If we're switching this Snippet to be an external reference clean up existing files.
      $uri = $this::SNIPPET_DIR . $this->getFileName();
      if (file_exists($uri)) {
        $this->fileDelete();
      }
    }

    // Save the entity
    $this->trustedData = FALSE;
    $storage = $this->entityTypeManager()->getStorage($this->entityTypeId);
    return $storage->save($this);
  }

  /**
   * Delete the JSnippet Entity and clean up any JS files saved as libraries.
   */
  public function delete() {
    parent::delete();
    $this->fileDelete();
  }

  /**
   * Get the filename for the snippet based on it's machine name.
   */
  public function getFileName() {
    return $this->id() . '.' . $this->get('type');
  }

  /**
   * Get the full URI for the snippet in the public filesystem.
   */
  public function getUri() {
    // Check snippet and evaluate if it is a URL.
    $external = filter_var($this->snippet, FILTER_VALIDATE_URL);
    if (!$external) {
      $uri = $this::SNIPPET_DIR . $this->get('type') . '/' . $this->getFileName();
    }
    else {
      $uri = trim($this->snippet);
    }
    return $uri;
  }

  /**
   * Build a Drupal Behavior from the snippet.
   */
  public function getBehavior() {
    $behavior = '(function ($, Drupal) {' . PHP_EOL;
    $behavior .= 'Drupal.behaviors.' . $this->id() . ' = {' . PHP_EOL;
    $behavior .= 'attach: function (context, settings) {' . PHP_EOL;
    $behavior .= $this->snippet . PHP_EOL;
    $behavior .= '}};' . PHP_EOL;
    $behavior .= '})(jQuery, Drupal);' . PHP_EOL;
    return $behavior;
  }
  
  /**
   * Delete file.
   */
  public function fileDelete() {
    $uri = $this::SNIPPET_DIR . $this->get('type') . '/' . $this->getFileName();
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
    foreach ($files as $file) {
      /** @var \Drupal\file\Entity\File $file */
      $file->delete();
    }
  }

}
