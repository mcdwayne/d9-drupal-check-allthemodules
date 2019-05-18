# Entity List

Drupal 8 ecedi_list port.

## How to customize the entity list display

`entity_list` define the plugin type `EntityListDisplay` to manage the list render.

You can extend `DefaultEntityListDisplay` or `EntityListDisplayBase` plugin to make your own.

Once the plugin is created, you can now choose your plugin in the display vertical tab in the entity list form.

Example of extending the `DefaultEntityListDisplay`:

```php
<?php

/**
 * @EntityListDisplay(
 *   id = "my_entity_list_display",
 *   label = @Translation("My entity list display")
 * )
 */
class MyEntityListDisplay extends DefaultEntityListDisplay {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      '#markup' => $this->t("This method is reponsible for creating the render array of the list."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state, EntityListInterface $entity_list) {
    $form = parent::settingsForm($form_state, $entity_list);
    
    // Add custom settings.
    
    return $form
  }
}

```