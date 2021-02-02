# Neat Database components

[![Stable Version](https://poser.pugx.org/neat/database/version)](https://packagist.org/packages/neat/database)
[![Build Status](https://travis-ci.org/neat-php/database.svg?branch=master)](https://travis-ci.org/neat-php/database)

Neat database components provide a clean and expressive API for accessing your
databases. At its core is the Connection which uses a PDO instance underneath.

## Getting started

To install this package, simply issue [composer](https://getcomposer.org) on the
command line:
```
composer require neat/database
```

Then create a new database connection
```php
<?php

// Connecting is easy, just pass a PDO instance
$pdo = new PDO('mysql:host=localhost;dbname=test', 'username', 'password');
$db  = new Neat\Database\Connection($pdo);
```

## Querying

Fetching information from the database is straightforward with the ```query```
and ```select``` methods. They both return a ```Result``` object that allows
you to fetch ```row(s)``` and ```value(s)``` by calling its equally named
methods.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// Get all users as associative arrays in an indexed array
$users = $db->query('SELECT * FROM users')->rows();

// Get a single user row as an associative array
$user = $db->query('SELECT * FROM users WHERE id = ?', 31)->row();

// Get the id of a user
$id = $db->query('SELECT id FROM users WHERE username = ?', 'john')->value();

// Get an array of user names
$names = $db->query('SELECT username FROM users')->values();
```

## Traversing results

In most cases, when you fetch a multi-row result, you'll want to iterate over
the results row by row. This may seem trivial, but there are several ways to
go about this.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// Fetching all rows before iterating over them can consume a lot of memory.
foreach ($db->query('SELECT * FROM users')->rows() as $row) {
    var_dump($row);
}

// By calling the row() method repeatedly until you hit false, you store only
// one row at a time in memory
$result = $db->query('SELECT * FROM users');
while ($row = $result->row()) {
    var_dump($row);
}

// The same can be achieved by looping over the Result directly using foreach
foreach ($db->query('SELECT * FROM users') as $row) {
    var_dump($row);
}
```

## Fetched results

To use a result multiple times (for example to count its rows and then return
all the rows), you'll need to ```fetch``` the result first. A live ```query```
result wouldn't allow you to do this:
```php
$db = new Neat\Database\Connection(new PDO('...'));

// Get the fetched result first
$result = $db->fetch('SELECT * FROM users');

$count = $result->count();
$users = $result->rows();
```

## Manipulation

Because non-select queries never return a result, it wouldn't make sense to
use the same result api either. Instead, the number of rows affected by your
query would be nice to work with... which is exactly what the ```execute```
method gives you.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// Update a user (execute returns the number of rows affected)
$rows = $db->execute('UPDATE users SET login_at = ?', new DateTime('now'));

// Delete all inactive users
$rows = $db->execute('DELETE FROM users WHERE active = 0');

// Insert a user and get the auto_increment id value
$rows = $db->execute('INSERT INTO users (username, password) VALUES (?, ?)',
                     'john', password_hash('secret', PASSWORD_DEFAULT));
```

Notice how an insert with only 2 fields already gets quite long and unreadable.
Because these data manipulation queries are often filled with data from arrays,
it makes sense to use a specialized api for this purpose too.

Enter the ```insert```, ```update``` and ```delete``` methods. Like the
```execute``` method, these methods also return the number of rows affected.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// Welcome John! We'll now turn you into a database record.
if ($db->insert('users', ['username' => 'john', 'password' => '...'])) {
    $id = $db->insertedId();
}

// Update John's last login
$time = new DateTime('now');
$rows = $db->update('users', ['login_at' => $time], ['username' => 'john']);

// Delete all users that haven't logged in for a year or more
$rows = $db->delete('users', 'login_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)');
```

Please note that the insert method does NOT return the last inserted id,
instead you'll have to use the ```insertedId``` method.

## Query building

To assist in writing queries within your code, you can use the query builder.
It allows you to incrementally compose a query without having to worry about
SQL syntax and concatenation yourself.

The query builder can be retrieved by calling the ```build``` or ```select```
method. By chaining method calls, you can interactively build your query and
access the result by using the result api or convert the query to its string
representation using a typecast or its get* methods.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// Look mama, without SQL!
$users = $db->select()->from('users')->where('active = 1')->query()->rows();

// Or just get the SQL... this prints "SELECT * FROM users"
echo $db->select()->from('users');

// Complex select statements are just as easy to build
$db->select('g.*, COUNT(1) as active_users')
   ->from('users', 'u')
   ->leftJoin('users_groups', 'ug', 'u.id = ug.user_id')
   ->leftJoin('groups', 'g', 'g.id = ug.group_id')
   ->where('users.active = ?', 1)
   ->groupBy('g.id')
   ->having('COUNT(u.id) > 1')
   ->orderBy('g.name')
   ->limit(25)
   ->query()
   ->rows();
   
// Mixing the order of your calls can be useful too
$query = $db->select('u.*')
            ->from('users', 'u')
            ->where('active = 1');
if (isset($searchGroup)) {
    $query->join('users_groups', 'ug', 'u.id = ug.user_id')
          ->join('groups', 'g', 'g.id = ug.group_id')
          ->where('g.name LIKE ?', "%$searchGroup%");
}
```

The ```insert```, ```update``` and ```delete``` methods also return a query
builder instance when you don't pass all their parameters.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// A simple insert query
$db->insert('users')
   ->values(['username' => 'john', 'password' => '...'])
   ->execute();

// Or an update query
$db->update('users')
   ->set(['login_at' => new DateTime('now')])
   ->where(['username' => 'john'])
   ->execute();
```

## Escaping and quoting

When the built-in query builder and placeholder substitution simply don't cut
it anymore, you'll most likely end up concatenating your own SQL queries. The
```quote``` and ```quoteIdentifier``` methods allow you to safely embed literal
values and identifiers into your own SQL statements.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// First escape and quote the user input into an SQL safe string literal
$quoted = $db->quote('%' . $_GET['search'] . '%');
$sql = "SELECT * FROM users WHERE lastname LIKE $quoted OR firstname LIKE $quoted";

// It also understands DateTime value objects
$date = $db->quote(new DateTime('last monday'));
$sql = "SELECT * FORM users WHERE login_at > $date";

// And NULL values (be sure to use the appropriate SQL syntax, eg IS instead of =)
$null = $db->quote(null); // store 'NULL' into $null

// Identifiers can also be quoted
$table = $db->quoteIdentifier('users'); // store '`users`' (note the backticks) into $table 
$sql = "SELECT * FROM $table";
```

## Transactions and locking

If you want to run a set of database operations within a transaction, you
can use the transaction method and pass your operations as a closure. When
the closure returns, the transaction will be automatically committed. But
if an exception is thrown, the transaction will rollback itself.

```php
$db = new Neat\Database\Connection(new PDO('...'));

// When the email could not be sent, rollback the transaction
$db->transaction(function () use ($db)
{
    $db->execute('UPDATE users SET active = 0 WHERE username = ?', 'john');
    if (!mail('john@example.com', 'Confirmation', 'Account terminated')) {
        throw new \RuntimeException('E-mail failure, please rollback!');
    }
});
```
