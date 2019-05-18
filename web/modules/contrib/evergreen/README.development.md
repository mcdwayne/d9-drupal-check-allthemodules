# Evergreen Module Development Notes

## Structure

The module is primarily arranged into two entities:

- Evergreen Config (configuration entity)
- Evergreen content (content entity)

The configuration entity is the default settings for a particular entity. It
defines the default status and expiry values for content in that entity.

The content entity relates to the actual content, but contains the actual
settings used for this content (expired? evergreen? expiration time, etc.).
Content entities are currently only created when the entity is created/edited.
Existing content will not get an evergreen content entity unless it is edited.
Furthermore, if the default status for an entity is "perishable", existing
content will be considered expired if it's past the change time + expiry value.
