Acquia Lift: Common Data Format (CDF)
=====================================

ContentHub uses the Common Data Format (CDF) for communication between the ContentHub Service and the client layer comprising all the applications connected to the hub. ContentHub uses the JSON format to serialize CDF. Every piece of data is typed to ease its indexing for search purposes.

Recommendations
^^^^^^^^^^^^^^^

Content Hub supports custom data typing of CDF entities. CDF entities that are shipped by default include:

- ``drupal8_content_entity``
- ``drupal8_config_entity``
- ``rendered_entity``

Additional entity types can be defined as necessary by exporting systems. In Drupal this is done by `subscribing to the proper event`_ and creating custom CDF types to correspond to the data.

.. _subscribing to the proper event: events.html#creating-a-cdf-object

All dates should be expressed in the `ISO 8601`_ format — for example, ``2016-02-09T23:04:20-05:00``. In PHP, use ``date('c')`` instead of ``date('Y-m-d H:i:s’)``. Using ``date('Y-m-d H:i:s’)`` returns ``2016-02-09 23:04:20``, which is incorrect.

.. _ISO 8601: https://en.wikipedia.org/wiki/ISO_8601

Entity structure
^^^^^^^^^^^^^^^^
Each entry in a CDF document is an unrestricted entity, allowing the transmission of a heterogeneous collection in a single file.

Each entry is stored separately within the ``entities`` key of the CDF object. Each entity must have the following key/value pairs:

- ``uuid``: Each entity is identified by a required UUID. Each client application should map its internal storage to such UUID in order to allow data synchronization over time.
- ``type``: An arbitrary string which may be used by the client side to determine the type of entity and trigger appropriate behavior. Examples include drupal8_content_entity and rendered_entity.
- ``created``: The created date in `ISO 8601`_ standard format.
- ``modified``: The modified date in `ISO 8601`_ standard format.
- ``origin``: The UUID of the client application that originally created this entity and owns it. This can be useful on the client side to know if a client is allowed to perform actions such as editing an entity.
- ``attributes``: Localizable collections of values keyed by the attribute name. Each attribute is typed so that it can be stored in ContentHub and indexed properly for search purposes.
- ``assets`` (Optional): A list of resources that are referenced in the attributes of the entity in the form of tokens. ``url`` points to the ``Asset``. Token strings will be replaced in every attribute that has an occurrence in their value.
- ``metadata`` (Optional): An optional list of additional data about the entity that remains unindexed in all circumstances.


.. _ISO 8601: https://en.wikipedia.org/wiki/ISO_8601
.. _ISO 8601: https://en.wikipedia.org/wiki/ISO_8601

Attributes
^^^^^^^^^^
Attribute data drives the CDF communication behavior. Each entity type can support its own attributes so long as those attributes do not conflict in both name AND type to other attributes defined for other entities. As an example, both the ``drupal8_content_entity`` and ``drupal8_config_entity`` use a ``data`` attribute to hold their entity data. This attribute can be shared because its expectations are uniform across all use cases. Additional attributes can be created as necessary for custom entity types. Common attributes include:

- ``data``: Used to store string level data that represents an entity. This could be serialized json, yaml or simple html.
- ``tags``: This attribute is used to promote all the related tags of a given entity into a single indexable attribute of the CDF.
- ``bundle``: This attribute is used in conjunction with the ``drupal8_content_entity`` entity. It documents what bundle is associated with that content for indexing purposes. Examples include ‘article’, ‘product’ and ‘tags’.
- ``entity_type``: This attribute is used in conjunction with both the ``drupal8_content_entity`` and ``drupal8_config_entity`` entities. It documents what entity type is associated with that content or configuration for indexing purposes. Examples include ‘node’, ‘taxonomy_term, or ‘file’.
- ``source_entity``: Used by the ``rendered_entity`` CDF to document the entity from which HTML was generated.
- ``default_language``: The language of the entity.
- ``view_mode``: Used by the ``rendered_entity`` to document the view mode of the render.
- ``language``: Use by the ``rendered_entity`` to document the language of the render.

Examples
^^^^^^^^
**Drupal 8 Content Entity** (``drupal8_content_entity``)

