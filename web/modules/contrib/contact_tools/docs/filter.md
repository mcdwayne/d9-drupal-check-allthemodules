# Filter

Module provide single filter called "Contact Tools modal links".

This filter looking for all link which `href` tag contains `/contact-tools/CONTACT_FORM`. What it does:

- It's looking for class, and add `use-ajax` class if you forgot it or don't want to add manually. So you don't need to worry about it. You also can pass all classes what you need here without `use-ajax`, fitler handle it.
- Set `data-dialog-type` to `modal` if you not set it manually or don't need other variant of modal.
- Set default values for `data-dialog-options`: width to 500px and dialogClass to `contact-tools-modal` as you call it via other methods. You can override it by passing your own values, filter respects user input over default values. You can also pass aditional dialog options according to [Dialog API](http://api.jqueryui.com/dialog/). Also, if just need several additional options, but you okay with default values, you can not pass them the will be added to your additional options.
- Attach `core/drupal.dialog.ajax` library. It will be included only on pages where fitler finds such links, if not, this library won't be loaded.

### Examples

```html
<!-- Simple example with minimum data. -->
<a href="/contact-tools/callback">Call me!</a>

<!-- You also can pass arguments and other data you need. -->
<a href="/contact-tools/callback?from=header-block" class="button">Call me!</a>

<!-- You can change dialog default options. Look at the quotes for data-dialog-options attribute. This is important! -->
<a href="/contact-tools/callback" data-dialog-options='{"width": "auto"}'>Call me!</a>

<!-- Pass additional dialog options from Dialog API which is not provided by default. -->
<a href="/contact-tools/callback" data-dialog-options='{"title": "We will call you!", "width": "100%"}'>Call me!</a>
```