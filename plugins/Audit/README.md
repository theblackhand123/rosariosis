Audit module
============

![screenshot](https://gitlab.com/francoisjacquet/Audit/raw/master/screenshot.png?inline=false)

https://gitlab.com/francoisjacquet/Audit

Version 10.0 - July, 2022

License GNU GPL v2

Author François Jacquet

DESCRIPTION
-----------
Audit module for RosarioSIS. This module adds a school security tool for audit purpose. The Audit Log program records every SQL query of INSERT, UPDATE and DELETE type along with the user, time and URL. Track down every significant action performed by users in the system and consult logs in the list.

Warning: The module will slow down the system execution by 10% and will significantly fill up the database.

Translated in French & Spanish.

CONTENT
-------
School
- Audit Log

INSTALL
-------
Copy the `Audit/` folder (if named `Audit-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires RosarioSIS 4.5+
