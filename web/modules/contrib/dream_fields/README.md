# Dream fields

This module makes it easy to add fields to any entity.

## Todo

- [x] How to declare the options the user can select
- [x] How to define the (possible) options
- [ ] Does this need a separate permission
- [x] Add it as local task to manage fields
- [ ] Add it as local action to entity display
- [x] Add an option to re-use a field
- [x] Add display options
- [x] Add a description for each plugin
- [ ] How to handle other view modes
- [ ] Use weights for sorting the plugins```uasort($options,array('Drupal\Component\Utility\SortArray','sortByWeightElement'));```
- [ ] Use code similar to FieldConfigEditForm::Form to handle defautl widgets

## Which fields to show

To avoid information overflow we need to limit the list to **10** options at the most,
so this means we as a community have to decide which fields needs to be included.

For each type we have to define the following:

- the label
- a good short description in human language
- the storage settings
- the widget settings
- the display settings (default)

Change the output, so the - optional - settings of a field are closer to the radio button.
This will also allow us to use a better description field.

```
() Single line
   Description and maybe an example of when to use this option.
() Image
   Description and maybe an example of when to use this option.
     Image options
() List of checkboxes
   Description and maybe an example of when to use this option.
     Checkbox settings
() Multiple lines of text
```

## Other ideas for fields

- create an image and specify the output width, the code will create an image style on the fly.
- add an internal link.
- add an external link.
- add an hero image, output will use responsive images if enabled, otherwise will fall back to normal image.

## How to decide

AFAIK there are no known number of how many fields are used on an average site, and how they are configured. We could write a module (another one) to gather those statistics, or create a survey asking people which kind of fields they use, but it should contain questions like "An multi value image field with the image linked to the content"
