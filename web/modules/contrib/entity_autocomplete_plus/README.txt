This module overrides the core entity autocomplete matcher to make configurable:

- the number of matches returned (this is hard-coded in core to 10)
- extra info returned in addition to the entity title

Extra info is configured using tokens. You may set a default value globally in the module configuration 
(/admin/config/content/entity_autocomplete_plus) and/or set tokens per field using the widget third-party 
settings (under manage form display).
