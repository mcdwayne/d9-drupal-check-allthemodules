# Hookalyzer

Hookalyzer provides runtime analysis of hook invocations and dispatched events. It may prove helpful for a few different tasks:

* "Who mucked with my datastructure?!" - hookalyzer tracks the mutation of data through each step of an alter hook, granting inquiring minds a decisive answer to the question, "Where's the
  poop?"
* Grokking the hook system - with so much of Drupal being built through event-driven inversion of control, one can learn a lot about how Drupal functions by watching hooks build datastructures.
* Broader informational and statistical analysis about what hooks are run in your system, where they are run from, what components participate in running them, etc.

## TODO

Most things, still.

* Recursive diff output (on alters). Priority #1!
* Diffing output for non-alter invocations.
* Granular settings-based controls over what hooks to track, and how.
* Use Component/Diff to do snazzy visual diffing of scalars.
* Implement event tracking via an overridden EventDispatcher, as well.
* Output compatibility with both HTML and non-HTML contexts (base storage format should probably be JSON)
* Statistical tracking of number of hooks invoked, their participants, largest datastructure adders/mutators, etc.
* Fun graphs representing the gathered statistical data.
