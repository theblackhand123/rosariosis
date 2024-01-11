# Public Pages plugin

![screenshot](https://gitlab.com/francoisjacquet/Public_Pages/raw/master/screenshot.png?inline=false)

https://www.rosariosis.org/plugins/public-pages/

Version 10.2 - May, 2023

Author François Jacquet

Sponsored by LearnersPlatform, UK

License Gnu GPL v2

## Description

Public pages for RosarioSIS. Publish your school info, events / agenda, marking periods & courses directory.
Visitors who cannot login can access this information publicly. You can choose which page you want to enable.

Translated in [French](https://www.rosariosis.org/fr/plugins/public-pages/), [Spanish](https://www.rosariosis.org/es/plugins/public-pages/) and Portuguese (Brazil).

**Premium** plugin:

- Handles multiple schools.
- Set Default page.
- Custom page: add Title and Content from the _School > Configuration > Public Pages_ tab.
- Activities page.
- Staff (teachers & administrators) directory.
- Each user can decide whether to publish or not his profile.
- "Publish" tab for users to publish custom content about them or their work.

Note: If you have already activated the standard plugin, you will have to remove and reactivate the plugin from the interface or run the `install_premium.sql` file.


## Content

Plugin Configuration

Activate pages:
- School
- Calendar
- Marking Periods
- Courses

## Install

Copy the `Public_Pages/` folder (if named `Public_Pages-master`, rename it) and its content inside the `plugins/` folder of RosarioSIS.

Go to _School > Configuration > Plugins_ and click "Activate".

Requires RosarioSIS 5.0+
