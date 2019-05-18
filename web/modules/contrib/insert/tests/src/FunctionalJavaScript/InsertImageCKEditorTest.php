<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

/**
 * Tests Insert module's functionality on CKEditor instances.
 *
 * @group insert
 */
class InsertImageCKEditorTest extends InsertImageCKEditorTestBase {

  public function testCaption() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0', 'title_field' => '1']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
      'caption' => TRUE,
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $titleField = $page->findField($fieldName . '[0][title]');

    $titleField->setValue('some title');
    $page->findButton('Insert')->click();

    $instancesQuery = 'CKEDITOR.instances["edit-body-0-value"].widgets.instances';

    $hasCaption = $this->getSession()->evaluateScript($instancesQuery . '[0].data.hasCaption');
    $this->assertTrue($hasCaption, 'Verified caption being inserted: ' . (string)$hasCaption);

    // For some reason, $titleField->setValue('') empties the whole editor, so
    // resetting title field needs to be simulated using JavaScript.
    $this->getSession()->executeScript('jQuery(\'[name$="[title]"]\').val("").trigger("input")');
    $hasCaption = $this->getSession()->evaluateScript($instancesQuery . '[1].data.hasCaption');
    $this->assertFalse($hasCaption, 'Verified caption being removed when erasing title field: ' . (string)$hasCaption);

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();
    $hasCaption = $this->getSession()->evaluateScript($instancesQuery . '[0].data.hasCaption');
    $this->assertFalse($hasCaption, 'Verified no caption being inserted when title is empty: ' . (string)$hasCaption);

    $this->getSession()->executeScript('jQuery(\'[name$="[title]"]\').val("some title").trigger("input")');
    $hasCaption = $this->getSession()->evaluateScript($instancesQuery . '[1].data.hasCaption');
    $this->assertTrue($hasCaption, 'Verified caption being inserted on images already placed: ' . (string)$hasCaption);
  }

  public function testAlign() {
    $fieldName = strtolower($this->randomMachineName());

    $this->createImageField($fieldName, ['alt_field' => '0']);
    $this->updateInsertSettings($fieldName, [
      'styles' => [
        'image' => 'image',
      ],
      'default' => 'image',
      'align' => TRUE,
    ]);

    $images = $this->drupalGetTestFiles('image');

    $this->drupalGet('node/add/article');
    $page = $this->getSession()->getPage();

    $page->attachFileToField(
      'files[' . $fieldName . '_0]',
      \Drupal::service('file_system')->realpath($images[0]->uri)
    );

    $this->assertSession()->waitForField($fieldName . '[0][fids]');

    $page->findButton('Insert')->click();

    $alignQuery = 'CKEDITOR.instances["edit-body-0-value"].widgets.instances[0].data.align';
    $align = $this->getSession()->evaluateScript($alignQuery);
    $this->assertEquals('none', $align, 'Verified initial align attribute: ' . $align);

    $elements = $page->findAll('css', '.insert-align-controls input');
    end($elements)->click();

    $align = $this->getSession()->evaluateScript($alignQuery);
    $this->assertEquals('right', $align, 'Verified altering align attribute: ' . $align);

    $page->findField('title[0][value]')->setValue('title');
    $page->findButton('Save')->click();
    $this->drupalGet('node/1/edit');

    $align = $this->getSession()->evaluateScript($alignQuery);
    $this->assertEquals('right', $align, 'Verified align attribute after reloading form of saved node: ' . $align);

    $page->find('css', '.insert-align-controls input')->click();

    $align = $this->getSession()->evaluateScript($alignQuery);
    $this->assertEquals('none', $align, 'Verified reset align attribute: ' . $align);
  }

}
