# Dynamic image styles

For certain usecases you don't want to define image styles in the UI:

* You work together with a team on a decoupled app. They don't want to have to touch Drupal.

With this module you can specify image styles dynamically by constructing the following
URL:

```/sites/default/files/styles/image_resize[width]=640&image_resize[height]=500&image_rotate[degrees]=50/public/2017-05/33Ovd.jpg```

The syntax supports any image plugin you want, but I guess the resize is the most common one.

## Protection

In order to protect sites from DDOS you can configure the allowed query strings inside
```image_styles_dynamic.settings```.
