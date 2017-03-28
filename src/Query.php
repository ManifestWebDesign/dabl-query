<?php

/**
 * @link https://github.com/ManifestWebDesign/DABL
 * @link http://manifestwebdesign.com/redmine/projects/dabl
 * @author Manifest Web Design
 * @license    MIT License
 */

namespace Dabl\Query;
use Dabl\Adapter\DBMSSQL;
use RuntimeException;
use PDO;

/**
 * Used to build query strings using OOP
 */
class Query {
	const ACTION_COUNT = 'COUNT';
	const ACTION_DELETE = 'DELETE';
	const ACTION_SELECT = 'SELECT';
	const ACTION_UPDATE = 'UPDATE';

	// Comparison types
	const EQUAL = '=';
	const NOT_EQUAL = '<>';
	const ALT_NOT_EQUAL = '!=';
	const GREATER_THAN = '>';
	const LESS_THAN = '<';
	const GREATER_EQUAL = '>=';
	const LESS_EQUAL = '<=';
	const LIKE = 'LIKE';
	const BEGINS_WITH = 'BEGINS_WITH';
	const ENDS_WITH = 'ENDS_WITH';
	const CONTAINS = 'CONTAINS';
	const NOT_LIKE = 'NOT LIKE';
	const CUSTOM = 'CUSTOM';
	const DISTINCT = 'DISTINCT';
	const IN = 'IN';
	const NOT_IN = 'NOT IN';
	const ALL = 'ALL';
	const IS_NULL = 'IS NULL';
	const IS_NOT_NULL = 'IS NOT NULL';
	const BETWEEN = 'BETWEEN';
	const NOOP = null;

	// Comparison type for update
	const CUSTOM_EQUAL = 'CUSTOM_EQUAL';

	// PostgreSQL comparison types
	const ILIKE = 'ILIKE';
	const NOT_ILIKE = 'NOT ILIKE';

	// JOIN TYPES
	const JOIN = 'JOIN';
	const LEFT_JOIN = 'LEFT JOIN';
	const RIGHT_JOIN = 'RIGHT JOIN';
	const INNER_JOIN = 'INNER JOIN';
	const OUTER_JOIN = 'OUTER JOIN';

	// Binary AND
	const BINARY_AND = '&';

	// Binary OR
	const BINARY_OR = '|';

	static $operators = array(
		self::EQUAL,
		self::NOT_EQUAL,
		self::ALT_NOT_EQUAL,
		self::GREATER_THAN,
		self::LESS_THAN,
		self::GREATER_EQUAL,
		self::LESS_EQUAL,
		self::LIKE,
		self::BEGINS_WITH,
		self::ENDS_WITH,
		self::CONTAINS,
		self::NOT_LIKE,
		self::IN,
		self::NOT_IN,
		self::ALL,
		self::IS_NULL,
		self::IS_NOT_NULL,
		self::BETWEEN,
		self::ILIKE,
		self::NOT_ILIKE,
		self::BINARY_AND,
		self::BINARY_OR
	);

	// 'Order by' qualifiers
	const ASC = 'ASC';
	const DESC = 'DESC';

	protected $action = self::ACTION_SELECT;

	/**
	 * @var array
	 */
	protected $columns = array();

	/**
	 * @var mixed
	 */
	protected $table;

	/**
	 * @var string
	 */
	protected $tableAlias;

	/**
	 * @var array
	 */
	protected $extraTables = array();

	/**
	 * @var QueryJoin[]
	 */
	protected $joins = array();

	/**
	 * @var Condition
	 */
	protected $where;

	/**
	 * @var array
	 */
	protected $orders = array();

	/**
	 * @var array
	 */
	protected $groups = array();

	/**
	 * @var Condition
	 */
	protected $having;

	/**
	 * @var int
	 */
	protected $limit;

	/**
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * @var bool
	 */
	protected $distinct = false;

	/**
	 * @var array
	 */
	protected $updateColumnValues;

	/**
	 * Creates new instance of Query, parameters will be passed to the
	 * setTable() method.
	 * @return Query
	 * @param $table_name Mixed[optional]
	 * @param $alias String[optional]
	 */
	function __construct($table_name = null, $alias = null) {
		$this->setWhere(new Condition);
		$this->setTable($table_name, $alias);
		return $this;
	}