.. code-block:: js

    {
        "entities": [
            {
                "uuid": "757ec2de-ec14-482c-95bf-f7b54415cbea",
                "type": "drupal8_content_entity",
                "created": "2018-07-12T21:20:57+00:00",
                "modified": "2018-07-12T21:20:57+00:00",
                "origin": "3bc7f45e-4e18-3117-99a8-df724068ea81",
                "attributes": {
                    "renders": {
                        "type": "array",
                        "value": {
                            "en": {
                                "full": "6ae82e20-8397-4b3c-9975-0003908f3ad0"
                            },
                            "ko": {
                                "full": "84707969-ad69-45f8-b244-f76ae699ffde"
                            }
                        }
                    },
                    "label": {
                        "type": "string",
                        "value": "Content and content type definitions"
                    },
                    "entity_type": {
                        "type": "string",
                        "value": "node"
                    },
                    "bundle": {
                        "type": "string",
                        "value": "article"
                    },
                    "tags": {
                        "type": "array<reference>",
                        "value": [
                            "66da6c0d-a6cc-4ae7-9f25-ac78057b8b4f",
                            "f6baff74-c23e-439b-bf78-a76b92b34efa",
                            "4ad8e36f-5e17-4117-89b9-ef916086dd91"
                        ]
                    }
                },
                "metadata": {
                    "data": {
                        "type": "string",
                        "value": "*** base64 encoded JSON string ***",
                    },
                    "dependencies": {
                        "0": "9a47a495-02da-44b4-9385-b704b2982715",
                        "1": "baf709a9-a0ce-4ca9-94ab-65346861f72d",
                        "2": "52fa5867-ec57-46b4-9d74-a9beca7105a5",
                        "3": "868fb79d-57c4-4a8b-8de8-97482dca0722",
                        "4": "1b358565-5032-4c10-be86-a7da2dea2404",
                        "5": "54e2242b-3eca-40e3-bd4f-153a06fed693",
                        "6": "0a22d01c-98f6-48d4-9f2f-effd40c1edb5",
                        "7": "a1185cca-f67b-4e77-8875-5f71e65b2fc5",
                        "8": "547e6427-f605-4145-9e01-90eef7873311",
                        "9": "14be7e54-c19a-4a62-a392-838e5480a308",
                        "10": "21720c54-839e-403a-81af-e6194aa9c221",
                        "11": "63d90b0d-8f65-4d07-9d7b-fa5122b2fd04",
                        "12": "dae28869-9b2d-41ed-b6eb-b319bc5efdd3",
                        "13": "f10299cc-f520-4ed7-90e6-34fc6584aa34",
                        "14": "4d951526-ac41-4017-974e-8216e403e1ce",
                        "15": "e95a5718-4849-4b45-968c-600b50a28180",
                        "16": "59631cec-1a88-4d3c-a4f5-38abf04b976d",
                        "17": "2c7a15bc-a106-4893-a9d6-514ab0e972c4",
                        "18": "842b502d-9304-450f-98fc-ffe35a29b340",
                        "19": "33835aab-ed76-4ddb-b162-fe7a63f3a9cc",
                        "20": "d63b73f0-77b5-414d-9c76-4b882d0ffc38",
                        "21": "f3897627-52a1-4fee-8f2a-8285d5a603ed",
                        "22": "d6212781-8fa5-4bc3-a3e7-74b6790ef25c",
                        "23": "ad66a332-6a9a-4b1b-811a-12a9b713b75e",
                        "24": "93a4aab3-2578-465e-8891-31f213e36b80",
                        "25": "97ef9862-d6e0-4f9b-9486-63408f856b5b",
                        "26": "96ee3b5c-00e7-450c-a64c-129f0dca439e",
                        "27": "6b878f4d-24bd-4c89-a4a8-46b6c5643e14",
                        "28": "2f7a0621-52e8-4d7f-8316-1ca984688bb7",
                        "29": "c25f0ad3-10d0-44e6-9385-cef252199f27",
                        "30": "7225f7ee-ff9c-4a9c-b483-000d3af5cec7",
                        "31": "266391fa-5cb2-4496-b188-77a589a67039",
                        "32": "33f00398-fd45-435e-a4fd-6e783236d7c4",
                        "33": "dcb245c2-aba6-46f1-b8e1-464e33c23b71",
                        "34": "725df087-cccd-4587-aa26-7053bcc16644",
                        "35": "02676183-126d-4f0b-ada9-1cc4f9db723f",
                        "36": "d961a36d-1acb-43bd-90f9-92b27f26b05f",
                        "37": "66da6c0d-a6cc-4ae7-9f25-ac78057b8b4f",
                        "38": "c751f2c8-69e8-4a3d-8e9e-b3e763c924bd",
                        "39": "f6baff74-c23e-439b-bf78-a76b92b34efa",
                        "40": "4ad8e36f-5e17-4117-89b9-ef916086dd91",
                        "module": {
                            "comment": "comment",
                            "text": "text",
                            "node": "node",
                            "file": "file",
                            "image": "image",
                            "content_translation": "content_translation",
                            "taxonomy": "taxonomy",
                            "user": "user",
                            "path": "path",
                            "language": "language",
                            "editor": "editor",
                            "ckeditor": "ckeditor"
                        }
                    },
                    "default_language": "en",
                    "field": {
                        "uuid": {
                            "type": "uuid"
                        },
                        "langcode": {
                            "type": "language"
                        },
                        "type": {
                            "type": "entity_reference",
                            "target": "node_type"
                        },
                        "revision_timestamp": {
                            "type": "created"
                        },
                        "revision_uid": {
                            "type": "entity_reference",
                            "target": "user"
                        },
                        "revision_log": {
                            "type": "string_long"
                        },
                        "status": {
                            "type": "boolean"
                        },
                        "title": {
                            "type": "string"
                        },
                        "uid": {
                            "type": "entity_reference",
                            "target": "user"
                        },
                        "created": {
                            "type": "created"
                        },
                        "changed": {
                            "type": "changed"
                        },
                        "promote": {
                            "type": "boolean"
                        },
                        "sticky": {
                            "type": "boolean"
                        },
                        "default_langcode": {
                            "type": "boolean"
                        },
                        "revision_default": {
                            "type": "boolean"
                        },
                        "revision_translation_affected": {
                            "type": "boolean"
                        },
                        "path": {
                            "type": "path"
                        },
                        "content_translation_source": {
                            "type": "language"
                        },
                        "content_translation_outdated": {
                            "type": "boolean"
                        },
                        "body": {
                            "type": "text_with_summary"
                        },
                        "comment": {
                            "type": "comment"
                        },
                        "field_image": {
                            "type": "image",
                            "target": "file"
                        },
                        "field_tags": {
                            "type": "entity_reference",
                            "target": "taxonomy_term"
                        }
                    }
                }
            }
        ]
    }

