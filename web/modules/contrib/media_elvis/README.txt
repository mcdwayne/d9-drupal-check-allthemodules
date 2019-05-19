SUMMARY
Media Elvis integration relies on Entity browser module, more specifically it
provides an extension (plugin) for that module. The main advantage of Entity
browser is that it can be used as a central hub for selecting, uploading or
importing media in various parts of the content creation process. For example it
can be used to attach media to node fields, but at the same time it can be used
to add inline media.


Media Elvis supports two types of output file entity and media entity.

REQUIREMENTS
- Entity Browser
- File entity or Media entity

INSTALLATION
- Make sure Entity browser is enabled
- Make sure File entity or Media entity is enabled
- Download https://github.com/desandro/imagesloaded/archive/v3.2.0.zip and
  extract the download to /libraries/imagesloaded (or any libraries directory if
  you're using the Libraries module).
- Download https://github.com/desandro/masonry/archive/v3.3.2.zip and extract
  the download to /libraries/masonry (or any libraries directory if you're using
  the Libraries module)
- Enable Media Elvis

IF YOU USE BOWER
- Edit/add a .bowerrc file with libraries path configuration:
  {
    "directory": "web/libraries",
    "ignoredDependencies": [
      "jquery"
    ]
  }
- Eddit/add a bower.json file with the following dependencies:
  {
    "name": "example_bower_file",
    "authors": [
      "MD-Systems"
    ],
    "description": "",
    "main": "",
    "license": "GPL-2.0+",
    "homepage": "http://www.md-systems.ch/",
    "private": true,
    "ignore": [
      "**/.*",
      "node_modules",
      "bower_components",
      "test",
      "tests"
    ],
    "dependencies": {
      "masonry": "^3.3.2",
      "imagesloaded": "^3.2.0"
    }
  }
