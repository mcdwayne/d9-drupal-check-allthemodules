<?php

namespace Drupal\migrate_html_to_paragraphs\Plugin\QueueWorker;

/**
 * A Queue Worker that deletes orphaned Paragraphs on CRON run.
 *
 * @QueueWorker(
 *   id = "migrate_html_to_paragraphs_delete_orphaned_paragraphs",
 *   title = @Translation("Delete orphaned paragraphs"),
 *   cron = {"time" = 10}
 * )
 */
class CronParagraphDelete extends ParagraphDeleteBase {

}
