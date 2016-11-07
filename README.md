# easySQL
mySQL/mySQLi wrapper for PHP

(still missing config file, I'll fix that later.)

Any Code in this respository is subject to [My1's Open Source License](License.md)

## How to use this?

### Starting off

first you need to include the easySQL.php which for itself includes the config.php with all the config stuff.

The class decides by itself whether to use mysql or mysqli functions (not perfect yet) by what the config says and whether the mysql functions even exist (they were removed in PHP7)

### Connecting

Then you can connect to the database by calling
```php
esql::dbc("link",$database,$user,$pass,$host,$debug);
```
with everything except a link identifier as a string being optional and pulled from the config.

The link Identifier is needed because the MySQL connection ressources are stored in a assoc array with the link identifier being the index.

debug is a boolean for giving out a lot of debug info, it's generally not recommended to turn it on outside of testing.

### Queries

then you can do quite a bunch of different stuff, most important probably being the queries, where the syntax is slightly different depending on what you do, but in general you have the following:

```php
esql::dbq("link",$action,$col,$table,$filter,$debug);
```
filter and debug generally being optional.

action being what you want to do (select, insert, update or delete). FOr Security measure the use of update and delete doesnt work without a filter. if you REALLY want to update or delete everything just put a `"1"` into the filter which essentially works like an `if (1==true)` kind of check.

for insert the filter acts as place where you put the values in. (I maybe need a better name for that variable)

the filter value also contains the sort/group so for that matter you just say `"1 sort by something group by something_else"`
for deleting you just leave the col value empty.

example expressions:
```php
$res=esql::dbq("link","select","uid,username,pass","users","admin=1");
esql::dbq("link","insert","username,pass","users",'"My1","password"');
esql::dbq("link","update",'pass="Pa$$w0rd"',"users",'username="My1"');
esql::dbq("link","delete",'',"users",'username="tester"');
```

### Doing PHP Stuff

so while the queries do directly mess with the DB, now we get to the more PHP-y stuff.

#### doing stuff with rows

```php
esql::num($res); //counts the rows of the result given by a query (dbq)
esql::affrows($link); //throws back number of affected rows by update, insert or delete
seek($res,$row); //moves the pointer around
esql::frow($res); //throws back the row the pointer sits on as a numbered array and moves the pointer forward. nice for a row-by-row loop.
esql::farray($res,$style); //throwsback the row the pointer sits on as an array of your choice and moves the pointer forward. nice for a row-by-row loop.
esql::iid($link); //throws back the ID (priamry index) of the last inserted row. helpful for example to get the ID of the newly added user without having to search.
```

#### pinpointing your result

one of the main reasons why I dont like MySQLi is that they kicked the `mysql_result()` function away, with no direct replacement, and to make it easier I added that function as a part of easySQL too. the only problem is that in mysqli it adjusts the row pointer and does a fetch row tho get the result, meaning you have to reset the row pointer if you want to fetch rows or whatever.

```php
dbres($res,$row,$col);
```
row and col both default to zero meaning if your mysql result already is set up in a way that you only need the first value anyway you can leave both of them out.

## Compatibility between easySQL and easySQLi

I made this project to achieve the best compatibility for use of database statements for both old and new PHP versions, and while I have an almost perfect feature parity, not everything can run perfectly between both, most notably the pointer set in the result function (although I plan to change it to do it with fetch_all as the background soon enough so that issue vanishes)

if someone wonders about all the empty lines, I even tried to achieve line-parity where you can have both files open with synced scrolling and the same functions on both files.
