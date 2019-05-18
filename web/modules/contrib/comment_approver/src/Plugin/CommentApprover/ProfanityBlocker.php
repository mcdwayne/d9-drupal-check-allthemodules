<?php

namespace Drupal\comment_approver\Plugin\CommentApprover;

use Drupal\comment_approver\Plugin\CommentApproverBase;

/**
 * Provides a plugin for testing profanity in comments.
 *
 * @CommentApprover(
 *   id = "profanity_blocker",
 *   label = @Translation("Profanity Blocker"),
 *   description = @Translation("Blocks a comment if it contains profanity words")
 * )
 */
class ProfanityBlocker extends CommentApproverBase {

  /**
   * {@inheritdoc}
   */
  public function isCommentFine($comment) {
    $commentFine = TRUE;
    $test_fields = $this->getTextData($comment);
    $config = $this->getConfiguration();
    $testWords = explode(',', $config['testWords']);
    foreach ($test_fields as $name => $value) {
      if ($this->findWords($testWords, $value)) {
        $commentFine = FALSE;
        break;
      }
    }
    return $commentFine;
  }

  /**
   * A helper function to check if any words is present in a text.
   *
   * @param array $words
   *   An array of words to find in a text.
   * @param string $text
   *   A text string to search for words.
   *
   * @return bool
   *   Return TRUE if any work is found otherwise FALSE
   */
  public function findWords(array $words, string $text) {
    foreach ($words as $word) {
      if (strpos($text, trim($word)) === FALSE) {
        // Word is not present do nothing.
      }
      else {
        // Word is present return true.
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $config = $this->getConfiguration();
    $myform['testWords'] = [
      '#type' => 'textfield',
      '#title' => t('Add a comma seperated list of words to test'),
      '#default_value' => $config['testWords'],
    ];
    return $myform;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['testWords' => 'shit,hell'];
  }

}
