# Automatic Attendance plugin

![screenshot](https://gitlab.com/francoisjacquet/Automatic_Attendance/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/plugins/automatic-attendance/

Version 11.1 - December, 2023

Author François Jacquet

License Gnu GPL v2

Sponsored by Paris'Com Sup, France

## Description

This plugin will automatically take Attendance each day. Every course period will have their students marked as "Present" (or your default Attendance Code).
You can also use this plugin to manually take missing attendance for past school dates. For this, click the "Take missing attendance" link when on the plugin Configuration screen.
The plugin runs once every day on a per school basis, after the configured hour.
If you use RosarioSIS in a multi-school setup and you notice there is still missing attendance for a school, just switch to this school using the left menu and reload the page using the `F5` key.

Translated in [French](https://www.rosariosis.org/fr/plugins/automatic-attendance/), [Spanish](https://www.rosariosis.org/es/plugins/automatic-attendance/) and Portuguese (Brazil).

## Content

Plugin Configuration

- Run after hour.
- Take missing attendance for a timeframe.

## Install

Copy the `Automatic_Attendance/` folder (if named `Automatic_Attendance-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires RosarioSIS 6.8+
