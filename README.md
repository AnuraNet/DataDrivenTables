#DataDrivenTables#

##Basic usage##

Create a very simple table:

``$table = new Table("exampleTable", $db, "SELECT title, author, released FROM books", array("title", "author", "released"), array("Title", "Author", "Released on"));``

Display the table:

``$table->printTable();``

This will create a table with three columns displaying the contents from the provided columns of the `books` database table.

**Note**: The first parameter has to be a unique id on your HTML page.

**Note**: The two column arrays (the 4th and 5th arguments) have to be the same length.

###Database providers###

**TODO**

##Constructor arguments##

The constructor parameters are as follows: ``($id, $db, $sqlQuery, $sqlArray, $nameArray, $emptyMsg = "", $rowsPerPage = -1, $type = "")``

###The `id` argument###
The `id` is a string that has to be a unique id on your HTML page. It is used to identify the table and to support multiple _DataDrivenTables_ on the same page.

###The `db` argument###
The `db` has to be a valid and initialized `DatabaseProvider`.

###The `sqlQuery` argument###

The sql query used to retrieve the data.

To provide arguments in the query, you have provide a tuple with the query and the argument values.

The syntax is similar to prepared statements.

Example:
``array("SELECT title, author, released FROM books WHERE released > ?", array(time()));``

###The `sqlArray` argument###

This identifies the column names which result in columns of the HTML table.

###The `nameArray` argument###

This array contains the display names of the table columns.

_Please note that the length of this array must be equal to the `sqlArray`!_

###The `emptyMsg` argument###

**Default**: `""`

This message is shown when the query returned an empty response.

###The `rowsPerPage` argument###

**Default**: `-1`

How many rows should be shown on one page. The page switching is completely handled by the JavaScript code of _DataDrivenTables_.

To disable pages, use the default value of `-1`.

###The `type` argument###

**Default**: `""`

This argument allows to give a custom CSS class which will be applied to the table.

##More possibilites: Extending the `Table` class##

###Column methods###

####Action method####

If a method called `printAction` is declared in the extending class it will be called after each row's content was printed.
The returned content will be printed in an additional column after the content columns.

The `printAction` method will receive the content of the "action column" (default: _"id"_, defined in the `actionKey` as explained below),
the complete associative array for the current column and the total row count as parameters.

####Column named methods####
If a method exists in the extending class, which name is equal to a sql column (contained in the `sqlArray`), it is always executed
for this specific table cell. The parameters are the value of the current cell, the complete associative array for the current column, the current row,
the current page and the total row count.

###Protected attributes###

**Note**: Most of the constructor arguments are applied directly to their respective attributes and won't be explained here.

- `$actionKey`: This key identifies the column of which the value is given to `printAction` method explained above.
(Please note that the given column doesn't necessarily have to be in the `sqlArray`, but must be contained in the sql query)

- `$timestampFormat`: Table columns containing the string "timestamp" are automatically formatted according to this timestamp format.
To disable this behaviour set `$timestampFormat` to `NULL`.

- `$countQueryTemplate`: To determine the amount of pages, the number of rows matching the given query have to determined.
In the default template (``SELECT COUNT(*) as rowCount FROM ($1) count``) `$1` is replaced with the sql query provided in the constructor.
The count query can be overwritten to improve performance.

- `$countArgs`: This is an array containing the arguments which will be provided to the count query mentioned above.
If this argument is not `NULL` (default) and the `$countQueryTemplate` still contains `$1`, the arguments given in the `sqlQuery` array will be used instead.

- `$additionalScriptParameters`: This associative array has to be filled with GET parameters which are needed for the ajax requests to reach the PHP page where the table is constructed.
