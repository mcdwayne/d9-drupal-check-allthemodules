# Contextual Views

Drupal module that provides contextual views blocks.

* Project homepage: https://www.drupal.org/project/contextual_views

## Composer

- It's suggested to install the module via composer:

   `composer require drunomics/contextual_views`
   
   The module is also available as `drupal/contextual_views` via the drupal.org
   release system, however only `drunomics/contextual_views` gets patch level
   releases. This is as drupal.org does not support semantic versioning for
   modules yet.

## Installation

Install as usual, see
 https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8
for further information.

## Usage instructions

* Define a block plugin with the same plugin ID as used automatically. E.g. 
  `views_block:news-news_by_channel` . The pattern is 
  `views_block:{{ view }}-{{ display }}`. This way the defined class will be used
   automatically instead of the default one. 
* Extend the class `Drupal\contextual_views\Plugin\Block\ContextualViewsBlock`
* Define the necessary context on the plugin using annotations, e.g. the node context.
* Alter the block form to add additional config as needed, e.g. a textfield that 
  contains the name of the field holding the term reference.
* Override build(), get the context value (e.g. the node), derive the views argument
  (the taxonomy term) from the context and set it on the view as argument using 
  `$view->setArguments()`. Then call the parent build.
 
A complete code example for the described block can be viewed here: https://gist.github.com/fago/f51fe8861919a40c753a03c79a134848


## Todos

* Write more documentation
* Provide a configuration form for satisfying views context based upon available
  block context.



## Contributing

Always use the  [Module issue queue](https://www.drupal.org/project/issues/contextual_views).

Development happens on GitHub using the pull request model:
in case you are not familiar with that, please take a few minutes to read the
[GitHub article](https://help.github.com/articles/using-pull-requests) on using
pull requests.

There are a few conventions that should be followed when contributing:

* Always create an issue in the [drupal.org issue queue](https://www.drupal.org/project/issues/contextual_views)
  for every pull request you are working on.
* Always cross-reference the Issue in the Pull Request and the Pull Request in
  the issue.
* Always create a new branch for every pull request: its name should contain a
  brief summary of the ticket and its issue id, e.g **readme-2276369**.
* Try to keep the history of your pull request as clean as possible by squashing
  your commits: you can look at the [Symfony documentation](http://symfony.com/doc/current/cmf/contributing/commits.html)
  or at the [Git book](http://git-scm.com/book/en/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages)
  for more information on how to do that.

For further information on how to contribute please refer to
[the documentation](https://www.drupal.org/contribute/development/).
