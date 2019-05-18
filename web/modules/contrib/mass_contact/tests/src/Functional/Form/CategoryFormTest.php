<?php

namespace Drupal\Tests\mass_contact\Functional\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\mass_contact\Entity\MassContactCategory;
use Drupal\Tests\mass_contact\Functional\MassContactTestBase;

/**
 * Tests the mass contact config entity add/edit form.
 *
 * @group mass_contact
 *
 * @coversDefaultClass \Drupal\mass_contact\Form\CategoryForm
 */
class CategoryFormTest extends MassContactTestBase {

  /**
   * Tests the form.
   */
  public function testForm() {
    $this->drupalLogin($this->admin);

    // Test navigation links are in place.
    $this->drupalGet('/admin/config');
    $this->assertSession()->linkExists(t('Mass Contact'));
    $this->clickLink(t('Mass Contact'));
    $this->assertSession()->addressEquals('/admin/config/mass-contact');

    $this->assertSession()->linkExists(t('Categories'));
    $this->assertSession()
      ->linkByHrefExists('/admin/config/mass-contact/settings');
    $this->clickLink(t('Categories'));
    $this->assertSession()
      ->addressEquals('/admin/config/mass-contact/category');
    $this->assertSession()->linkExists(t('Add category'));
    $this->clickLink(t('Add category'));
    $this->assertSession()
      ->addressEquals('/admin/config/mass-contact/category/add');

    // Create a category via the UI.
    $edit = [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'selected' => TRUE,
      'recipients[role][categories][' . $this->roles[3]->id() . ']' => TRUE,
      'recipients[role][categories][' . $this->roles[5]->id() . ']' => TRUE,
      'recipients[role][conjunction]' => 'AND',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    /** @var \Drupal\mass_contact\Entity\MassContactCategoryInterface $category */
    $category = MassContactCategory::load($edit['id']);
    $this->assertEquals($edit['label'], $category->label());
    $this->assertTrue($category->getSelected());
    $expected = [
      'categories' => [
        $this->roles[3]->id(),
        $this->roles[5]->id(),
      ],
      'conjunction' => 'AND',
    ];
    $this->assertEquals($expected, $category->getRecipients()['role']);

    // Test edit form.
    $this->drupalGet($category->toUrl('edit-form'));
    $edit['selected'] = FALSE;
    $edit['label'] = $this->randomString();
    $edit['recipients[role][categories][' . $this->roles[4]->id() . ']'] = TRUE;
    $edit['recipients[role][conjunction]'] = 'OR';
    $this->drupalPostForm(NULL, $edit, t('Save'));

    \Drupal::entityTypeManager()
      ->getStorage('mass_contact_category')
      ->resetCache();
    /** @var \Drupal\mass_contact\Entity\MassContactCategoryInterface $category */
    $category = MassContactCategory::load($edit['id']);
    $this->assertEquals($edit['label'], $category->label());
    $this->assertFalse($category->getSelected());
    $expected = [
      'categories' => [
        $this->roles[3]->id(),
        $this->roles[4]->id(),
        $this->roles[5]->id(),
      ],
      'conjunction' => 'OR',
    ];
    $this->assertEquals($expected, $category->getRecipients()['role']);

    // Test that when no recipients are selected, a validation error is thrown.
    $this->drupalGet('/admin/config/mass-contact/category/add');
    // Create a category via the UI.
    $edit = [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()
      ->pageTextContains('At least one recipient is required.');
  }

}
