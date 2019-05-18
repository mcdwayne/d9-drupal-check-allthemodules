<?php

namespace Drupal\ext_redirect\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\ext_redirect\RedirectRuleHelper;

/**
 * Form controller for Redirect Rule edit forms.
 *
 * @ingroup ext_redirect
 */
class RedirectRuleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\ext_redirect\Entity\RedirectRule */
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    /** @var RedirectRuleHelper $helper */
    $helper = \Drupal::service('ext_redirect.helper');
    $source_site = $form_state->getValue('source_site');
    if (!empty($source_site[0]['value'])) {
      $source_site = $source_site[0]['value'];
    }

    // Get the source path.
    $source_paths = $form_state->getValue('source_path');
    if (isset($source_paths[0]['source_path'])) {
      // Extract lines.
      $source_paths = preg_split('/\n|\r\n?/', $source_paths[0]['source_path']);
      // Remove blank lines.
      $source_paths = array_filter($source_paths, 'trim');
      // Write the cleaned value back to the form state.
      $form_state->setValue('source_path', array(array('source_path' => implode("\n", $source_paths))));
      // For each line check if there is an existing rule with same source_site
      // and source_path combination.
      foreach ($source_paths as $source_path) {
        // Check if the source path starts with a slash.
        if (trim($source_path) !== '*' && strpos($source_path, '/') !== 0) {
          $form_state->setErrorByName('source_path', $this->t('Every source path line has to start with a slash "/" or the wildcard character "*"!'));
        }
        // Get all matching rules.
        $matching_redirect_rules = $helper->getRedirectRulesBySourceSiteAndPath($source_site, $source_path);
        if (!is_array($matching_redirect_rules)) {
          continue;
        }
        $matching_redirect_ids = array_keys($matching_redirect_rules);
        $entity_id = $entity->id();
        // If we have matching rules, and the rule is new or the matching rules
        // diffed with this rule are not empty.
        $diff = array_diff($matching_redirect_ids, [$entity_id]);
        if (!empty($diff)) {
          $existing_entity = \Drupal::entityTypeManager()->getStorage('redirect_rule')->load(reset($diff));
          // Create an edit url, so the editor can directly switch to the
          // existing rule.
          $edit_url = Link::fromTextAndUrl('Edit rule ' . $existing_entity->id(), $existing_entity->toUrl('edit-form'))->toString();
          $form_state->setErrorByName('source_path', $this->t('Another rule with same source already exists for %source_site. Edit existing rule instead: @url', array('%source_site' => $source_site, '@url' => $edit_url)));
        }
      }
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Redirect Rule.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Redirect Rule.', [
          '%label' => $entity->label(),
        ]));
    }
    // Invalidate all cache entries which are tagged with "ext_redirect".
    Cache::invalidateTags(array('ext_redirect'));
    // Redirect to the overview page.
    $form_state->setRedirect('entity.redirect_rule.collection');
  }

}
