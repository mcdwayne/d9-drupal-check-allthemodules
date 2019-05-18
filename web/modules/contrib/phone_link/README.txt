Provide formatter for text and telephone (only Drupal 8) fields which replace phone number with link:

```
+38 (000) 000-00-00
```

to

```
<a href="tel:380000000000">+38 (000) 000-00-00</a>
// OR
<a href="tel:380000000000">Call to +38 (000) 000-00-00</a>
// OR Skype format
<a href="callto:380000000000">Call to +38 (000) 000-00-00</a>
```

How to use:

- Install the module.
- Add new text or telephone (only Drupal 8) field.
- Go to display settings and chose "Phone link" formatter for you textfield.
- Set title and text on field formatter settings (optional).
- Save display settings.
