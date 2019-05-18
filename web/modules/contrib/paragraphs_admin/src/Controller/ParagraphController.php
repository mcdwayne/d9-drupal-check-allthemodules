<?php

namespace Drupal\paragraphs_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Controller for paragraphs admin.
 */
class ParagraphController extends ControllerBase {

  /**
   * Deletes paragraph content.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraph to be deleted.
   */
  public function deleteParagraph(ParagraphInterface $paragraph) {
    $pid = $paragraph->id();
    $paragraph->delete();
    drupal_set_message($this->t('Paragraph @pid deleted.', ['@pid' => $pid]));

    // Redirect back to view node page.
    return $this->redirect('view.paragraphs.page_admin_paragraphs');
  }

}
