<?php

/**
 * @file
 *	Displays the Personal Notes block.
 */
namespace Drupal\personal_notes\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Creates a 'personal_notes' Block
 * @Block(
 *   id = "block_personal_notesblk",
 *   admin_label = @Translation("personal_notesblock"),
 * )
 */
class PersonalNotesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */

  public function build() {
    if (!\Drupal::currentUser()->isAnonymous()) {				//	user must be logged on to have personal notes
      $results = _personal_notes_fetch_content_db();			//	get their notes
      $notedata = array();
      $notes = array();
      foreach ($results as $result) {
        foreach ($result as $field => $value) {
          if ( preg_match ( '/^(title)|(note)|(notenum)$/', $field ) ) {
            $notedata[$field] = $value;							//	save note's number, title and message
          }
        }
        $notes
        [
          $notedata['title'] .
          str_pad
          (
            $notedata['notenum'],
            5,
            '0',
            STR_PAD_LEFT
          )														//	gives a constant length index display in the title
        ] =														//	serialize the data
          $notedata['note'];									//	store in a twig template friendly array
      }
      $build = array (
        '#theme' => 'block--personal_notes',
        '#notes' => $notes,
        '#attached' => array (									//	attach the stylesheet library
          'library' => array (
            'personal_notes/personal_notes',
          )
        )
      );
      return $build;
    }															//	end if user is logged in
  }																//	end build method

}
