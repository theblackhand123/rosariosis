Billing Elements module
=======================

![screenshot](https://gitlab.com/francoisjacquet/Billing_Elements/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/billing-elements/

Version 11.4 - January, 2024

License GNU GPL v2

Author François Jacquet

Sponsored by English National Program, France & LearnersPlatform, UK & Rousseau International, Cameroon

DESCRIPTION
-----------
Billing Elements and Store. The Billing Elements module is a companion to the Student Billing module. It provides a way to define, categorize, sell, track and generate reports (charts) for billing elements / items. Billing Elements are typically books, school trips, courses or any item you sell to students. A Fee is automatically created every time you assign an element to a (group of) student.

Optionally, each element can be restricted to one or various Grade Levels, and associated to one Course Period.

Elements can be offered to Students and their Parents for purchase. When a Student or a Parent accesses the Billing Elements module, it will be displayed as a "Store" where they can purchase an element if they have sufficient funds (check _Student Billing > Payments_ for Balance). When a course is purchased, the Student is automatically enrolled. Compatible with the Moodle and Iomad plugins.

Note: Elements are automatically rolled to the next school year (when rolling Schools). Uncheck the Rollover checkbox if you do not wish to roll an element.

Note 2: _Category Breakdown_ charts are displaying 25 elements at most per category. Please try to create enough categories in order to fully benefit from the report.

Note 3: Students are **not** enrolled in the course when the Element is assigned by an administrator.

Note 4: the "Purchase" button is hidden from Students and Parents if you remove them access to the _Student Elements_ / _My Elements_ program.

Includes help.
Translated in [French](https://www.rosariosis.org/fr/modules/billing-elements/), [Spanish](https://www.rosariosis.org/es/modules/billing-elements/) and Portuguese (Brazil).

CONTENT
-------
Billing Elements (or "Store" for Student and Parent profiles)
- Elements
- Mass Assign Elements
- Student Elements (or "My Elements" for Students)
- Category Breakdown

INSTALL
-------
Copy the `Billing_Elements/` folder (if named `Billing_Elements-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires RosarioSIS 5.6+
