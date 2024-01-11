Hostel module
=============

![screenshot](https://gitlab.com/francoisjacquet/Hostel/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/modules/hostel/

Version 1.4 - January, 2024

License GNU GPL v2

Author François Jacquet

DESCRIPTION
-----------
Hostel (dormitory, boarding school) module for RosarioSIS. Manage your school hostel rooms and assign them to students.
Rooms are organized in buildings. You can attribute a room to various students, within the limit of its capacity.
The hostel is shared amongst schools within RosarioSIS.
Translated in [French](https://www.rosariosis.org/fr/modules/hostel/), [Spanish](https://www.rosariosis.org/es/modules/hostel/) and Portuguese (Brazil).

Hostel **Premium** module:
- Room & Building Fields: add custom fields (address, type, etc.)
- Upload and attach file (PDF, etc.)
- Rental Fees: list dates for which Student Fees equal to Room Price will be assigned (a factor can be adjusted for daily prices or for incomplete months).
- Widget: search Students by Building or Room (requires RosarioSIS 10.4+)
- Add "Room" field to _Advanced Report_, _Print Class List_ & _Student Info > General Info_

Note: you must activate both free **and** Premium modules for the Premium module to work.

CONTENT
-------
Hostel
- Rooms
- Room List

VIDEO
-----
[Hostel module review](https://www.rosariosis.org/wp-content/uploads/2022/10/2022-10-18%20Hostel%20module%20review.mp4)


INSTALL
-------
Copy the `Hostel/` folder (if named `Hostel-master`, rename it) and its content inside the `modules/` folder of RosarioSIS.

Go to _School > Configuration > Modules_ and click "Activate".

Requires RosarioSIS 9.2.1+
