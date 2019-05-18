CONTRIBUTING
------------

This document provides guidance for contributing to this project.

Via GitLab
-----------

The simplest way to contribute to the Official Documentation (provided by this repo) is to use the GitLab web interface.

1. Login to GitLab.
2. [Create a fork of the repository](https://docs.gitlab.com/ee/workflow/forking_workflow.html#creating-a-fork).
3. Make changes to your fork it the GitLab UI.
4. [Submit a merge request](https://docs.gitlab.com/ee/workflow/forking_workflow.html#merging-upstream).

Please read the [Project forking workflow documentation](https://docs.gitlab.com/ee/workflow/forking_workflow.html) to learn how you can perform these steps.

ASCIIDOC OUTPUT BUILD SCRIPTS
-----------------------------

The Guides and Guidelines are both set up with scripts to make output compatible with the [AsciiDoc Display module](https://www.drupal.org/project/asciidoc_display), as well as PDF and other e-book output (the scripts are adapted from the sample scripts in that project, and only the Guides book is currently set up to make e-book output).

To run the scripts, you will need several open-source tools:

- AsciiDoc (for any output): http://asciidoc.org/INSTALL.html
- DocBook (for any output): http://docbook.org or http://www.dpawson.co.uk/docbook/tools.html
- FOP (for PDF): http://xmlgraphics.apache.org/fop/
- Calibre (for MOBI): http://calibre-ebook.com/

On a Linux machine, you can use one of these commands to install all the tools:

    apt-get install asciidoc docbook fop calibre
    yum install asciidoc docbook fop calibre

On a Mac:

    brew install asciidoc xmlto

Note that these scripts do not work with all available versions of AsciiDoc and Docbook tools. They have been tested to work with:

- asciidoc - version 8.6.9
- xmlto - version 0.0.25

You can check versions by typing:

    asciidoc --version
    xmlto --version

on the command line.

### Compiling AsciiDoc to HTML

You can compile the AsciiDoc documentation into HTML files by running the following from the repository's root directory:

    composer mac-compile-docs
    composer linux-compile-docs

depending on your operating system.

### IDE Configuration

If you would like to make a substantial contribution, take a few minutes to set up your local environment for efficient, automated document compilation.

If you use PHPStorm, you can configure it to render render a preview of AsciiDoc files by installing the [AsciiDoc plugin](https://plugins.jetbrains.com/plugin/7391-asciidoc).

### Managing GitLab CI

This section is for repository maintainers only.

The CI scripts for merge requests live it `.gitlab-ci.yml`. These use the [grasmash/asciidoc docker image](https://hub.docker.com/r/grasmash/asciidoc/) as a base and will compile and validate the documentation.

To update the base image:

1. Modify Dockerfile
2. `docker build -t asciidoc .`
3. `docker tag asciidoc grasmash/asciidoc`
4. `docker push grasmash/asciidoc`

To locally execute the GitLab CI scripts:

1. Install [GitLab Runner](https://docs.gitlab.com/runner/install/)
2. `gitlab-runner exec docker compile_docs`