17/7/2010
    * use Moodle forms

28/8/2009:
    * Millionaire: Export to html

26/8/2008:
    * Hangman: Export to html    
    
8/8/08: 
    * Fix: Millionaire: When a question has background or foregroundcolor doesn't set the background/foreground color
7/9/08
   * New: Include subcategories of questions 
   * Fix: Hiddenpicture: Problem when exist \ in dirroot, dataroot

20/8/08
    * New: translation to Dutch

21/7/08
    * New: Ability to export hangman to javame for use in mobile phones
    
17/7/08
    * Warnings on sudoku in multiple choice questions
    
12/07/08 
    * No cellpadding at parameters screen 
    
11/07/08
    * Problem when the language of hangman words is different from current language
    * Auto detects the language of words for the game hangman    
    
28/06/08
    * Removes greeks from English translation
    * CONTRIB-427 : At the end of game "the hidden picture" doesn't show the hidden picture
    * CONTRIB-426 : The first time doesn't show the dice
    * CONTRIB-446 : When an answer is wrong in millionaire game, the correct answer shown by the system is also wrong

ver 1.6.8
    * Fix: Show grades only to students
    * New: Translation to Basque (thanks to Juan Ezeiza)
    
ver 1.6.6
    * Fixes some warning with moodle 1.9
    * New: Hiddenpicture: The width of picture is not 500 but the original size
    * New: Hiddenpicture: You can set the width or height of the picture
    * Fix: In cross removes the <p> on the start of question and </p> on the end
    
ver 1.6.4
    * Fixes a problem in "Hidden pictures": Showed the main question two times
    * Fix: Added the addslashes before inserting to game_queries
    * New: Shows a betters message when can't find a glossaryentry with a attached picture
    * New: Added Rusian translation (thanks to )
    * New: A presentation of moodle (thanks to Christopher Pappas) is available from here

ver 1.6.1
    * Fixes a problem in hangman: Not show the first letter when you select the appropriate parameter
    * The default layout for crossword must be the layout with question at the bottom

ver 1.6
    *  Includes a new game named "The hidden picture" (An idea of Johnathan Kemp)
    * In hangman,crossword,cryptex you can use the space as a character (there is parameter) (An idea of morgan toumelin)
    * In hangman,crossword,cryptex you can use the - as a character (there is parameter) (An idea of morgan toumelin)
    * There is a second layout for the crossword (An idea of Fernando Oliveira)
    * You can set the bottom text of each game in a little different way
    * Fixes a problem with question categories in version 1.9 of moodle

ver 1.4

Interface:

    * The interface is like a quiz. The student plays games and teacher can see the grades
    * You can set a text that will be visible at the bottom of the game. In this way will be a picture at the bottom and a crossword with questions about the picture
    * You can use pictures inside questions


Restrictions:

    * You can only backup/restore the data of game not the user attempts. (not works backup now)
    * In the report overview you can see only what students said for questions not for glossaryentries


Upgrade

    * Delete the files from mod/game
    * Copy the new files to mod/game

