Calculated Objects question type.

This a Moodle question type which extends the 'calculated' question type.
Teachers can create questions like "How much is {apples} + {oranges}?"
- where the {wildcards} become M and N x images of apples and oranges respectively. It is aimed at primary-school students (age 4-10).

Note, this question type uses the database tables of the 'calculated' question type.

Tested with Moodle 1.9.7.

(Author N.D.Freear, 14 August 2010.)

Currently supported wildcards:  apple, orange, pear, pineapple, walnut, coffee, cookie (each with or without an 's', eg. {cookies}).

Changes, 27 August:
1. Renamed language string file, for auto-include (from CONTRIB-2308).
2. Renamed help file, for auto-include.
3. Added 2 missing language strings.
4. Verified that styles.css is being auto-included.
4. Simplified install instructions (todo).
5. Fixed missing % modulo operator bug (CONTRIB-2308)
6. (http://moodle.org/mod/forum/discuss.php?d=156605)

To install:
1. Download and unzip the archive. Copy the directory 'calculatedobjects' into the directory {MOODLE}/question/type/ on your server.
2. Visit the administrator 'notifications' page, http://moodle.example.org/admin/ - there are no database changes for this question type.

(Note, English language strings, help file, and styles will be auto-included.)


TODO:
* Test with Moodle 2 beta.
* More testing of backup and restore.
* Evaluate ereg and preg* calls.
* Work on validation functions (qtype_calculatedobjects_find_formula_errors).
* Tidy up.
* If there's demand, add ability to use custom icons/images.
* If there's demand, translation.


Acknowledgements:
- images sourced from Wikimedia:
* http://commons.wikimedia.org/wiki/Category:Food_and_drink_icons
* http://commons.wikimedia.org/wiki/File:Source_preview_FRUITS.jpg
* http://commons.wikimedia.org/wiki/File:Tulliana_cookie.png
- (icon sourced from Iconarchive):
* http://www.iconarchive.com/show/food-icons-by-aha-soft/apple-icon.html - See license.
* http://www.iconarchive.com/icons/aha-soft/food/license.txt


[End.]