**Drupal 8 Configuration Entity** (``drupal8_config_entity``)

.. code-block:: js

    {
        "entities": [
            {
                "uuid": "9a47a495-02da-44b4-9385-b704b2982715",
                "type": "drupal8_config_entity",
                "created": "2018-07-12T16:20:57-05:00",
                "modified": "2018-07-12T16:20:57-05:00",
                "origin": "3bc7f45e-4e18-3117-99a8-df724068ea81",
                "attributes": {
                    "data": {
                        "type": "string",
                        "value": "uuid: 9a47a495-02da-44b4-9385-b704b2982715\nlangcode: en\nstatus: true\ndependencies:\n  config:\n    - core.entity_view_display.comment.comment.default\n    - field.field.node.article.body\n    - field.field.node.article.comment\n    - field.field.node.article.field_image\n    - field.field.node.article.field_tags\n    - image.style.large\n    - node.type.article\n  module:\n    - comment\n    - image\n    - text\n    - user\n_core:\n  default_config_hash: ChmU3AVqDKU32A_fyChG0W9dTRKmVBR58B6OClCLvZI\nid: node.article.default\ntargetEntityType: node\nbundle: article\nmode: default\ncontent:\n  body:\n    type: text_default\n    weight: 0\n    region: content\n    settings: {  }\n    third_party_settings: {  }\n    label: hidden\n  comment:\n    type: comment_default\n    weight: 110\n    region: content\n    label: above\n    settings:\n      view_mode: default\n      pager_id: 0\n    third_party_settings: {  }\n  field_image:\n    type: image\n    weight: -1\n    region: content\n    settings:\n      image_style: large\n      image_link: ''\n    third_party_settings: {  }\n    label: hidden\n  field_tags:\n    type: entity_reference_label\n    weight: 10\n    region: content\n    label: above\n    settings:\n      link: true\n    third_party_settings: {  }\n  links:\n    weight: 100\n    region: content\n    settings: {  }\n    third_party_settings: {  }\nhidden:\n  langcode: true\n"
                    },
                    "label": {
                        "type": "string",
                        "value": null
                    },
                    "entity_type": {
                        "type": "string",
                        "value": "entity_view_display"
                    }
                },
                "metadata": {
                    "dependencies": {
                        "0": "baf709a9-a0ce-4ca9-94ab-65346861f72d",
                        "1": "52fa5867-ec57-46b4-9d74-a9beca7105a5",
                        "2": "868fb79d-57c4-4a8b-8de8-97482dca0722",
                        "3": "1b358565-5032-4c10-be86-a7da2dea2404",
                        "4": "54e2242b-3eca-40e3-bd4f-153a06fed693",
                        "5": "0a22d01c-98f6-48d4-9f2f-effd40c1edb5",
                        "6": "a1185cca-f67b-4e77-8875-5f71e65b2fc5",
                        "7": "547e6427-f605-4145-9e01-90eef7873311",
                        "8": "14be7e54-c19a-4a62-a392-838e5480a308",
                        "9": "21720c54-839e-403a-81af-e6194aa9c221",
                        "10": "63d90b0d-8f65-4d07-9d7b-fa5122b2fd04",
                        "11": "dae28869-9b2d-41ed-b6eb-b319bc5efdd3",
                        "12": "f10299cc-f520-4ed7-90e6-34fc6584aa34",
                        "13": "4d951526-ac41-4017-974e-8216e403e1ce",
                        "14": "e95a5718-4849-4b45-968c-600b50a28180",
                        "module": {
                            "comment": "comment",
                            "text": "text",
                            "node": "node",
                            "file": "file",
                            "image": "image",
                            "content_translation": "content_translation",
                            "taxonomy": "taxonomy",
                            "user": "user"
                        }
                    },
                    "default_language": "en"
                }
            }
        ]
    }