	function __clone() {
		if ($this->where instanceof Condition) {
			$this->where = clone $this->where;
		}
		if ($this->having instanceof Condition) {
			$this->having = clone $this->having;
		}
		foreach ($this->joins as $key => $join) {
			$this->joins[$key] = clone $join;
		}
	}

	/**
	 * Returns new instance of self by passing arguments directly to constructor.
	 * @param mixed $table_name
	 * @param string $alias
	 * @return Query
	 */
	static function create($table_name = null, $alias = null) {
		return new self($table_name, $alias);
	}

	/**
	 * Specify whether to select only distinct rows
	 * @param Bool $bool
	 */
	function setDistinct($bool = true) {
		$this->distinct = (bool) $bool;
		return $this;
	}

	/**
	 * Sets the action of the query.  Should be SELECT, DELETE, or COUNT.
	 * @return Query
	 * @param $action String
	 */
	function setAction($action) {
		$this->action = strtoupper($action);
		return $this;
	}

	/**
	 * Returns the action of the query.  Should be SELECT, DELETE, or COUNT.
	 * @return String
	 */
	function getAction() {
		return $this->action;
	}

	/**
	 * Add a column to the list of columns to select.  If unused, defaults to *.
	 *
	 * {@example libraries/dabl/database/query/Query_addColumn.php}
	 *
	 * @param String $column_name
	 * @return Query
	 */
	function addColumn($column_name, $alias = null) {
		if ($alias === null || $alias === '') {
			$alias = $column_name;
		}
		$this->columns[$alias] = $column_name;
		return $this;
	}

	/**
	 * Set array of strings of columns to be selected
	 * @param array $columns_array
	 * @return Query
	 */
	function setColumns($columns_array) {
		$this->columns = array();
		foreach ($columns_array as $alias => &$column) {
			$this->addColumn($column, is_int($alias) ? null : $alias);
		}
		return $this;
	}

	/**
	 * Return array of columns to be selected
	 * @return array
	 */
	function getColumns() {
		return $this->columns;
	}

	/**
	 * Set array of strings of groups to be selected
	 * @param array $groups_array
	 * @return Query
	 */
	function setGroups($groups_array) {
		$this->groups = $groups_array;
		return $this;
	}

	/**
	 * Return array of groups to be selected
	 * @return array
	 */
	function getGroups() {
		return $this->groups;
	}

	/**
	 * Sets the table to be queried. This can be a string table name
	 * or an instance of Query if you would like to nest queries.
	 * This function also supports arbitrary SQL.
	 *
	 * @param String|Query $table_name Name of the table to add, or sub-Query
	 * @param String[optional] $alias Alias for the table
	 * @return Query
	 */
	function setTable($table_name, $alias = null) {
		if ($table_name instanceof Query) {
			if (!$alias) {
				throw new RuntimeException('The nested query must have an alias.');
			}
			$table_name = clone $table_name;
		} elseif (null === $alias) {
			$space = strrpos($table_name, ' ');
			$as = strrpos(strtoupper($table_name), ' AS ');
			if ($as != $space - 3) {
				$as = false;
			}
			if ($space) {
				$alias = trim(substr($table_name, $space + 1));
				$table_name = trim(substr($table_name, 0, $as === false ? $space : $as));
			}
		}

		if ($alias) {
			$this->setAlias($alias);
		}

		$this->table = $table_name;
		return $this;
	}

	/**
	 * Returns a String representation of the table being queried,
	 * NOT including its alias.
	 *
	 * @return String
	 */
	function getTable() {
		return $this->table;
	}

	function setAlias($alias) {
		$this->tableAlias = $alias;
		return $this;
	}

	/**
	 * Returns a String of the alias of the table being queried,
	 * if present.
	 *
	 * @return String
	 */
	function getAlias() {
		return $this->tableAlias;
	}

