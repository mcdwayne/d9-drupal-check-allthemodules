# Embederator

There are many ways to go about embedding snippets of markup (CRM forms, internal iframes, etc etc.) but this module attempts to balance needs of admins, themers, and content editors. The "embederator" is a content entity, and "embederator_type" config entities serve as its bundles. An individual type of embed (e.g., a species of Blackbaud ticket purchase form) is represented by a bundle, while a particular instance of that form (e.g., to purchase tickets to the Nutcracker on Dec 14) is a single content entity of that bundle.

In this model, the bulk of the markup for the form is saved with the config entity as full_html and tokens are used to represent pieces of the individual form that change. When an individual embederator form is rendered, those tokens are subsituted using fields on the embederator entity. Since customizing an individual form is usually possible with a single ID or hash value, `embed_id` is included as a base field of the embederator and `[embederator:embed_id]` may be used when setting the markup for the bundle. Other fields may be added as needed to the bundle and as tokens in the embed markup.

Some advantages to this approach:

- Sitebuilders may restrict access to the bundle configuration to limit security concerns about dealing with raw HTML
- Content editors have access to a list of all embedded entities and are able to create a new embed with (usually) a single text field ID
- Bundles are templatable as `embederator--BUNDLE.html.twig` so responsive wrappers may be customized (by default embeds get BEM-style wrappers with bundle identifiers)
- Markup for embeds is importable/exportable config so may be modified headlessly
