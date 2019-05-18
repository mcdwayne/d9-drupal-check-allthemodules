<?php

namespace Drupal\sendinblue\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\sendinblue\SendinblueManager;

/**
 * Provides a list controller for content_entity_example_contact entity.
 *
 * @ingroup content_entity_example
 */
class SignupListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['mode'] = t('Display Mode(s)');
    $header['mcLists'] = t('SendinBlue Lists');
    $header['access'] = t('Page Access');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\sendinblue\Entity\Signup */
    $signup = $entity;
    $settings = $signup->settings->first()->getValue();

    $modes = NULL;
    $block_only = FALSE;
    switch ($signup->mode->value) {
      case SENDINBLUE_SIGNUP_BLOCK:
        $modes = Link::fromTextAndUrl(t('Block'), Url::fromUri('internal:/admin/structure/block'))
          ->toString();
        $block_only = TRUE;
        break;

      case SENDINBLUE_SIGNUP_PAGE:
        $modes = Link::fromTextAndUrl(t('Page'), Url::fromUri('internal:/' . $settings['path']))
          ->toString();
        break;

      case SENDINBLUE_SIGNUP_BOTH:
        $modes = Link::fromTextAndUrl(t('Block'), Url::fromUri('internal:/admin/structure/block'))
          ->toString();
        $modes .= ' and ';
        $modes .= Link::fromTextAndUrl(t('Page'), Url::fromUri('internal:/' . $settings['path']))
          ->toString();

        break;
    }

    $list_name = SendinblueManager::getListNameById($settings['subscription']['settings']['list']);
    $list_labels = Link::fromTextAndUrl($list_name, Url::fromUri('https://my.sendinblue.com/users/list/id/?utm_source=drupal_plugin&utm_medium=plugin&utm_campaign=module_link' . $settings['subscription']['settings']['list']));

    if ($block_only) {
      $access = t('N/A - this form only exists as a block');
    }
    else {
      $all_roles_allowed = user_roles(FALSE, 'sendinblue_signup_all_forms' . $signup->name->value);
      $page_roles_allowed = user_roles(FALSE, 'sendinblue_signup_form_' . $signup->name->value);
      $roles_allowed = array_merge($all_roles_allowed, $page_roles_allowed);

      /** @var \Drupal\user\Entity\Role $role_object */
      foreach ($roles_allowed as $id => $role_object) {
        $roles_allowed[$id] = $role_object->label();
      }

      $access = implode(', ', $roles_allowed);
      $actions[] = Link::fromTextAndUrl(t('Permissions'), Url::fromUri('internal:/admin/people/permissions', ['fragment' => 'edit-sendinblue-signup-all-forms']));
    }

    $row['name'] = $signup->title->value;
    $row['mode'] = Markup::create($modes);
    $row['mcLists'] = $list_labels;
    $row['access'] = $access;

    return $row + parent::buildRow($entity);
  }

}
