Student ID Card module
======================

![screenshot](https://gitlab.com/francoisjacquet/Student_ID_Card/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/student-id-card/

Version 2.1 - January, 2024

License GNU GPL v2

Author François Jacquet

Sponsored by Gildardo R. C., Salvador

DESCRIPTION
-----------
Print Student ID Card (badge) for any number of students using substitutions.
If you wish parents and students to be able to print their own card, activate the program from the _Users > User Profiles_ screen.

This module extends the following modules:

- Students: Student ID Card

The Student ID Card content is customizable (text, photo position & size, background image, and padding).

Consult a [sample Student ID Card](https://gitlab.com/francoisjacquet/Student_ID_Card/raw/master/img/student-id-card-sample.png?inline=false).

The default settings are suitable for the default background image. Another [sample background image](https://gitlab.com/francoisjacquet/Student_ID_Card/-/blob/master/img/student-id-card-background2.jpg) along with the [appropriate settings](https://gitlab.com/francoisjacquet/Student_ID_Card/-/blob/master/img/student-id-card-background2.md) are also available.

Translated in [French](https://www.rosariosis.org/fr/modules/student-id-card/) and [Spanish](https://www.rosariosis.org/es/modules/student-id-card/).

Note: if you wish parents and students to be able to print their own card, activate the program from the _Users > User Profiles_ screen.

Note 2: you can add your custom CSS rules to the `Student_ID_Card/css/custom.css` file. The file will be automatically loaded. See the [custom_sample.css](https://gitlab.com/francoisjacquet/Student_ID_Card/-/blob/master/css/custom_sample.css) file for portrait/vertical format badge/background image.

CONTENT
-------
Students
- Student ID Card

INSTALL
-------
Copy the `Student_ID_Card/` folder (if named `Student_ID_Card-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires RosarioSIS 6.8+
