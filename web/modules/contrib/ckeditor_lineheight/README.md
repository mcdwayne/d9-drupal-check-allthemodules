### CKEditor Line Height

Integrates CKEditor's [Line Height plugin](https://ckeditor.com/cke4/addon/lineheight) to Drupal's CKEditor implementation adding a new dropdown button to modify the line height of your content using inline style.

### Requirements
CKEditor Module (Core)

### Installation
1. Download the CKEditor plugin from [here](https://ckeditor.com/cke4/addon/lineheight) and place it in `<Drupal root>/libraries/ckeditor/plugins`
2. Download the [module](https://www.drupal.org/project/ckeditor_lineheight)
3. Enable it
4. Configure the CKEditor toolbar to include the dropdown button

The line heights are predefined, however, you can easily change that setting by implementing `hook_editor_js_settings_alter` for each format like so:

```php
/**
 * Implements hook_editor_js_settings_alter().
 */
function HOOK_editor_js_settings_alter(array &$settings) {
  $settings['editor']['formats']['full_html']['editorSettings']['line_height'] = '10px;22px';
}
```
Above I'm setting the 'full_html' format to have only the 10px and 22px line height options.
Please note that you can change the measuring unit from px to em for example.