# Parent Agreement plugin

![screenshot](https://gitlab.com/francoisjacquet/Parent_Agreement/raw/master/screenshot.png?inline=false)

https://gitlab.com/francoisjacquet/Parent_Agreement/

Version 10.0 - July, 2022

Author François Jacquet

Sponsored by Santa Cecilia school, Salvador

License Gnu GPL v2

## Description

Parent Agreement plugin for RosarioSIS. Display your school agreement for parents to accept each school year. Students whose parents did not accept the Agreement cannot login.

Agreement title and text is customizable.

Logic:

- Students without associated parents can login.
- Parents without associated students are not shown the Agreement.
- Parents associated with 2 students are invited to accpet the Agreement or logout.
- Parents who have accepted the Agreement can login.
- Students whose Parents did not accept the Agreement cannot login. The following error message is displayed: "Your parents must login first and accept the Parent Agreement so you can login.".
- Next school year, the Agreement is shown again to Parents.

Translated in French & Spanish.

## Content

Plugin Configuration

- Agreement title
- Agreement text

## Install

Copy the `Parent_Agreement/` folder (if named `Parent_Agreement-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires RosarioSIS 7.3+
