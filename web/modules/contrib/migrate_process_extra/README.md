## About

Provides a collection of Migrate process plugins that are not part of the core
or Migrate Plus process plugins.

Source plugins are already covered by a variety of modules 
by core or contrib modules (e.g. Migrate Plus for XML, JSON, ... or Migrate 
source XLS, Migrate Spreadsheet, ...).
Destination plugins are more intended to be really custom.

### Extra process plugins

- **country_code** - Gets the ISO ISO 3166-1 alpha-2 country code from a 
country name, with optional locale configuration, requires 
commerceguys/addressing and commerceguys/intl, used by the Address module.
- **synonym** (@todo see #2889388) - Merges several values for entity reference.
- **validate_email** - Validates email address via the core email.validator 
service, with optional MX / A lookup.
- **validate_link** - Checks if a link does not return a 404 header.
- **format_phone** (@todo see #2889576) - Formats a phone number based on
Google's libphonenumber.
- **wrapper_extract** - Extracts a value from a wrapper string defined
via configuration: [ ], ( ), ...
- **wrapper_remove** - Removes a value from a wrapper, and the wrapper itself.

Example use case for Synonym: several values are describing the same 
reality and we want to define them as the same taxonomy term, this can be
also useful to cover typos.

Example use case for WrapperExtract or WrapperRemove: a title that provides
the name and acronym (Drupal.org (DO)) that must be extracted in two fields.

## Documentation

### country_code

Example Migrate process plugin definition for the Address module, 
from several source fields, with a country name field
defined in English that is converted in a country code.

```
  field_address/country_code:
    plugin: country_code
    source: country_name_en
    locale: en
  field_address/locality: city
  field_address/postal_code: zip
  field_address/address_line1: address
```

### synonym

@todo 

### validate_email

Example with an optional DNS validator.
It checks the MX record first with a fallback to the A (or AAAA)' record.
If one of the two is valid, the test passes.

```
  field_email:
    plugin: validate_email
    source: email
    extra_validator: dns
```

### validate_link

```
  field_website:
    plugin: validate_link
    source: website
```

### format_phone

[Documentation](https://github.com/giggsey/libphonenumber-for-php/blob/master/docs/PhoneNumberUtil.md])

```
  field_telephone:
    plugin: format_phone
    source: head_office_phone
    region: EU
```

### wrapper_extract

Example for a name that contains an acronym defined between parentheses:
e.g. _'European Union (EU)'_.
Define **open** as _'('_ and **close** as _')'_ to get the _'EU'_ string.
Also possible for other cases like _'&lt;tag&gt;'_ and _'&lt;'/tag&gt;'_ 
in some situations, when tags are enclosed in the string.

```
  field_acronym:
    plugin: wrapper_extract
    source: name
    open: (
    close: )
```

### wrapper_remove

Example for a name that contains an acronym defined between parentheses:
e.g. _'European Union (EU)'_.
Define **open** as _'('_ and **close** as _')'_ to get the
_'European Union'_ string.

```
  title:
    plugin: wrapper_remove
    source: name
    open: (
    close: )
```
