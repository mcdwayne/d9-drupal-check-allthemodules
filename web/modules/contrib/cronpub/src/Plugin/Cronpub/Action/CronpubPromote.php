<?php
/**
 * Created by:
 * User: jfeltkamp
 * Date: 09.03.16
 * Time: 22:24
 */

namespace Drupal\cronpub\Plugin\Cronpub\Action;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\cronpub\Plugin\Cronpub\CronpubActionInterface;
use Drupal\node\NodeInterface;

/**
 * Plugin implementation of the 'promote' actions.
 *
 * @CronpubAction(
 *   id = "promote",
 *   label = @Translation("Set promoted/Unset promoted"),
 *   description = @Translation("Set/unset promote property of the parent node of this field."),
 *   start = {
 *     "label" = @Translation("Set promoted"),
 *     "description" = @Translation("Date and time at which the content will be promoted on the front page.")
 *   },
 *   end = {
 *     "label" = @Translation("Unset promoted"),
 *     "description" = @Translation("Date and time at which the content will be removed from front page.")
 *   },
 *   permission = "administer nodes",
 * )
 */
class CronpubPromote implements CronpubActionInterface {

  /**
   * {@inheritdoc}
   */
  public function startAction(ContentEntityBase $content_entity) {
    return $this->setPromoted($content_entity, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function endAction(ContentEntityBase $content_entity) {
    return $this->setPromoted($content_entity, FALSE);
  }

  /**
   * Set sticky property.
   *
   * @param ContentEntityBase $content_entity
   *   The content entity on what top to set sticky
   * @param bool $promoted
   *   The promoted value.
   *
   * @return mixed
   */
  private function setPromoted(ContentEntityBase $content_entity, $promoted) {
    try {
      if ($content_entity instanceof NodeInterface) {
        $content_entity->setPromoted($promoted);
      }
      elseif ($content_entity->hasField('promoted')) {
        // May be there are other entities with a promoted property so we can try to set it.
        $content_entity->get('promoted')->setValue([['value' => $promoted]]);
      }
      $time = $content_entity->save();
      $message = new TranslatableMarkup('%action the %link on %date.', [
        '%action' => ($promoted) ? t('Set promoted') : t('Unset promoted'),
        '%link' => $content_entity->getEntityTypeId() . '/' . $content_entity->id(),
        '%date' => \Drupal::service('date.formatter')->format($time),
      ]);
      \Drupal::logger('Cronpub')->notice($message);
    } catch (\Exception $e) {
      \Drupal::logger('Cronpub')->error($e->getMessage());
    }
  }

}