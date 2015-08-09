Phrodo Database
===============
Database by Phrodo provides a clean and expressive API for accessing your
databases. At its core is the Connection which uses a PDO instance underneath.

```php
// Connecting is easy, just pass a PDO instance
$pdo = new PDO('mysql:host=localhost;dbname=test', 'username', 'password');
$db  = new Phrodo\Database\Connection($pdo);
```

Querying
--------
Fetching information from the database is straightforward with the ```query```
and ```select``` methods. They both return a ```Result``` object that allows
you to fetch ```row(s)``` and ```value(s)``` by calling its equally named
methods.

```php
// Get all users as associative arrays in an indexed array
$users = $db->query('SELECT * FROM users')->rows();

// Get a single user row as an associative array
$user = $db->query('SELECT * FROM users WHERE id = ?', 31)->row();

// Get the id of a user
$id = $db->query('SELECT id FROM users WHERE username = ?', 'john')->value();

// Get an array of user names
$names = $db->query('SELECT username FROM users')->values();
```

Traversing results
------------------
In most cases, when you fetch a multi-row result, you'll want to iterate over
the results row by row. This may seem trivial, but there are several ways to
go about this.

```php
// Fetching all rows before iterating over them can consume a lot of memory.
foreach ($db->query('SELECT * FROM users')->rows() as $row) {
    // Do stuff with $row
}

// By calling the row() method repeatedly until you hit null, you store only
// one row at a time in memory
$result = $db->query('SELECT * FROM users');
while ($row = $result->row()) {
    // Do stuff with $row
}

// The same can be achieved by looping over the Result directly using foreach
foreach ($db->query('SELECT * FROM users') as $row) {
    // Do stuff with $row
}

// Same thing, but this query returns only one column. This means you get the
// value instead of the entire row.
foreach ($db->query('SELECT username FROM users') as $username) {
    // Do stuff with $username
}
```

Counting
--------
To count the number of results found, use the ```count``` method.
```php
// Counting the returned result rows...
$count = $db->query('SELECT * FROM users')->count();

// Passing the result to the count function works just as well
$count = count($db->query('SELECT * FROM users'));
```

Manipulation
------------
Because non-select queries never return a result, it wouldn't make sense to
use the same result api either. Instead the number of rows affected by your
query would be nice to work with... which is exactly what the ```execute```
method gives you.

```php
// Update a user (execute returns the number of rows affected)
$rows = $db->execute('UPDATE users SET login_at = ?', new DateTime('now'));

// Delete all inactive users
$rows = $db->execute('DELETE FROM users WHERE active = 0');

// Insert a user and get the auto_increment id value
$rows = $db->execute('INSERT INTO users (username, password) VALUES (?, ?)',
                     'john', hash('secret'));
```

Notice how an insert with only 2 fields already gets quite long and unreadable.
Because these data manipulation queries are often filled with data from arrays,
it makes sense to use a specialized api for this purpose too.

Enter the ```insert```, ```update``` and ```delete``` methods. Each of these
methods takes the table name as the first argument and the data to be inserted
or updated as the next and the where clause as its last argument. While insert
automatically returns the last inserted id, the other two methods return the
number of rows affected.

```php
// Welcome John! We'll now turn you into a database record.
$id = $db->insert('users', ['username' => 'john', 'password' => $hash]);

// Update John's last login
$time = new DateTime('now');
$rows = $db->update('users', ['login_at' => $time], ['username' => 'john']);

// Delete all users that haven't logged in for a year or more
$rows = $db->delete('users', 'login_at < ?', new DateTime('-1 year'));
```

Query building
--------------
To assist in writing queries within your code, you can use the query builder.
It allows you to incrementally compose a query without having to worry about
SQL syntax and concatenation yourself.

The query builder can be retrieved by calling the ```build``` or ```select```
method. By chaining method calls, you can interactively build your query and
access the result by using the result api or convert the query to its string
representation using a typecast or its get* methods.

```php
// Look mama, without SQL!
$users = $db->select('*')->from('users')->where('active = 1')->rows();

// Or just get the SQL... this prints "SELECT * FROM users"
echo $db->select('*')->from('users');

// Complex select statements are just as easy to build
$db->select('g.*, COUNT(1) as active_users')
   ->from('users', 'u')
   ->leftJoin('users_groups', 'ug', 'u.id = ug.user_id')
   ->leftJoin('groups', 'g', 'g.id = ug.group_id')
   ->where('users.active = ?', 1)
   ->groupBy('g.id')
   ->having('COUNT(u.id) > 1)')
   ->orderBy('g.name')
   ->limit(25)
   ->rows();
   
// Mixing the order of your calls can be useful too
$query = $db->select('u.*')
            ->from('users', 'u')
            ->where('active = 1');
if ($searchGroup) {
    $query->leftJoin('users_groups', 'ug', 'u.id = ug.user_id')
          ->leftJoin('groups', 'g', 'g.id = ug.group_id')
          ->where('g.name LIKE ?', "%searchGroup%");
}

```

The ```insert```, ```update``` and ```delete``` methods also return a query
builder instance when you don't pass all their parameters.

```php
// A simple insert query
$db->insert('users')
   ->set(['username' => 'john', 'password' => $hash)]
   ->execute();

// Or an update query
$db->update('users')
   ->set(['login_at' => new DateTime('now')]
   ->where(['username' => 'john'])
   ->execute();
```

When no rows are returned or affected by the query, you can even define some
alternative behaviour:
```php
// Throw an exception when no row was found (notice the orFail)
$db->select('*')
   ->from('users')
   ->where('username = ? AND password = ?', ['john', hash('password')])
   ->orFail('Invalid username/password combo')
   ->row()

// Update a row or insert a new one (notice the orInsert)
$db->update('groups')
   ->set(['name' => 'administrators', 'allow_all' => 1])
   ->where('id = ?', 1)
   ->orInsert()
   ->execute()
```

One API to rule them all
------------------------
And there it is...

```php
    $db('INSERT INTO mount_doom VALUES (?)', 'ring');
```

The connection itself is invokable and returns the type of result you'd most
likely be interested in based on the query you've passed. So a select query
will return a result, an insert query will return the last insert id and any
other type of query will return the number of rows affected.

Transactions and locking
------------------------
If you want to run a set of database operations within a transaction, you
can use the transaction method and pass your operations as a closure. When
the closure returns, the transaction will be automatically committed. But
if an exception is thrown, the transaction will rollback itself.

```php
// When the email could not be sent, rollback the transaction
$db->transaction(function () use ($db)
{
    $db->execute('UPDATE users SET active = 0 WHERE username = ?', 'john');
    if (!mail('john@example.com', 'Your account was deactivated')) {
        throw new \RuntimeException('E-mail failure, please rollback!');
    }
});
```

In addition to Atomicity, Consistency, Isolation and Durability (ACID), the
transaction API also lets you add locks into the mix. By acquiring read
and/or write locks, you can make sure you have exclusive access to a table
for the duration of the transaction.

```php
// Run a transaction with locks
$db->transaction()
   ->withWriteLock(['users', 'groups'])
   ->withReadLock('permissions')
   ->run($closure);
```

Todo
----
* Database, table and column definition read, create, alter and drop operations
* Database migrations
* Unit testing
* Performance testing
* Compatibility testing (MySQL, sqlite, pgSQL)

Unsupported
-----------
Following features are intentionally left unsupported:
* Scrollable cursors
* Prepared statements
* Identifier escaping/quoting
