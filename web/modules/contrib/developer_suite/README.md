# Developer Suite

Provides several tools for developers. This module provides no interface but 
only an API.

## Tools

For examples please see the /modules/developer_suite_examples folder in the 
root of this project.

#### Command Bus

Provides a command bus for developers. The command bus comes in handy if your 
application utilizes a service layer. For more information about service layers
check out this great talk: https://www.youtube.com/watch?v=ajhqScWECMo.

#### Batch Manager

The Batch Manager is a service that wraps the Drupal batch API providing an 
easy interface for your batch operations.

#### Entity Type Class

Provides a plugin based system to alter entity type classes. It currently 
support overriding Node (per node type), User and File entity type classes.

#### Collection

Provides an extendable base class that predefines several useful methods to 
make working with collections more consistent and easy.

#### Advanced Validation

Provides an extendable general and form validator class to perform advanced
validation much like the Symfony constraints.

