# Supported Fields

Currently (29/08/17) the full set of elastic search fields are supported by this plugin.
The following list shows what, if any, drupal field type they are mapped to.

## Elastic Types

| Elastic DataType | Field Mapper Implemented | Drupal Field Type(s)               |
|:-----------------|:-------------------------|:-----------------------------------|
| Binary           | Y                        |                                    |
| Boolean          | Y                        | boolean                            |
| Byte             | Y                        |                                    |
| Date             | Y                        | date                               |
|                  |                          | datetime                           |
|                  |                          | created                            |
|                  |                          | changed                            |
|                  |                          | timestamp                          |
| Double           | Y                        | decimal                            |
| Float            | Y                        | decimal                            |
| GeoPoint         | Y                        |                                    |
| GeoShape         | Y                        |                                    |
| HalfFloat        | Y                        | decimal                            |
| Integer          | Y                        | integer                            |
|                  |                          | duration                           |
| IP               | Y                        |                                    |
| Keyword          | Y                        | text                               |
|                  |                          | uri                                |
|                  |                          | link                               |
|                  |                          | string                             |
|                  |                          | token                              |
|                  |                          | uuid                               |
|                  |                          | language                           |
|                  |                          | path                               |
|                  |                          | email                              |
| Long             | Y                        | integer                            |
| Nested           | Y                        | n/a (used automatically if nested) |
| Object           | Y                        | entity_reference                   |
|                  |                          | entity_reference_revisions         |
|                  |                          | file                               |
|                  |                          | image                              |
| ScaledFloat      | Y                        | decimal                            |
| Short            | Y                        |                                    |
| Text             | Y                        | text                               |
|                  |                          | text_long                          |
|                  |                          | text_with_summary                  |
|                  |                          | uri                                |
|                  |                          | link                               |
|                  |                          | string                             |
|                  |                          | string_long                        |
|                  |                          | token                              |
|                  |                          | uuid                               |
|                  |                          | language                           |
|                  |                          | path                               |
|                  |                          | email                              |
| Token Count      | Y                        |                                    |
|                  |                          |                                    |
| Attachment       | N                        |                                    |
| Percolator       | N                        |                                    |
|                  |                          |                                    |


##  Meta Types

| MetaType        | ElasticDataType | Field Mapper Implemented | Drupal Field Types         |
|:----------------|:----------------|:-------------------------|:---------------------------|
| None            | n/a             | Y                        | ALL                        |
| SimpleReference | Keyword         | Y                        | entity_reference           |
|                 |                 |                          | entity_reference_revisions |
