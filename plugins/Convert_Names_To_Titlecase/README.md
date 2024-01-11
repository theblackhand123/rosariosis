# Convert Names To Titlecase plugin

![screenshot](https://gitlab.com/francoisjacquet/Convert_Names_To_Titlecase/raw/master/screenshot.png?inline=false)

https://gitlab.com/francoisjacquet/Convert_Names_To_Titlecase/

Version 10.0 - July, 2022

Author François Jacquet

License Gnu GPL v2

## Description

RosarioSIS plugin to convert user and student names to titlecase (first letter of each word to uppercase). This is a convenient tool to correct and standardize names that were entered all in UPPERCASE, or lowercase.

Note: the plugin makes use of the [`initcap()`](https://www.postgresql.org/docs/current/functions-string.html) function from PostgreSQL.

Note 2: the plugin affects the `FIRST_NAME`, `LAST_NAME` and `MIDDLE_NAME` columns for both the `students` and the `staff` database tables.

Translated in French & Spanish.

## Content

Plugin Configuration

- Convert

## Install

Copy the `Convert_Names_To_Titlecase/` folder (if named `Convert_Names_To_Titlecase-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires RosarioSIS 4+
