<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;
use Drupal\user\Entity\User;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_if_author",
 *   label = @Translation("Hide if content from author"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideIfAuthor extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    if (isset($settings['settings']['author'])) {
      $user = User::load($settings['settings']['author']);
    }
    else {
      $user = NULL;
    }
    $config = \Drupal::config('user.settings');
    $form['author'] = array(
      '#title' => t('Authored by'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
      '#description' => t('Leave blank for %anonymous.', ['%anonymous' => $config->get('anonymous')]),
      '#default_value' => $user,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    $entity = $this->getEntity($build);
    if (!$entity) {
      $build[$field]['#access'] = FALSE;
      return;
    }
    if ((!$settings['settings']['author'] && $entity->getOwnerId() == 0) || ($entity->getOwnerId() == $settings['settings']['author'])) {
      $build[$field]['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    if (isset($settings['settings']['author'])) {
      $user = User::load($settings['settings']['author']);
      $user = $user->getUsername();
    }
    else {
      $config = \Drupal::config('user.settings');
      $user = $config->get('anonymous');
    }
    return t("Condition: %condition (%settings)", [
      "%condition" => t('Hide if content from author'),
      '%settings' => $user,
    ]);
  }

}