	/**
	 * @param type $table_name
	 * @param type $alias
	 * @return Query
	 * @throws RuntimeException
	 */
	function addTable($table_name, $alias = null) {
		if ($table_name instanceof Query) {
			if (!$alias) {
				throw new RuntimeException('The nested query must have an alias.');
			}
			$table_name = clone $table_name;
		} elseif (null === $alias) {
			// find the last space in the string
			$space = strrpos($table_name, ' ');
			if ($space) {
				$table_name = substr($table_name, 0, $space + 1);
				$alias = substr($table_name, $space);
			} else {
				$alias = $table_name;
			}
		}

		$this->extraTables[$alias] = $table_name;
		return $this;
	}

	/**
	 * Provide the Condition object to generate the WHERE clause of
	 * the query.
	 *
	 * @param Condition $w
	 * @return Query
	 */
	function setWhere(Condition $w) {
		$this->where = $w;
		return $this;
	}

	/**
	 * Returns the Condition object that generates the WHERE clause
	 * of the query.
	 *
	 * @return Condition
	 */
	function getWhere() {
		return $this->where;
	}

	/**
	 * Add a JOIN to the query.
	 *
	 * @todo Support the ON clause being NULL correctly
	 * @param string|Query $table_or_column Table to join on
	 * @param string|Condition $on_clause_or_column ON clause to join with
	 * @param string $join_type Type of JOIN to perform
	 * @return Query
	 */
	function addJoin($table_or_column, $on_clause_or_column = null, $join_type = self::JOIN) {
		if ($table_or_column instanceof QueryJoin) {
			$this->joins[] = clone $table_or_column;
			return $this;
		}

		if (null === $on_clause_or_column) {
			if ($join_type == self::JOIN || $join_type == self::INNER_JOIN) {
				$this->addTable($table_or_column);
				return $this;
			}
			$on_clause_or_column = '1 = 1';
		}

		$this->joins[] = new QueryJoin($table_or_column, $on_clause_or_column, $join_type);
		return $this;
	}

	/**
	 * Alias of {@link addJoin()}.
	 * @param $table_or_column
	 * @param null $on_clause_or_column
	 * @param string $join_type
	 * @return Query
	 */
	function join($table_or_column, $on_clause_or_column = null, $join_type = self::JOIN) {
		return $this->addJoin($table_or_column, $on_clause_or_column, $join_type);
	}

