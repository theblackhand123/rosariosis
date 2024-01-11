# Reference

## Examples

You will find sample CSV and Excel files inside the `tests/` folder.

## Data Processing

Identify Student
- Relate existing Students enrolled in the Course Period based on their Student ID, or Username, or First and Last Names.
- Student ID: exact match.
- Username: Comparison is done using lowercase and trimmed strings.
- First and Last Names: Comparison is done using lowercase and trimmed strings.

Assignments
- Relate existing Assignments (for a given teacher, course period and quarter) in RosarioSIS.
- Grades contained in the related columns are processed this way: comma `,` is converted to point `.` (decimal separator), and non numeric characters are stripped. Asterisk `*` grades / characters are kept, to excuse a student.
- Existing grades are updated.

Final Grades
- Choose whether your file contains "Percent" or "Letter" grades.
- Percent: the `%` sign will be stripped, along with any non numerical characters and point `.`. Comma `,` will be converted to point `.`.
- Letter: trailing spaces are removed. Tries to match letter grades by their title (see the _Grades > Grading Scales_ program and the _Scheduling > Courses_ program for the associated scale). If no exact match is found, comma `,` will be converted to point `.`. If still no match is found, `.00`, `.0`, `,00`, `,0` are stripped.
- Existing grades are updated.
- Empty grades are skipped.

## Options

- Import first row: check it if your file does not contain a header with column names.
- Include inactive students: import grades for inactive students (school status and course status).