**Rendered Entity** (``rendered_entity``)

.. code-block:: js

    {
        "entities": [
            {
                "uuid": "6ae82e20-8397-4b3c-9975-0003908f3ad0",
                "type": "rendered_entity",
                "created": "2018-07-12T21:20:57+00:00",
                "modified": "2018-07-12T21:20:57+00:00",
                "origin": "3bc7f45e-4e18-3117-99a8-df724068ea81",
                "attributes": {
                    "data": {
                        "type": "string",
                        "value": "\n<article data-history-node-id=\"4\" role=\"article\" about=\"\/node\/4\" typeof=\"schema:Article\" class=\"node node--type-article node--promoted node--view-mode-full clearfix\">\n  <header>\n    \n          <h2 class=\"node__title\">\n        <a href=\"\/node\/4\" rel=\"bookmark\"><span property=\"schema:name\" class=\"field field--name-title field--type-string field--label-hidden\">Content and content type definitions<\/span>\n<\/a>\n      <\/h2>\n          <span property=\"schema:name\" content=\"Content and content type definitions\" class=\"rdf-meta hidden\"><\/span>\n  <span property=\"schema:interactionCount\" content=\"UserComments:0\" class=\"rdf-meta hidden\"><\/span>\n\n          <div class=\"node__meta\">\n        <article typeof=\"schema:Person\" about=\"\/user\/1\" class=\"profile\">\n  <\/article>\n\n        <span>\n          Submitted by <span rel=\"schema:author\" class=\"field field--name-uid field--type-entity-reference field--label-hidden\"><span lang=\"\" about=\"\/user\/1\" typeof=\"schema:Person\" property=\"schema:name\" datatype=\"\">admin<\/span><\/span>\n on <span property=\"schema:dateCreated\" content=\"2018-07-12T16:41:50+00:00\" class=\"field field--name-created field--type-created field--label-hidden\">Thu, 07\/12\/2018 - 11:41<\/span>\n        <\/span>\n          <span property=\"schema:dateCreated\" content=\"2018-07-12T16:41:50+00:00\" class=\"rdf-meta hidden\"><\/span>\n\n      <\/div>\n      <\/header>\n  <div class=\"node__content clearfix\">\n    \n            <div class=\"field field--name-field-image field--type-image field--label-hidden field__item\">  <img property=\"schema:image\" src=\"\/sites\/ch2x-pub.dd\/files\/styles\/large\/public\/2018-07\/1280_ApHs8AcgmiB2.png?itok=L2D0tQtQ\" width=\"480\" height=\"189\" alt=\"page layout diagram\" typeof=\"foaf:Image\" class=\"image-style-large\" \/>\n\n\n<\/div>\n      \n            <div property=\"schema:text\" class=\"clearfix text-formatted field field--name-body field--type-text-with-summary field--label-hidden field__item\"><p>Just as you would expect, the content of your website is the information you want to provide to your website\u2019s visitors.<\/p>\n\n<p>Content items \u2014 called\u00a0<em>nodes<\/em>\u00a0in Drupal \u2014 are always of a given\u00a0<em>content type<\/em>.<\/p>\n\n<p>A content type defines how content is collected and displayed. All content types have a title and a body, but this is not always enough to differentiate different kinds of content. To define specific characteristics of different kinds of content, you can add and change the fields on an existing content type and create a new content type with the fields needed to describe it.<\/p>\n\n<p>Different content types are created with different functions in mind, and therefore have different sets of fields. Defining your content by content type also gives you one more criterion that allows you to sort and publish your content in different ways and places on your website.<\/p>\n\n<p>Drupal allows site administrators to edit the standard settings of content types and define custom content types at\u00a0<strong>Structure &gt; Content types<\/strong>.<\/p>\n\n<p>For more information, see the\u00a0<a href=\"https:\/\/www.drupal.org\/node\/21947\">Content types<\/a>\u00a0definition page on Drupal.org, or\u00a0<a href=\"https:\/\/www.drupal.org\/getting-started\/6\/admin\/content\/types\">About content types<\/a>.<\/p><\/div>\n      <div class=\"field field--name-field-tags field--type-entity-reference field--label-above clearfix\">\n      <h3 class=\"field__label\">Tags<\/h3>\n    <ul class=\"links field__items\">\n          <li><a href=\"\/taxonomy\/term\/3\" property=\"schema:about\" hreflang=\"en\">Content types<\/a><\/li>\n          <li><a href=\"\/taxonomy\/term\/4\" property=\"schema:about\" hreflang=\"en\">Nodes<\/a><\/li>\n          <li><a href=\"\/taxonomy\/term\/5\" property=\"schema:about\" hreflang=\"en\">Drupal<\/a><\/li>\n      <\/ul>\n<\/div>\n  <div class=\"node__links\">\n    <ul class=\"links inline\"><li class=\"comment-forbidden\"><a href=\"\/user\/login?destination=\/node\/4%23comment-form\">Log in<\/a> or <a href=\"\/user\/register?destination=\/node\/4%23comment-form\">register<\/a> to post comments<\/li><\/ul>  <\/div>\n<section rel=\"schema:comment\" class=\"field field--name-comment field--type-comment field--label-above comment-wrapper\">\n  \n  \n\n  \n<\/section>\n\n  <\/div>\n<\/article>\n"
                    },
                    "source_entity": {
                        "type": "string",
                        "value": "757ec2de-ec14-482c-95bf-f7b54415cbea"
                    },
                    "language": {
                        "type": "string",
                        "value": "en"
                    },
                    "view_mode": {
                        "type": "string",
                        "value": "full"
                    },
                    "view_mode_label": {
                        "type": "string",
                        "value": "Full content"
                    },
                    "base_url": {
                        "type": "string",
                        "value": "http:\/\/ch2x-pub.dd:8083\/"
                    },
                    "preview_image": {
                        "type": "string",
                        "value": "http:\/\/ch2x-pub.dd:8083\/sites\/ch2x-pub.dd\/files\/styles\/acquia_lift_support_preview_image\/public\/2018-07\/1280_ApHs8AcgmiB2.png?itok=JkAwc5_t"
                    },
                    "label": {
                        "type": "string",
                        "value": "Content and content type definitions"
                    },
                    "entity_type": {
                        "type": "string",
                        "value": "node"
                    },
                    "bundle": {
                        "type": "string",
                        "value": "article"
                    },
                    "bundle_label": {
                        "type": "string",
                        "value": "Article"
                    },
                    "tags": {
                        "type": "array<reference>",
                        "value": [
                            "66da6c0d-a6cc-4ae7-9f25-ac78057b8b4f",
                            "f6baff74-c23e-439b-bf78-a76b92b34efa",
                            "4ad8e36f-5e17-4117-89b9-ef916086dd91"
                        ]
                    }
                }
            }
        ]
    }
