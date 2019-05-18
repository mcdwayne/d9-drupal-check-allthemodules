# Field formatter conditions

### Adds conditional functionality to fields.

You can create a FieldFormatterConndition on fairly simple manner.
Generate a plugin.

    <?php
    namespace Drupal\your_module\Plugin\Field\FieldFormatter\Condition;

    use Drupal\fico\Plugin\FieldFormatterConditionBase;

    /**
      * Description for your plugin.
      *
      * @FieldFormatterCondition(
      *   id = "your_plugin_id",
      *   label = @Translation("Your plugin name"),
      *   dsFields = TRUE,
      *   types = {
      *     "all"
      *   }
      * )
      */
    class YourConditionName extends FieldFormatterConditionBase {

      /**
       * {@inheritdoc}
       */
      public function alterForm(&$form, $settings) {
        // Define your formular elements here...
      }

      /**
       * {@inheritdoc}
       */
      public function access(&$build, $field, $settings) {
        // Define the access here.
        // Restrict Access via: $build[$field]['#access'] = FALSE; .
      }

      /**
       * {@inheritdoc}
       */
      public function summary($settings) {
        return t("Condition: %condition (%settings)", [
          "%condition" => t('Your plugin name'),
          '%settings' => $your_plugin_settings,
        ]);
      }

    }
