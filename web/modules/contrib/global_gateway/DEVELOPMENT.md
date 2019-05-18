## Adding a new mapper

Every mapper can consists of such "bricks":
- mapper plugin to retrieve mapped data and some ui functions,
for example country -> money
- config entity to store a mapped data and also schema for it
- forms for the admin ui interface to manage mappings
- switcher_data plugin to provide line items for global gateway block

**Mapper plugin**

Add a new mapper class in the `Drupal\my_module\Plugin\global_gateway\mapper` namespace.
Use this reference Drupal\global_gateway_language\Plugin\global_gateway\mapper\LanguageMapper
as an example.
Important thing about annotation: `entity_type_id` param used to bind
config entity and the plugin.

**Config entity**

Example: `global_gateway/modules/global_gateway_language/src/Entity/RegionLanguageMapping.php`.
*Notice*: we don't use `handlers > forms`
or `links` annotation properties.
Instead of `handlers > forms` we're adding routes manually,
via `.routing.yml`.

*Important*. You should add `_module_dependencies: 'global_gateway_ui'`
to the route definition in the `requirements` section of the route.

Schema.
Should contains at least one property `mapping` where you should define
`region` property and `your_property` (as a sequence)

**Forms**

Yor are completely free to define admin forms. For the reference use
`global_gateway/modules/global_gateway_language/src/Form/LanguageMappingChangeForm.php`.

To get a current mapping between region and your `thing` use:
```
    $mapping = $this->mapper
      ->setRegion($region)
      ->getEntity();
```

**switcher_data plugin**

You should define your plugin in the `my_module/src/Plugin/global_gateway/switcher_data`
folder.
There is only one method that is required for this plugin:
`getOutput()`. It is invoked on global gateway block build stage,
as also as on ajax block refreshing
(when user changes a country).

*Note:* you should manually add your plugin id
in `Drupal\global_gateway\Form\GlobalGatewaySwitcherForm::__construct` method
to allow global gateway block to use output from your plugin.
Also you can rewrite that part of code in the block,
to control line items per block instance.


**Create new detection method:**

You have to create class in your own module under src/Plugin/RegionNegotiation directory.
Class should have annotation like in the following example:

```
@RegionNegotiation(
  id = "smart_ip",
  weight = -5,
  name = @Translation("Smart IP"),
  description = @Translation("Detect region code using Smart IP module.")
)
```
Class should extends the basic class "RegionNegotiationTypeBase"
 and implement your own method:
  ```public function getRegionCode(Request $request = NULL)```
which will be used for region code detection.
Programmatically get region code:

To get the region code programmatically
 you have to use "global_gateway_region_negotiator" service, example:
```php
$region_code = \Drupal::service('global_gateway_region_negotiator')->negotiateRegion();
```
