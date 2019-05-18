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
 * Plugin implementation of the 'sticky' actions.
 *
 * @CronpubAction(
 *   id = "sticky",
 *   label = @Translation("Set sticky/Unset sticky"),
 *   description = @Translation("Set/unset sticky property of the parent entity of this field."),
 *   start = {
 *     "label" = @Translation("Set sticky"),
 *     "description" = @Translation("Date and time at which the content will be set on sticky on top of lists.")
 *   },
 *   end = {
 *     "label" = @Translation("Unset sticky"),
 *     "description" = @Translation("Date and time at which the content will be unset from top of lists.")
 *   },
 *   permission = "administer nodes",
 * )
 */
class CronpubSticky implements CronpubActionInterface {

  /**
   * {@inheritdoc}
   */
  public function startAction(ContentEntityBase $content_entity) {
    return $this->setSticky($content_entity, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function endAction(ContentEntityBase $content_entity) {
    return $this->setSticky($content_entity, FALSE);
  }

  /**
   * Set sticky property.
   *
   * @param ContentEntityBase $content_entity
   *   The content entity on what top to set sticky
   * @param bool $sticky
   *   The sticky value.
   *
   * @return mixed
   */
  private function setSticky(ContentEntityBase $content_entity, $sticky) {
    try {
      if ($content_entity instanceof NodeInterface) {
        $content_entity->setSticky($sticky);
      }
      elseif ($content_entity->hasField('sticky')) {
          // May be there are other entities with a sticky property so we can try to set it.
          $content_entity->get('sticky')->setValue([['value' => $sticky]]);
      }
      $time = $content_entity->save();
      $message = new TranslatableMarkup('%action the %link on %date.', [
        '%action' => ($sticky) ? t('Set sticky') : t('Unset sticky'),
        '%link' => $content_entity->getEntityTypeId() . '/' . $content_entity->id(),
        '%date' => \Drupal::service('date.formatter')->format($time),
      ]);
      \Drupal::logger('Cronpub')->notice($message);
    } catch (\Exception $e) {
      \Drupal::logger('Cronpub')->error($e->getMessage());
    }
  }

}