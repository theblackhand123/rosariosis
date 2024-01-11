# Iomad plugin

![screenshot](https://gitlab.com/francoisjacquet/Iomad/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/plugins/iomad/

Version 10.1 - May, 2023

Author François Jacquet

Sponsored by LearnersPlatform, UK

License Gnu GPL v2

## Description

[Iomad](https://www.iomad.org/) is multi-tenancy Moodle.
This RosarioSIS plugin integrates Iomad with RosarioSIS. RosarioSIS schools are created as Iomad companies.
See the Content section below for integration points.

Translated in [French](https://www.rosariosis.org/fr/plugins/iomad/), [Spanish](https://www.rosariosis.org/es/plugins/iomad/) and Portuguese (Brazil).

## Content

School

- Copy School: "Create company in Iomad".
- School Information: update company Name, Short Name and City, delete company.
- Configuration: assign a school to an existing company.
- Rollover: unassign students from current company and assign to next school year company.

Users

- Add a User: if "Create User in Moodle", will assign user to the selected (or all) companies (teachers and administrators only).
- Administrators are assigned as company managers.
- User Info: unassign user from current companies and assign user to the selected (or all) companies (teachers and administrators only).

Students

- Add a Student: if "Create Student in Moodle", will assign user to company.
- Student Info: unassign student from current company and assign student to the selected company (Enrollment Records).

Scheduling

- Courses: if "Create Course Period in Moodle", will assign course to company (not licensed).
- Schedule: enrol or unenrol student into/from course.
- Group Schedule: enrol students into course.
- Group Drops: unenrol students from course.
- Scheduler: enrol students into courses.


## Install

Copy the `Iomad/` folder (if named `Iomad-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires RosarioSIS 5.8+

### Integrator setup

Install [Iomad](https://www.iomad.org/).

First follow the [Moodle integrator setup](https://gitlab.com/francoisjacquet/rosariosis/-/wikis/Moodle-integrator-setup).

On step **3.4**

- Select _Company Manager_, _Client Course Editor_ for Allow role assignments
- Check Allow for all capabilities under section "Block: Iomad Company / User Admin"

After setup, you can enter the Iomad plugin Configuration.
