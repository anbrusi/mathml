<?php

namespace isLib;

class Ldb {

    private static \PDO $dbh;

    public static function connect():bool {
        try {
            $user = 'iststch_user';
            $pass = 'iststch_user';
            self::$dbh = new \PDO('mysql:host=localhost;dbname=iststch_mathml', $user, $pass);
            self::$dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    } 

    public static function prepare(string $query, array $options=[]):\PDOStatement {
        return self::$dbh->prepare($query, $options);
    }

    public static function lastInsertId():string|false {
        return self::$dbh->lastInsertId();
    }

	/**
	 * Builds the SQL set clause for prepared queries 'SET fieldname1=:fieldname1, fieldname2=:fieldname2, ...'
	 * 
	 * @param array $fields an array of arrays whose keys are the fieldnames, the values are not used
	 */
	private static function prepareSetClause(array $fields):string {
		$setClause = 'SET ';
		foreach ($fields as $name => $value) {
			$setClause .= '`'.$name.'`=:'.$name.', ';
		}
		return substr($setClause,0,strlen($setClause)-2);
	}
	
    /**
	 * Executes an sql statement and returns the number of affected rows.
	 * Typical uses are DELETE, INSERT, UPDATE queries or just counting tasks.
	 * It is a misnomer and has nothing to do with PDO::exec, but rather with PDO::execute
	 * 
	 * @param string $sql
	 * @param array $bindings. Optional. If there are IN parameters in $sql such as WHERE id=:id, this binds :id to $id with array(':id' => $id)
	 * @throws \Exception
	 * @return integer the number of affected rows.
	 */
	public static function exec(string $sql,array $bindings=array()):int {
		$stmt = self::prepare($sql);
		$stmt->execute($bindings);
		return $stmt->rowCount();
	}

	/**
	 * Gets the first row returned by a query. Use this, when it is clear, that at most one row exists
	 * Works with prepared statements and is injection safe
	 * 
	 * @param string $sql
	 * @param array $bindings. Optional. If there are in parameters in $sql such as WHERE id=:id, this binds :id to $id with array(':id' => $id)
	 * @throws \Exception
	 * @return mixed array indexed by DB field names whose values are the values stored in these fields or false if there is no first row
	 */
	public static function firstRow(string $sql, array $bindings=array()) {
		$stmt = self::prepare($sql);
		$stmt->execute($bindings);
		$row = $stmt->fetch();
		// Free resources in case this was not the only row;
		if ($stmt->closeCursor() == false) {
			throw new \Exception('Cannot close PDO statement cursor');
		}
		return $row;
	}
	/**
	 * Gets the value of the first field in the first row of a query. Use this, when the query should return exactly one value
	 * Works with prepared statements and is injection safe
	 * 
	 * @param string $sql
	 * @param array $bindings. Optional. If there are in parameters in $sql such as WHERE id=:id, this binds :id to $id with array(':id' => $id)
	 * @throws \Exception
	 * @return mixed the searched value or false if the query $sql does not retrieve anything. 
	 * If the content of the field field satisfying the query is null, null is returned, but if no field satisfies the query false is returned
	 */
	public static function scalar(string $sql, array $bindings=array()) {
		$row = self::firstRow($sql,$bindings);
		if ($row === false) return false;
		return current($row);
	}
	/**
	 * Gets all records from $table satisfying the conditions in $whereClause (without keyword WHERE)
	 * 
	 * @param string $table
	 * @param array $fields an array of the fields to include in each record
	 * @param string $whereClause optional. Default is empty string. If given it is a WHERE statement without 'WHERE'
	 * @param string $orderClause optional. Default is empty string. If given it is an ORDER BY statement, without 'ORDER BY'
	 * @throws \Exception
	 * @return array each element is an array indexed by the field names with the content of these fields as values. The array can be empty
	 */
	public static function fetchAll(string $table, array $fields, string $whereClause='', string $orderClause=''):array {
		$sql = 'SELECT ';
		foreach ($fields as $field) {
			$sql .= '`'.$field.'`,';
		}
		// Remove last comma
		$sql = substr($sql,0,strlen($sql) - 1);
		$sql .= ' FROM '.$table;
		if ($whereClause != '') {
			$sql .= ' WHERE '.$whereClause;
		}
		if ($orderClause != '') {
			$sql .= ' ORDER BY '.$orderClause;
		}
		$stmt = self::prepare($sql);
		$stmt->execute();
		$result = $stmt->fetchAll();
		if ($result === false) {
			throw new \Exception('Error in query '.$sql);
		}
		return $result;
	}
	/**
	 * Inserts the values of $fields in the fields named by the keys of $fields in table $table
	 * 
	 * @param string $table the table into which $fields are inserted
	 * @param array $fields the keys are the field names in the db, the values are the values to be inserted in these fields
	 * @throws \Exception
	 * @return integer the auto increment value
	 */
	public static function insert(string $table, array $fields) {
		if (count($fields) == 0) {
			throw new \Exception('LDB::insert needs at least one field to insert. None is given');
		}
		// failure of prepare causes a PDO exception
		$stmt = self::prepare('INSERT INTO '.$table.' '.self::prepareSetClause($fields));
		if (!$stmt->execute($fields)) {
			throw new \Exception('Failure in INSERT INTO '.$table);
		}
		return self::lastInsertId();
	}
	/**
	 * Updates the values of $fields in the fields named by the keys of $fields in table $table
	 * 
	 * @param string $table the table into which $fields are inserted
	 * @param array $fields the keys are the field names in the db, the values are the values to be inserted in these fields
	 * @param string $whereClause the WHERE condition without the keyword 'WHERE'
	 * @throws \Exception
	 */
	public static function update(string $table, array $fields, string $whereClause) {
		// failure of prepare causes a PDO exception
		$stmt = self::prepare('UPDATE '.$table.' '.self::prepareSetClause($fields).' WHERE '.$whereClause);
		if (!$stmt->execute($fields)) {
			throw new \Exception('Failure in UPDATE '.$table);
		}
	}

	/**
	 * Returns an array of info about all columns in table $table of schema $schema. 
	 * 
	 * Each info is itself an array with keys 'COLUMN_NAME', 'COLUMN_KEY', 'EXTRA', 'COLUMN_DEFAULT',
	 * 'IS_NULLABLE', 'DATA_TYPE',  'CHARACTER_MAXIMUM_LENGTH'
	 * COLUMN_NAME: the name of the field
	 * COLUMN_KEY: mostly empty, can be 'PRI'=primary index, 'UNI' unique index, 'MUL' multiple index
	 * EXTRA: mostly empty, can be 'auto_increment', 'on update CURRENT_TIMESTAMP'
	 * COLUMN_DEFAULT: mosty NULL for no default, can be a value which will be the default, 'CURRENT_TIMESTAMP'
	 * IS_NULLABLE: 'YES' or 'NO'. If COLUMN_DEFAULT is NULL and IS_NULLABLE is NO, a field value is mandatory in insert operations
	 * DATA_TYPE: 'timestamp', 'int', 'double', 'varchar' etc.
	 * CHARACTER_MAXIMUM_LENGTH: applies only to DATA_TYPE=varchar
	 * 
	 * If there are no columns an empty array is returnaed. On error an exception is thrown
	 * 
	 * @param string $schema
	 * @param string $table
	 * return array
	 */
	public static function columnInfo(string $schema, string $table):array {
		$sql = 'SELECT COLUMN_NAME, COLUMN_KEY, EXTRA, COLUMN_DEFAULT, IS_NULLABLE, DATA_TYPE,  CHARACTER_MAXIMUM_LENGTH '.
				' FROM information_schema.COLUMNS WHERE TABLE_NAME="'.$table.'" AND TABLE_SCHEMA="'.$schema.'"';
		$stmt = self::prepare($sql);
		$stmt->execute();
		$columns = $stmt->fetchAll();
		if ($columns === false) {
			throw new \Exception('Cannot fetch column names of table '.$table);
		}
		return $columns;
	}
}