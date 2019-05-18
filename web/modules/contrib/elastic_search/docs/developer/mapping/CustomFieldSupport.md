# Custom Field Support

The elastic_search plugin uses FieldMapper plugins to map Drupal fields to (multiple) elastic field types, it does so by returning an array of supported field type names.
If you wish to allow a custom field to be mapped to an elastic type you must therefore add the field type name to this array.
To be able to do this each FieldMapper plugin raises an event which allows you to alter their supported field list.
As this is a true 'alter' of the data you are additionally able to remove Drupal field types which you do not wish to be mapped by elastic in this event.

In the implemented subscriber you should listen to the events for the `elastic_search.field_mapper.supports.{elastic_type}`,
where `{elastic_type}` is the ID of the FieldMapper plugin and when the event is triggered add the machine name of your field type to the supported type config array.

For example to add support for your field type `my_field_type` to the elastic type `boolean` you would listen for the event `'elastic_search.field_mapper.supports.boolean'`
and in the subscriber function add `my_field_type` to the supported types array.

As the event is of type `FieldMapperSupports` you can use the methods `$event->getSupported()` and `$event->setSupported($config)` to alter the data.

This is a full working example of a field mapper plugin for the [SoundcloudField](https://www.drupal.org/project/soundcloudfield) module
```
/**
 * Class SoundcloudField
 *
 * @package Drupal\elastic_search_sandbox\EventSubscriber\FieldMapper
 */
class SoundcloudField implements EventSubscriberInterface {

  /**
   * @return mixed
   */
  public static function getSubscribedEvents() {
    $events['elastic_search.field_mapper.supports.text'][] = [
      'alterSupports',
      0,
    ];
    $events['elastic_search.field_mapper.supports.keyword'][] = [
      'alterSupports',
      0,
    ];
    return $events;
  }

  /**
   * @param $event
   */
  public function alterSupports($event) {
    $config = $event->getSupported();
    $config[] = 'soundcloud';
    $event->setSupported($config);
  }
```
