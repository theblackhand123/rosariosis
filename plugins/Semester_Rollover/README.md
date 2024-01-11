Semester Rollover module
========================

![screenshot](https://gitlab.com/francoisjacquet/Semester_Rollover/raw/master/screenshot.png?inline=false)

https://gitlab.com/francoisjacquet/Semester_Rollover/

Version 10.1 - September, 2022

License GNU GPL v2

Author François Jacquet

Sponsored by Instituto Japon, Ecuador

DESCRIPTION
-----------
Semester Rollover lets you roll students to the next semester. The Rollover program only serves for one school year to another, and thus does not allow for passing students to the next grade during the year. The Semester Rollover Students program works by first dropping students and re-enroll them in the Next Grade, on the first day of the next semester. See Reference below.

Note: you may want to create specific _Enrollment Codes_ such as "Beginning of Semester 2" and a "End of Semester 1" drop code.

Warning: please make sure you edit and save a copy of _Report Cards_ (and maybe _Transcripts_) for Semester 1 before using the _Semester Rollover Students_ program! After Rollover, the Grade Level will not be relevant anymore.

Includes Help.
Translated in French & Spanish.

REFERENCE
---------
Students are rolled to the next Semester based on their individual "Rolling / Retention Options" (see _Students > Student Info_):
- Next grade at current school: the student is enrolled in the next Grade Level (see _School > Grade Levels_) or only Dropped if no Next Grade is set.
- Retain: no need to drop and re-enroll the student in the same Grade Level. The student is skipped.
- Do not enroll after this school year: student is Dropped. Interpreted here as "Do not enroll after this semester".
- Other School: the student is enrolled in another school. Note: no Grade Level or Calendar are set.


CONTENT
-------
School
- Semester Rollover Students

INSTALL
-------
Copy the `Semester_Rollover/` folder (if named `Semester_Rollover-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires RosarioSIS 5.0+
