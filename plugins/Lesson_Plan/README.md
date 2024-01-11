Lesson Plan module
==================

![screenshot](https://gitlab.com/francoisjacquet/Lesson_Plan/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/lesson-plan/

Version 1.0 - July, 2023

License GNU GPL v2

Author François Jacquet

Sponsored by Rousseau International, Cameroon

DESCRIPTION
-----------
This module adds a Lesson Plan to the _Scheduling_ module. For each Course Period, teachers can write some (rich) text about their lessons, their objectives and each of their parts' activities and resources via dedicated text inputs. The lessons are added to the Plan and can later be consulted by students, parents and administrators.

For each lesson, the teacher can enter its title, date, location, length in minutes.

Additionnaly, parts of the lesson can be added. Each part has a time (minutes) and consists of the following predefined MarkDown fields:
- Content and teacher activity
- Learner activity
- Formative assessment
- Learning materials and resources

Note: if you do not wish to share lesson plans and keep them private to teachers and administrators, simply remove access to the _Scheduling > Read_ program for Student and Parent profiles using the _Users > User Profiles_ program.

Note 2: if you wish to rename the predefined fields and their tooltips, you can edit the existing translations or create your own for your locale. For example for English, copy the [`locale/es_ES.utf8/`](https://gitlab.com/francoisjacquet/Lesson_Plan/-/tree/master/locale) folder to `locale/en_US.utf8`, and edit the `Lesson_Plan.po` file with [Poedit](https://poedit.net/).

Translated in [French](https://www.rosariosis.org/fr/modules/lesson-plan/), [Spanish](https://www.rosariosis.org/es/modules/lesson-plan/).

The **Premium** version adds the following functionalities:

- Attach File to Lessons
- Teachers can evaluate past lessons
- Send email reminder to Teachers who did not add a lesson for next week's classes. Setup from the _School > Configuration_ program.

Note: both the Premium and free modules must be activated for the Premium module to work.

CONTENT
-------
School
- Configuration (Email Reminder)

Scheduling
- Lesson Plan - Read
- Lesson Plan - Add Lesson (Teacher only)

INSTALL
-------
Copy the `Lesson_Plan/` folder (if named `Lesson_Plan-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School Setup > School Configuration > Modules_ and click "Activate".

Requires RosarioSIS 9.0+
