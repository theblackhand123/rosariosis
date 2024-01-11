# Reference

## FET Timetable generator

[FET](https://lalescu.ro/liviu/fet/) is a free Timetabling software available for Windows, Mac OS and GNU/Linux.

Export your Timetable from FET using the _File > Export_ menu.
You can then import the CSV file.

Here is the column correspondance between FET and RosarioSIS:

- Activity Id = Course Period
- Day = Day
- Hour = Period
- Students Sets = Students Sets / Grade Level
- Subject = Subject
- Teachers = Teacher
- Room = Room

The "Activity Tags" and "Comments" columns are not imported, unless you want to reuse them for another field like "Seats" (Course Period) in RosarioSIS.


## Examples

You will find sample CSV and Excel files inside the `tests/` folder.

Note: to import the `Brazil-more-difficult_timetable.csv` file, you should first set RosarioSIS to Portuguese (`pt_PT.utf8` locale).


## Data Processing

Subject
- Relate existing Subjects in RosarioSIS or create it.
- Comparison is done using lowercase and trimmed strings.

Course = Subject + Students Sets / Grade Level columns
- Relate existing Courses in RosarioSIS or create it.
- Comparison is done using lowercase and trimmed strings.
- Course "Title" is the Subject + Students Sets / Grade Level values.
- Course "Short Name" is composed of the first 4 letters + Students Sets / Grade Level value.

Course Period = FET Activity
- 1 course period per Activity ID, if Activity ID column is set.
- **Or** 1 course period per "Subject + Students Sets + Teachers (+ Room)" unique combination.
- Course Period "Short Name" is composed of the Course "Short Name" + number.

Period
- Relate existing Periods in RosarioSIS or create it.
- Comparison is done using lowercase and trimmed strings, if the Period begins with the Hour value.

Day
- Relate Days in RosarioSIS.
- Comparison is done using lowercase and trimmed strings, and translate strings if language is not English.

Teacher
- Relate existing Teachers in RosarioSIS or add user.
- Comparison is done using lowercase and trimmed strings.


## Options

- Import first row: check it if your file does not contain a header with column names.
- Delete existing Courses & Course Periods.
- Course Period options.


## Tips

Activity ID
- FET activity ID is optional. Without it Course Periods will be grouped based on subject + teacher + student sets + room unicity. This may result in _less_ course periods being created.

Seats
- You can add a column to specify Seats for each course period.

Use different set of Course Period options:
- Split your "timetable.csv" file and import course periods using different options.


## Limitations

- Does not handle multiple Teachers for the same Course Period / Activity. Only the first Teacher will be retained.
