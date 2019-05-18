<?php /**
 * @file
 * Contains \Drupal\jplayer\Plugin\Field\FieldFormatter\JplayerPlayer.
 */

namespace Drupal\jplayer\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Annotation;
use Drupal\Core\Annotation\Translation;


/**
 * @FieldFormatter(
 *  id = "jplayer_player",
 *  label = @Translation("jPlayer - Player"),
 *  description = @Translation("Display file fields as an HTML5-compatible with Flash-fallback media player."),
 *  field_types = {
 *    "file",
 *    "text",
 *    "link_field"
 *  }
 * )
 */
class JplayerPlayer extends FileFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @FIXME
   * Move all logic relating to the jplayer_player formatter into this
   * class. For more information, see:
   *
   * https://www.drupal.org/node/1805846
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FormatterInterface.php/interface/FormatterInterface/8
   * https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FormatterBase.php/class/FormatterBase/8
   */

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['audio_player'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Select Player'),
      '#default_value' => $this->getSetting('audio_player'),
      '#options' => $plugins,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {

  }

}
