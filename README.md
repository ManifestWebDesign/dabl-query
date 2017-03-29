[![Build Status](https://travis-ci.org/ManifestWebDesign/dabl-query.svg?branch=master)](https://travis-ci.org/ManifestWebDesign/dabl-query)

# DABL Query
Lightweight object-oriented SQL query builder

## Example

code:
```php
use Dabl\Query\Query;
use Dabl\Adapter\DABLPDO;

$q = Query::create('my_table')
    ->leftJoin('my_table.id', 'other_table.my_table_id')
    ->add('my_column', 'some value')
    ->orGreater('another_column', 5)
    ->groupBy('other_table.id')
    ->orderBy('my_table.name', Query::DESC);

echo "$q";

$pdo = DABLPDO::connect(array(
    'driver' => 'mysql',
    'host' => 'localhost',
    'dbname' => 'test',
    'user' => 'root',
    'password' => ''
));

$q->getQuery($pdo)->bindAndExecute();
```

output:
```sql
SELECT my_table.*
FROM my_table
	LEFT JOIN other_table ON (my_table.id = other_table.my_table_id)
WHERE 
	my_column = 'some value'
	OR another_column > 5
GROUP BY other_table.id
ORDER BY my_table.name DESC
```

## Features

* Nested conditions in WHERE and HAVING clauses
* Subqueries
* Joins
