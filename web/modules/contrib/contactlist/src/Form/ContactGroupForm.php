<?php

namespace Drupal\contactlist\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ContactGroupForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\contactlist\Entity\ContactGroupInterface $group */
    $group = $this->entity;
    if (!$group->isNew()) {
      $form['group'] = [
        '#type' => 'details',
        '#title' => $this->t('Contacts in group "@group"', ['@group' => $group->getName()]),
        '#open' => TRUE,
      ];

      $form['group']['contacts'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Name'),
          $this->t('Telephone'),
          $this->t('Email'),
          $this->t('Remove'),
        ],
        '#empty' => $this->t('No contacts in this group.'),
        '#prefix'  => '<div class="group-contacts-wrapper">',
        '#suffix'  => '</div>',
      ];

      foreach ($group->getContacts() as $contact) {
        $form['group']['contacts'][$contact->id()] = [
          'name' => ['#markup' => $contact->getContactName()],
          'telephone' => ['#markup' => $contact->getPhoneNumber()],
          'email' => ['#markup' => $contact->getEmail()],
          'remove' => [
            '#type' => 'checkbox',
            '#checked' => FALSE,
          ],
        ];
      }

    }
    $form['#attached']['library'] = ['contactlist/group-form'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($form_state->getTriggeringElement()['#value']->getUntranslatedString() === 'Save') {
      // Remove the contacts that were selected for removal.
      /** @var \Drupal\contactlist\Entity\ContactListEntryInterface $contact */
      foreach ($this->entity->getContacts() as $contact) {
        $value = $form_state->getValue(['contacts', $contact->id()]);
        if (isset($value) && $value['remove']) {
          $contact->removeGroups([$this->entity])->save();
        }
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $message1 = $this->t('Contact group <b>@group</b> has been saved.', ['@group' => $this->entity->getName()]);
    $message2 = $this->t('Go to <a href=":href">contacts list</a> to add contacts to your group.',
      [':href' => Url::fromRoute('entity.contactlist_entry.collection')->toString()]);

    drupal_set_message($message1);
    drupal_set_message($message2);
    $form_state->setRedirect('entity.contact_group.collection');
  }

}
