## Contributing to paragraphs_collection CSS code

Paragraphs collection module is using Gulp and SASS tools for more efficient CSS
development.

paragraphs_collection is reusing same Gulp building patterns and configurations
from paragraphs base module. Please check paragraphs/css/README.md for detail
explanations of our Gulp building process.


## Preparing your development environment for Gulp/SASS toolchain

To quickly setup your machine do next steps

```
$ cd paragraphs/css
$ npm install
$ gulp
```

For more details please check paragraphs/css/README.md document and relevant
section for development environment preparation.


## Development workflow

We are offering out of the box various more specific or more general tasks.

To easily see all gulp tasks we are offering execute

`$ gulp --tasks`

You will get list like

```
Tasks for .../modules/contrib/paragraphs_collection/css/gulpfile.js
├─┬ default
│ ├─┬ sass:lint
│ │ ├── paragraphs_collection:sass:lint
│ │ ├── paragraphs_collection_demo:sass:lint
│ │ └── paragraphs_collection_test:sass:lint
│ └─┬ sass
│   ├── paragraphs_collection:sass
│   ├── paragraphs_collection_demo:sass
│   └── paragraphs_collection_test:sass
├─┬ paragraphs_collection
│ ├── paragraphs_collection:sass
│ └── paragraphs_collection:sass:lint
├── paragraphs_collection:sass
├── paragraphs_collection:sass:lint
├─┬ paragraphs_collection_demo
│ ├── paragraphs_collection_demo:sass
│ └── paragraphs_collection_demo:sass:lint
├── paragraphs_collection_demo:sass
├── paragraphs_collection_demo:sass:lint
├─┬ paragraphs_collection_test
│ ├── paragraphs_collection_test:sass
│ └── paragraphs_collection_test:sass:lint
├── paragraphs_collection_test:sass
├── paragraphs_collection_test:sass:lint
├─┬ sass
│ ├── paragraphs_collection:sass
│ ├── paragraphs_collection_demo:sass
│ └── paragraphs_collection_test:sass
└─┬ sass:lint
  ├── paragraphs_collection:sass:lint
  ├── paragraphs_collection_demo:sass:lint
  └── paragraphs_collection_test:sass:lint
```

This list should be self explanatory, here are just couple of quick explanations
to get you into the speed:

To compile CSS for paragraphs_collection_test you would do

`$ gulp paragraphs_collection_test:sass`

To run linter on paragraphs_collection_test module you would do

`$ gulp paragraphs_collection_test:sass:lint`

Finally to run all defined tasks on paragraphs_collection_test module just do

`$ gulp paragraphs_collection_test`


If you want to run linter for all modules do

`$ gulp sass:lint`

We also offer default task that is running everything, just execute

`$ gulp`

to run all tasks in all modules.


Note that currently we are not stopping build process in case of linter
warnings. However this does not mean that we are tolerating linter warnings
in our code, you should always make a best effort to provide code that is
linter error free. For more information on linter please check relevant section
in 
