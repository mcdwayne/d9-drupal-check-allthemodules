# Content Serialization

## Introduction

The module allows you to serialize content entities. It's intended to provide
content while building a site, and for initial deployment of new site features.
It's explicitly designed to:
1. only use UUIDs.
1. provide easy collaboration and development:
   - Allow content to come from multiple sources (eg. initial content from the
     developers and updated content from a staging branch).
   - Exports should not change unless the data's actually changed (serial IDs,
     domain names, etc. shouldn't matter).
1. be format/serializer-agnostic.
1. allow cyclic references, eg. two nodes have related content fields
   referencing one another.
 
The code design goals are to
1. Make the code unopionated, eg. an import doesn't require a module, the source
   or destination can be switched out, the format isn't assumed, etc.
1. Keep things modular, eg. if someone wants to create their own export process
   and would like to use just the normalizers or batch loader from this project, 
   that should be possible.
1. Try to be a minimal wrapper around core/Symfony serialization.
   
What isn't the focus?
1. Building a UI.
1. Content staging

## Compatible modules

1. [YAML Encoder]: Lets you export to YAML.

## Usage

You can use content serialization via drush or programmatically.

### drush

Drush 8 is [no longer supported for use with Drupal 8.4+][1], so it's 
recommended to use drush 9.

```bash
# Export node 1 as JSON.
drush contentserialize:export node 1 --format=json --destination=/path/to/folder
# Export node 1 and all its dependencies as XML.
drush contentserialize:export-referenced node 1 --format=xml --destination=/path/to/folder
# Export all content entities except the node bundle 'page' and users to YAML
# (requires yamlencoder).
drush contentserialize:export-all --format=yaml --exclude=node:page,user
# Import from a folder
drush contentserialize:import --source=/path/to/folder
```

There is drush 8 support but it will be removed at some point.

```bash
# Export node 1 as JSON.
drush contentserialize-export node 1 --format=json --destination=/path/to/folder
# Export node 1 and all its dependencies as XML.
drush contentserialize-export-referenced node 1 --format=xml --destination=/path/to/folder
# Export all content entities except the node bundle 'page' and users to YAML
# (requires yamlencoder).
drush contentserialize-export-all --format=yaml --exclude=node:page,user
# Import from a folder
drush contentserialize-import --source=/path/to/folder
```

The destination can also be provided by setting the environment variable
`CONTENTSERIALIZE_EXPORT_DESTINATION` or by setting the configuration value
`file.defaults.export_destination` in `contentserialize.settings`. Similarly the
import source can be set via `CONTENTSERIALIZE_IMPORT_SOURCE` or
`file.defaults.import_sources`.

### API

See `Drupal\contentserialize\Commands\ContentSerializeCommands`.

## Updates

All exported entities, both configuration and content, should be re-exported
after core or contrib updates.

## Multiple sources

In some situations you might want to specify multiple sources for content. For
example:
1. You're building a new site and after each sprint you deliver features that
   include initial (perhaps dummy) content.
1. Prior to launch a content staging site is created where content is edited by
   the client.

The developer can export the initial content into its own directory, for example
`PROFILE/content/initial`. Periodically the content staging branch is exported
into a different folder, eg. `PROFILE/content/updated`. On deployment the
following drush command can be used to ensure content from `updated` will
override any in `initial`.

```bash
drush contentserialize:import --source=PROFILE/content/updated,PROFILE/content/initial
```

1. The client can't delete developer-provided content when using this method.
1. If there have been any contrib/core updates since the content branch was
   deployed, you should update the content branch with the latest code, run the
   updates, and only then export the content.

[1]: http://docs.drush.org/en/master/install/#drupal-compatibility
[YAML Encoder]: https://www.drupal.org/project/yamlencoder
