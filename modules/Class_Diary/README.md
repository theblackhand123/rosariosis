Class Diary module
==================

![screenshot](https://gitlab.com/francoisjacquet/Class_Diary/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/class-diary/

Version 10.3 - April, 2023

License GNU GPL v2

Author François Jacquet

DESCRIPTION
-----------
This module adds a Class Diary to the _Attendance_ module. For each Course Period, teachers can write some (rich) text about their class. The entries are added to the Diary and can later be consulted by students, parents and administrators.

Teachers can for example record a summary of what has been achieved or taught during the class. Whether it be private or to share it with parents.

Note: if you do not wish to share diary entries and keep them private to teachers and administrators, simply remove access to the _Attendance > Read_ program for Student and Parent profiles using the _Users > User Profiles_ program.

Translated in [French](https://www.rosariosis.org/fr/modules/class-diary/), [Spanish](https://www.rosariosis.org/es/modules/class-diary/) and Portuguese (Brazil).

The **Premium** version adds the following functionalities:

- Attach File(s) to Entries
- Enable Comments per diary (students, parents or other users can leave a comment for each entry). Users can delete their own comment. Only teachers and administrators can delete any comment.
- Send email reminder to Teachers who did not add an entry for yesterday's classes. Setup from the _School > Configuration_ program.

Note 2: both the Premium and free modules must be activated for the Premium module to work.

CONTENT
-------
School
- Configuration (Email Reminder)

Attendance
- Class Diary - Read
- Class Diary - Write (Teacher only)

INSTALL
-------
Copy the `Class_Diary/` folder (if named `Class_Diary-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School Setup > School Configuration > Modules_ and click "Activate".

Requires RosarioSIS 7.8+
