# Webform Score

This project lets you score an individual user's answers, then store and display the scores.

## Sample use cases

* graded assessments (e.g. quizzes to test students' ability to provide intended responses)
* point-based progress tracking

## Features

* Use existing webform authoring tools
* Pre-assign the following per form element
  * whether to score or not
  * possible point value
  * criteria for awarding points (currently either 'contains' or 'equals' match)
* Display scores on the webform submission page or on a View:
  * overall score as "X/Y" ("X" points out of a total of "Y")
  * score as percentage, i.e. "77%"
* Create custom scoring methods with Webform Score Plugins

## Try a demo

* visit https://simplytest.me/project/webform_score

## Basic usage

1. Enable Webform Score Module and dependencies such as Fraction.
2. Navigate to Webform page.
3. Edit an existing Webform or add a new one.
4. Create a new element on the form using the new category, called "Quiz".
5. Set your Option Values equal to how many points to score for the correct or most correct answers depending on what type of quiz you are creating.
6. At the bottom of the Quiz element, select the methodology to score the form.
7. Repeat steps 4-6 above until you have no more questions to put on your Quiz.