	/**
	 *
	 * @param string $table_or_column
	 */
	function crossJoin($table_or_column) {
		$this->addJoin($table_or_column);
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function innerJoin($table_or_column, $on_clause_or_column = null) {
		return $this->addJoin($table_or_column, $on_clause_or_column, self::INNER_JOIN);
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function leftJoin($table_or_column, $on_clause_or_column = null) {
		return $this->addJoin($table_or_column, $on_clause_or_column, self::LEFT_JOIN);
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function rightJoin($table_or_column, $on_clause_or_column = null) {
		return $this->addJoin($table_or_column, $on_clause_or_column, self::RIGHT_JOIN);
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function outerJoin($table_or_column, $on_clause_or_column = null) {
		return $this->addJoin($table_or_column, $on_clause_or_column, self::OUTER_JOIN);
	}

	/**
	 * Add a join after checking to see if Query already has a join for the
	 * same table and alias.  Similar to php's include_once
	 * @param QueryJoin $table_or_column
	 * @param string $on_clause_or_column
	 * @param string $join_type
	 * @return Query
	 */
	function joinOnce($table_or_column, $on_clause_or_column = null, $join_type = self::JOIN) {
		if ($table_or_column instanceof QueryJoin) {
			$join = clone $table_or_column;
		} else {
			if (null === $on_clause_or_column) {
				if ($join_type == self::JOIN || $join_type == self::INNER_JOIN) {
					foreach ($this->extraTables as &$table) {
						if ($table == $table_or_column) {
							return $this;
						}
					}
					$this->addTable($table_or_column);
					return $this;
				}
				$on_clause_or_column = '1 = 1';
			}
			$join = new QueryJoin($table_or_column, $on_clause_or_column, $join_type);
		}
		foreach ($this->joins as &$existing_join) {
			if ($join->getTable() == $existing_join->getTable()) {
				if ($join->getAlias() != $existing_join->getAlias()) {
					// tables match, but aliases don't match
					continue;
				}
				// tables match, and aliases match or there are no aliases
				return $this;
			}
		}
		$this->joins[] = $join;
		return $this;
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function leftJoinOnce($table_or_column, $on_clause_or_column = null) {
		return $this->joinOnce($table_or_column, $on_clause_or_column, self::LEFT_JOIN);
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function rightJoinOnce($table_or_column, $on_clause_or_column = null) {
		return $this->joinOnce($table_or_column, $on_clause_or_column, self::RIGHT_JOIN);
	}

	/**
	 * @param mixed $table_or_column
	 * @param mixed $on_clause_or_column
	 * @return Query
	 */
	function outerJoinOnce($table_or_column, $on_clause_or_column = null) {
		return $this->joinOnce($table_or_column, $on_clause_or_column, self::OUTER_JOIN);
	}

	/**
	 * @return QueryJoin[]
	 */
	function getJoins() {
		return $this->joins;
	}

	/**
	 * @param array $joins
	 * @return Query
	 */
	function setJoins($joins) {
		$this->joins = $joins;
		return $this;
	}

	/**
	 * Shortcut to adding an AND statement to the Query's WHERE Condition.
	 * @return Query
	 * @param $column Mixed
	 * @param $value Mixed[optional]
	 * @param $operator String[optional]
	 * @param $quote Int[optional]
	 */
	function addAnd($column, $value = null, $operator = self::EQUAL, $quote = null) {
		if (func_num_args() === 1) {
			$this->where->addAnd($column);
		} else {
			$this->where->addAnd($column, $value, $operator, $quote);
		}
		return $this;
	}

	/**
	 * Alias of {@link addAnd()}
	 * @return Query
	 */
	function add($column, $value = null, $operator = self::EQUAL, $quote = null) {
		if (func_num_args() === 1) {
			return $this->addAnd($column);
		} else {
			return $this->addAnd($column, $value, $operator, $quote);
		}
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andNot($column, $value) {
		$this->where->andNot($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andLike($column, $value) {
		$this->where->andLike($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andNotLike($column, $value) {
		$this->where->andNotLike($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andGreater($column, $value) {
		$this->where->andGreater($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andGreaterEqual($column, $value) {
		$this->where->andGreaterEqual($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andLess($column, $value) {
		$this->where->andLess($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andLessEqual($column, $value) {
		$this->where->andLessEqual($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @return Query
	 */
	function andNull($column) {
		$this->where->andNull($column);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @return Query
	 */
	function andNotNull($column) {
		$this->where->andNotNull($column);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $from
	 * @param mixed $to
	 * @return Query
	 */
	function andBetween($column, $from, $to) {
		$this->where->andBetween($column, $from, $to);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andBeginsWith($column, $value) {
		$this->where->andBeginsWith($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andEndsWith($column, $value) {
		$this->where->andEndsWith($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function andContains($column, $value) {
		$this->where->andContains($column, $value);
		return $this;
	}

	/**
	 * Shortcut to adding an OR statement to the Query's WHERE Condition.
	 * @return Query
	 * @param $column Mixed
	 * @param $value Mixed[optional]
	 * @param $operator String[optional]
	 * @param $quote Int[optional]
	 */
	function addOr($column, $value = null, $operator = self::EQUAL, $quote = null) {
		if (func_num_args() === 1) {
			$this->where->addOr($column);
		} else {
			$this->where->addOr($column, $value, $operator, $quote);
		}
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orNot($column, $value) {
		$this->where->orNot($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orLike($column, $value) {
		$this->where->orLike($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orNotLike($column, $value) {
		$this->where->orNotLike($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orGreater($column, $value) {
		$this->where->orGreater($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orGreaterEqual($column, $value) {
		$this->where->orGreaterEqual($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orLess($column, $value) {
		$this->where->orLess($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orLessEqual($column, $value) {
		$this->where->orLessEqual($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @return Query
	 */
	function orNull($column) {
		$this->where->orNull($column);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @return Query
	 */
	function orNotNull($column) {
		$this->where->orNotNull($column);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $from
	 * @param mixed $to
	 * @return Query
	 */
	function orBetween($column, $from, $to) {
		$this->where->orBetween($column, $from, $to);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orBeginsWith($column, $value) {
		$this->where->orBeginsWith($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orEndsWith($column, $value) {
		$this->where->orEndsWith($column, $value);
		return $this;
	}

	/**
	 * @param mixed $column
	 * @param mixed $value
	 * @return Query
	 */
	function orContains($column, $value) {
		$this->where->orContains($column, $value);
		return $this;
	}

	/**
	 * Shortcut to addGroup() method
	 * @return Query
	 */
	function groupBy($column) {
		$this->groups[] = $column;
		return $this;
	}

	/**
	 * Shortcut to addGroup() method
	 * @return Query
	 */
	final function group($column) {
		return $this->groupBy($column);
	}

	/**
	 * Adds a clolumn to GROUP BY
	 * @return Query
	 * @param $column String
	 */
	final function addGroup($column) {
		return $this->groupBy($column);
	}

	/**
	 * Provide the Condition object to generate the HAVING clause of the query
	 * @return Query
	 * @param $w Condition
	 */
	function setHaving(Condition $where) {
		$this->having = $where;
		return $this;
	}

	/**
	 * Returns the Condition object that generates the HAVING clause of the query
	 * @return Condition
	 */
	function getHaving() {
		return $this->having;
	}

	/**
	 * Shortcut for addOrder()
	 * @return Query
	 */
	function orderBy($column, $dir = null) {
		if (null !== $dir && '' !== $dir) {
			$dir = strtoupper($dir);
			if ($dir !== self::ASC && $dir !== self::DESC) {
				throw new RuntimeException("$dir is not a valid sorting direction.");
			}
			$column .= ' ' . $dir;
		}
		$this->orders[] = trim($column);
		return $this;
	}

	function removeOrderBys() {
		$this->orders = array();
		return $this;
	}

	/**
	 * Shortcut for addOrder()
	 * @return Query
	 */
	final function order($column, $dir = null) {
		return $this->orderBy($column, $dir);
	}

	/**
	 * Adds a column to ORDER BY in the form of "COLUMN DIRECTION"
	 * @return Query
	 * @param $column String
	 */
	final function addOrder($column, $dir = null) {
		return $this->orderBy($column, $dir);
	}

	/**
	 * Returns all ORDER BY columns as strings in the form of "COLUMN DIRECTION"
	 * @return Array
	 */
	function getOrders() {
		return $this->orders;
	}

	/**
	 * Sets the limit of rows that can be returned
	 * @return Query
	 * @param $limit Int
	 */
	function setLimit($limit) {
		if (null !== $limit) {
			$limit = (int) $limit;
		}
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Returns the LIMIT integer for this Query, if it has one
	 * @return int
	 */
	function getLimit() {
		return $this->limit;
	}

	/**
	 * Sets the offset for the rows returned.  Used to build
	 * the LIMIT part of the query.
	 * @return Query
	 * @param $offset Int
	 */
	function setOffset($offset) {
		$this->offset = (int) $offset;
		return $this;
	}

	/**
	 * @return int
	 */
	function getOffset() {
		return $this->offset;
	}

	/**
	 * Builds and returns the query string
	 *
	 * @param mixed $conn Database connection to use
	 * @return QueryStatement
	 */
	function getQuery(PDO $conn = null) {
		if (null === $conn && class_exists('DBManager')) {
			$conn = DBManager::getConnection();
		}

		// the QueryStatement for the Query
		$stmnt = new QueryStatement($conn);

		// the string $statement will use
		$qry_s = '';

		$action = $this->action;

		switch ($action) {
			default:
			case self::ACTION_COUNT:
			case self::ACTION_SELECT:
				$columns_stmnt = $this->getColumnsClause($conn);
				$stmnt->addIdentifiers($columns_stmnt->identifiers);
				$stmnt->addParams($columns_stmnt->params);
				$qry_s .= 'SELECT ' . $columns_stmnt->string . "\nFROM ";
				break;
			case self::ACTION_DELETE:
				$qry_s .= "DELETE\nFROM ";
				break;
			case self::ACTION_UPDATE:
				$qry_s .= "UPDATE\n";
				break;
		}

		$table_stmnt = $this->getTablesClause($conn);
		$stmnt->addIdentifiers($table_stmnt->identifiers);
		$stmnt->addParams($table_stmnt->params);
		$qry_s .= $table_stmnt->string;

		if ($this->joins) {
			foreach ($this->joins as $join) {
				$join_stmnt = $join->getQueryStatement($conn);
				$qry_s .= "\n\t" . $join_stmnt->string;
				$stmnt->addParams($join_stmnt->params);
				$stmnt->addIdentifiers($join_stmnt->identifiers);
			}
		}

		if (self::ACTION_UPDATE === $action) {
			if (empty($this->updateColumnValues)) {
				throw new RuntimeException('Unable to build UPDATE query without update column values');
			}

			$column_updates = array();

			foreach ($this->updateColumnValues as $column_name => &$column_value) {
				$column_updates[] = QueryStatement::IDENTIFIER . '=' . QueryStatement::PARAM;
				$stmnt->addIdentifier($column_name);
				$stmnt->addParam($column_value);
			}
			$qry_s .= "\nSET " . implode(',', $column_updates);
		}

		$where_stmnt = $this->getWhereClause();

		if (null !== $where_stmnt && $where_stmnt->string !== '') {
			$qry_s .= "\nWHERE " . $where_stmnt->string;
			$stmnt->addParams($where_stmnt->params);
			$stmnt->addIdentifiers($where_stmnt->identifiers);
		}

		if ($this->groups) {
			$clause = $this->getGroupByClause();
			$stmnt->addIdentifiers($clause->identifiers);
			$stmnt->addParams($clause->params);
			$qry_s .= $clause->string;
		}

		if (null !== $this->getHaving()) {
			$having_stmnt = $this->getHaving()->getQueryStatement();
			if (null !== $having_stmnt) {
				$qry_s .= "\nHAVING " . $having_stmnt->string;
				$stmnt->addParams($having_stmnt->params);
				$stmnt->addIdentifiers($having_stmnt->identifiers);
			}
		}

		if ($action !== self::ACTION_COUNT && $this->orders) {
			$clause = $this->getOrderByClause();
			$stmnt->addIdentifiers($clause->identifiers);
			$stmnt->addParams($clause->params);
			$qry_s .= $clause->string;
		}

		if (null !== $this->limit) {
			if ($conn) {
				if (class_exists('DBMSSQL') && $conn instanceof DBMSSQL) {
					$qry_s = QueryStatement::embedIdentifiers($qry_s, $stmnt->getIdentifiers(), $conn);
					$stmnt->setIdentifiers(array());
				}
				$conn->applyLimit($qry_s, $this->offset, $this->limit);
			} else {
				$qry_s .= "\nLIMIT " . ($this->offset ? $this->offset . ', ' : '') . $this->limit;
			}
		}

		if (self::ACTION_COUNT === $action && $this->needsComplexCount()) {
			$qry_s = "SELECT count(0)\nFROM ($qry_s) a";
		}

		$stmnt->string = $qry_s;
		return $stmnt;
	}

	/**
	 * Protected for now.  Likely to be public in the future.
	 * @return QueryStatement
	 */
	protected function getTablesClause($conn) {

		$table = $this->getTable();

		if (!$table) {
			throw new RuntimeException('No table specified.');
		}

		$statement = new QueryStatement($conn);
		$alias = $this->getAlias();
		// if $table is a Query, get its QueryStatement
		if ($table instanceof Query) {
			$table_statement = $table->getQuery($conn);
			$table_string = '(' . $table_statement->string . ')';
		} else {
			$table_statement = null;
		}

		switch ($this->action) {
			case self::ACTION_UPDATE:
			case self::ACTION_COUNT:
			case self::ACTION_SELECT:
				// setup identifiers for $table_string
				if (null !== $table_statement) {
					$statement->addIdentifiers($table_statement->identifiers);
					$statement->addParams($table_statement->params);
				} else {
					// if $table has no spaces, assume it is an identifier
					if (strpos($table, ' ') === false) {
						$statement->addIdentifier($table);
						$table_string = QueryStatement::IDENTIFIER;
					} else {
						$table_string = $table;
					}
				}

				// append $alias, if it's not empty
				if ($alias) {
					$table_string .= " AS $alias";
				}

				// setup identifiers for any additional tables
				if ($this->extraTables) {
					$table_string = '(' . $table_string;
					foreach ($this->extraTables as $t_alias => $extra_table) {
						if ($extra_table instanceof Query) {
							$extra_table_statement = $extra_table->getQuery($conn);
							$extra_table_string = '(' . $extra_table_statement->string . ') AS ' . $t_alias;
							$statement->addParams($extra_table_statement->params);
							$statement->addIdentifiers($extra_table_statement->identifiers);
						} else {
							$extra_table_string = $extra_table;
							if (strpos($extra_table_string, ' ') === false) {
								$extra_table_string = QueryStatement::IDENTIFIER;
								$statement->addIdentifier($extra_table);
							}
							if ($t_alias != $extra_table) {
								$extra_table_string .= " AS $t_alias";
							}
						}
						$table_string .= ", $extra_table_string";
					}
					$table_string .= ')';
				}
				$statement->string = $table_string;
				break;
			case self::ACTION_DELETE:
				if (null !== $table_statement) {
					$statement->addIdentifiers($table_statement->identifiers);
					$statement->addParams($table_statement->params);
				} else {
					// if $table has no spaces, assume it is an identifier
					if (strpos($table, ' ') === false) {
						$statement->addIdentifier($table);
						$table_string = QueryStatement::IDENTIFIER;
					} else {
						$table_string = $table;
					}
				}

				// append $alias, if it's not empty
				if ($alias) {
					$table_string .= " AS $alias";
				}
				$statement->string = $table_string;
				break;
			default:
				throw new RuntimeException('Uknown action "' . $this->action . '", cannot build table list');
				break;
		}
		return $statement;
	}

	/**
	 * Returns true if this Query uses aggregate functions in either a GROUP BY clause or in the
	 * select columns
	 * @return bool
	 */
	protected function hasAggregates() {
		if ($this->groups) {
			return true;
		}
		foreach ($this->columns as $column) {
			if (strpos($column, '(') !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns true if this Query requires a complex count
	 * @return bool
	 */
	protected function needsComplexCount() {
		return $this->hasAggregates()
			|| null !== $this->having
			|| $this->distinct;
	}

	/**
	 * Protected for now.  Likely to be public in the future.
	 * @return QueryStatement
	 */
	protected function getColumnsClause($conn) {
		$table = $this->getTable();

		if (!$table) {
			throw new RuntimeException('No table specified.');
		}

		$statement = new QueryStatement($conn);
		$alias = $this->getAlias();
		$action = $this->action;

		if ($action == self::ACTION_DELETE) {
			return $statement;
		}

		if ($action == self::ACTION_COUNT) {
			if (!$this->needsComplexCount()) {
				$statement->string = 'count(0)';
				return $statement;
			}

			if (null === $this->getHaving()) {
				if ($this->groups) {
					$groups = $this->groups;
					foreach ($groups as &$group) {
						$statement->addIdentifier($group);
						$group = QueryStatement::IDENTIFIER;
					}
					$statement->string = implode(', ', $groups);
					return $statement;
				}

				if (!$this->distinct && $this->columns) {
					$columns_to_use = array();
					foreach ($this->columns as $alias => $column) {
						if (strpos($column, '(') === false) {
							continue;
						}
						if ($alias === $column) {
							$alias = '';
						} else {
							$alias = ' AS "' . $alias . '"';
						}
						$statement->addIdentifier($column);
						$columns_to_use[] = QueryStatement::IDENTIFIER . $alias;
					}
					if ($columns_to_use) {
						$statement->string = implode(', ', $columns_to_use);
						return $statement;
					}
				}
			}
		}

		// setup $columns_string
		if ($this->columns) {
			$columns = $this->columns;
			foreach ($columns as $alias => &$column) {
				if ($alias === $column) {
					$alias = '';
				} else {
					$alias = ' AS "' . $alias . '"';
				}

				$statement->addIdentifier($column);
				$column = QueryStatement::IDENTIFIER . $alias;
			}
			$columns_string = implode(', ', $columns);
		} elseif ($alias) {
			// default to selecting only columns from the target table
			$columns_string = "$alias.*";
		} else {
			// default to selecting only columns from the target table
			$columns_string = QueryStatement::IDENTIFIER . '.*';
			$statement->addIdentifier($table);
		}

		if ($this->distinct) {
			$columns_string = "DISTINCT $columns_string";
		}

		$statement->string = $columns_string;
		return $statement;
	}

	/**
	 * Protected for now.  Likely to be public in the future.
	 * @return QueryStatement
	 */
	protected function getWhereClause() {
		return $this->getWhere()->getQueryStatement();
	}

	/**
	 * Protected for now.  Likely to be public in the future.
	 * @return QueryStatement
	 */
	protected function getOrderByClause($conn = null) {
		$statement = new QueryStatement($conn);
		$orders = $this->orders;
		foreach ($orders as &$order) {
			$order_parts = explode(' ', $order);
			if (count($order_parts) == 1 || count($order_parts) == 2) {
				$statement->addIdentifier($order_parts[0]);
				$order_parts[0] = QueryStatement::IDENTIFIER;
			}
			$order = implode(' ', $order_parts);
		}
		$statement->string = "\nORDER BY " . implode(', ', $orders);
		return $statement;
	}

	/**
	 * Protected for now.  Likely to be public in the future.
	 * @return QueryStatement
	 */
	protected function getGroupByClause($conn = null) {
		$statement = new QueryStatement($conn);
		if ($this->groups) {
			$groups = $this->groups;
			foreach ($groups as &$group) {
				$statement->addIdentifier($group);
				$group = QueryStatement::IDENTIFIER;
			}
			$statement->string = "\nGROUP BY " . implode(', ', $groups);
		}
		return $statement;
	}

	/**
	 * @return string
	 */
	function __toString() {
		$q = clone $this;
		if (!$q->getTable())
			$q->setTable('{UNSPECIFIED-TABLE}');
		return (string) $q->getQuery();
	}

	/**
	 * Returns a count of rows for result
	 * @return int
	 * @param $conn PDO[optional]
	 */
	function doCount(PDO $conn = null) {
		$q = clone $this;

		if (!$q->getTable()) {
			throw new RuntimeException('No table specified.');
		}

		$q->setAction(self::ACTION_COUNT);
		return (int) $q->getQuery($conn)->bindAndExecute()->fetchColumn();
	}

	/**
	 * Executes DELETE query and returns count of
	 * rows deleted.
	 * @return int
	 * @param $conn PDO[optional]
	 */
	function doDelete(PDO $conn = null) {
		$q = clone $this;

		if (!$q->getTable()) {
			throw new RuntimeException('No table specified.');
		}

		$q->setAction(self::ACTION_DELETE);
		return (int) $q->getQuery($conn)->bindAndExecute()->rowCount();
	}

	/**
	 * Executes SELECT query and returns a result set.
	 * @return PDOStatement
	 * @param $conn PDO[optional]
	 */
	function doSelect(PDO $conn = null) {
		$q = clone $this;

		if (!$q->getTable()) {
			throw new RuntimeException('No table specified.');
		}

		$q->setAction(self::ACTION_SELECT);
		return $q->getQuery($conn)->bindAndExecute();
	}

	/**
	 * Do not use this if you can avoid it.  Just use doUpdate.
	 * @deprecated
	 * @see Query::doUpdate
	 * @return Query
	 */
	function setUpdateColumnValues(array $column_values) {
		$this->updateColumnValues = &$column_values;
		return $this;
	}

	/**
	 * @param array $column_values
	 * @param PDO $conn
	 * @return int
	 * @throws RuntimeException
	 */
	function doUpdate(array $column_values, PDO $conn = null) {
		$q = clone $this;

		$q->updateColumnValues = &$column_values;

		if (!$q->getTable()) {
			throw new RuntimeException('No table specified.');
		}

		$q->setAction(self::ACTION_UPDATE);
		return (int) $q->getQuery($conn)->bindAndExecute()->rowCount();
	}

}
