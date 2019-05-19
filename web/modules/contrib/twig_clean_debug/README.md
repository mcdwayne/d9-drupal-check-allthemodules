# Intro

When in twig debug mode the html comments can interfere with development. This 
filter cleans away the suggestions using regex.

## Usage

Must be used in conjunction with the `|raw` filter otherwise output will be 
double escaped, e.g.:

```
{{ content.field_image|clean_debug|raw }}
```

## Warning

This will throw an exeption if the site is not in twig debug mode.