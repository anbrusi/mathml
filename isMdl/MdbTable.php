<?php
/**
 * @abstract
 * Handling of one particular DB table
 *
 * @author A. Brunnschweiler
 * @version 13.02.2016
 */
namespace isMdl;
class MdbTable {
	/**
	 * Name of referenced table
	 *
	 * @var string $table
	 */
	private $table;
	/**
	 * This is the name of the field, which is the primary key, if the table has a primary key. It is null if the table has no primary key
	 *
	 * @var string
	 */
	private $primaryKey;
	/**
	 * Array of info of all table fields (colums). Set by constructor with introspection
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
	 * @var array
	 */
	private $fieldInfos;
	/**
	 * if true, then $this->primaryKey is an auto increment field
	 *
	 * @var bool
	 */
	private $primaryAuto = false;
	/**
	 * An array of all the field names. Built by the constructor
	 *
	 * @var array
	 */
	protected $fieldNames;
	/**
	 * An array of field values indexed by their names. The constructor populates this array with key=fieldname, value=null
	 *
	 * @var array
	 */
	private $fields = null;
	/**
	 * Constructor
	 *
     * @param string $table
	 */
	function __construct(string $table) {

        $schema = \isLib\Lconfig::getDbName();

		// \isLib\Lerror::notice('schema',$schema,'table',$table,'primary key',$primaryKey);
		$this->table = $table;
		$this->fieldInfos = \isLib\Ldb::columnInfo($schema, $table);
		// \isLib\Lerror::notice('this->fieldInfos',$this->fieldInfos);
		$this->fieldNames = array();
		foreach ($this->fieldInfos as $info) {
			$this->fieldNames[] = $info['COLUMN_NAME'];
			$this->fields[$info['COLUMN_NAME']] = null;
			if ($info['COLUMN_KEY'] == 'PRI') {
				$this->primaryKey = $info['COLUMN_NAME'];
				if ($info['EXTRA'] == 'auto_increment') {
					$this->primaryAuto = true;
				}
			}
		}
	}


	/**
	 * Returns an array indexed by fieldnames with the values, that should be inserted.
	 * Checks that all fields, that have no default are present and throws an exception if not.
	 * If there is a primary index with auto increment, it is not added.
	 * If there is a primary index without auto increment, it is added with the primary index as parameter.
	 *
	 * @param mixed $primaryKey the value of the primary key, if it is not auto increment
	 *
	 * @return array
	 */
	private function getInsertFields($primaryKey):array {
		$fields =array();
		if (in_array('lastaccessor', $this->fieldNames)) {
			$this->fields['lastaccessor'] = $_SESSION['userid'];
		}
		foreach ($this->fieldInfos as $info) {
			$fieldname = $info['COLUMN_NAME'];
			if ($info['COLUMN_KEY'] == 'PRI') {
				// Add primary key only if it is not auto increment
				if ($info['EXTRA'] != 'auto_increment') {
					$fields[$fieldname] = $primaryKey;
				}
			} else {
				// Add field if present, else complain if mandatory
				if (isset($this->fields[$fieldname])) {
					$fields[$fieldname] = $this->fields[$fieldname];
				} else {
					if (($info['COLUMN_DEFAULT'] == 'NULL') && ($info['IS_NULLABLE'] == 'NO')) {
						throw new \Exception('Field '.$fieldname.' in table '.$this->table.' is mandatory and missing');
					}
				}
			}
		}
		// \isLib\Lerror::notice('fields',$fields);
		return $fields;
	}
	/**
	 * Returns the value of the current primary key (used in update)
	 *
	 * @throws \Exception if no primary key value is known
	 */
	protected function getPrimaryKeyValue() {
		if (!isset($this->fields[$this->primaryKey])) {
			throw new \Exception('Primary key value for table '.$this->table.' is missing');
		}
		return $this->fields[$this->primaryKey];
	}
	/**
	 * Returns available introspection data about column $name if available. Returns null if the column is not found
	 *
	 * @param string $name
	 * @throws \Exception
	 * @return array with keys 'COLUMN_NAME', 'COLUMN_KEY', 'EXTRA', 'COLUMN_DEFAULT',
	 * 							'IS_NULLABLE', 'DATA_TYPE',  'CHARACTER_MAXIMUM_LENGTH'
	 */
	private function getFieldInfo(string $name) {
		foreach ($this->fieldInfos as $key => $info) {
			if ($info['COLUMN_NAME'] == $name) {
				return $this->fieldInfos[$key];
			}
		}
		return null;
	}
	/**
	 * Loads all fields of a record with primary key $id
	 *
	 * @param mixed $id value of primary key, usually an integer
	 * @throws \Exception an exception if the record cannot be loaded. As an instance if $id does not exst in the DB
	 */
	public function load($id) {
		$sql = 'SELECT * FROM '.$this->table.' WHERE '.$this->primaryKey.'=:primaryKey';
		$stmt = \isLib\Ldb::prepare($sql);
		$stmt->execute(array(':primaryKey' => $id));
		$this->fields = $stmt->fetch();
		if ($this->fields === false) {
            if (!is_numeric($id)) {
                throw new \Exception("Non numerical ID passed to load.");
            } else {
                throw new \Exception('Could not load id='.$id.' from table '.$this->table);
            }
		}
	}
	/**
	 * Inserts a row in $this->table. The value $primaryKey of the primary key must be set only it is not auto increment
	 * Fields 'lastaccess', 'lastaccessor' and all fields having a default value need not be set.
	 *
	 * @param mixed $primaryKey
	 * @throws \Exception
	 * @return int value of the auto increment primary key
	 */
	public function insert($primaryKey=NULL) {
		if ($this->primaryAuto && ($primaryKey !== NULL)) {
			throw new \Exception('No explicit primary key value is possible for auto increment table '.$this->table);
		}
		if (($this->primaryKey !== null) && !$this->primaryAuto && ($primaryKey === NULL)) {
			throw new \Exception('Table '.$this->table.' is not auto increment. insert needs an explicit primary key');
		}
		$fields = $this->getInsertFields($primaryKey);
		// \isLib\Lerror::notice('going to insert fields',$fields);
		if ($this->primaryKey === null) {
			// The table has no primary key, so nothing is returned
			\isLib\Ldb::insert($this->table, $fields);
		} else {
			// The table has a primary key, although not necessarily auto increment
			$newKey = \isLib\Ldb::insert($this->table, $fields);
			$this->fields[$this->primaryKey] = $newKey;
			return $newKey; // makes sense only for auto increment
		}
	}
	/**
	 * Updates the record specified in $this->fields[$this->primarykey] of $this->table with the values in $this->fields
	 *
	 */
	public function update() {
		if ($this->primaryKey === null) {
			throw new \Exception('update is supported only for tables having a primary key. '.$this->table.' has none.');
		}
		$whereClause = $this->primaryKey.'="'.$this->getPrimaryKeyValue().'"';
		\isLib\Ldb::update($this->table, $this->fields, $whereClause);
	}
	/**
	 * Gets the value of DB field named $name
	 *
	 * @param string $name
	 * @throws \Exception
	 * @return mixed
	 */
	public function get(string $name) {
		if (array_key_exists($name, $this->fields)) {
			return $this->fields[$name];
		}
		throw new \Exception('Field '.$name.' does not exist');
	}
	/**
	 * Sets the value of DB field named $name.
	 * Throws an exception if there is no such field in the table
	 *
	 * @param string $name
	 * @param mixed $value
	 * @throws \Exception
	 */
	public function set(string $name, $value) {
		if ($this->getFieldInfo($name) === null) {
			throw new \Exception('Field '.$name.' not found in table '.$this->table);
		}
		$this->fields[$name] = $value;
	}
	/**
	 * Gets all DB fields, if they have been loaded
	 *
	 * @return array an array indexed by field names, with their values
	 * @throws \Exception
	 */
	public function getAll():array {
		if ($this->fields === null) {
			throw new \Exception('DB fields are not loaded');
		}
		return $this->fields;
	}
	/**
	 * Returns a numeric array of names of all the fields of the specific child
	 *
	 * @return array
	 */
	public function getFieldNames():array {
		return $this->fieldNames;
	}
	/**
	 * Returns an array of field values indexed by field names
	 *
	 * @param array $fields
	 * @throws \Exception
	 * @return mixed[]
	 */
	public function getFields(array $fields):array {
		$result = array();
		foreach ($fields as $fieldname) {
			if (in_array($fieldname, $this->fieldNames) && array_key_exists($fieldname, $this->fields)) {
				$result[$fieldname] = $this->fields[$fieldname];
			} else {
				throw new \Exception('Field '.$fieldname.' does not exist in '.$this->table.' or has not been loaded');
			}
		}
		return $result;
	}
	/**
	 * Sets all fields listed in $fields to the value in the POST variable named after the field
	 * Ltools::filterHTML is called for all POST variables
	 *
	 * @param array $fields
	 * @throws \Exception
	 */
	public function setFromPost(array $fields) {
		foreach ($fields as $field) {
			if (isset($_POST[$field])) {
				$this->set($field,$_POST[$field]);
			} else {
				throw new \Exception('POST variable "'.$field.'" is missing');
			}
		}
	}
	/**
	 * Scans POST variables. If it finds one with the same name as a DB field it sets the field to the value of the post variable
	 */
	public function setPosted() {
		// \isLib\Lerror::notice('POST',$_POST,'this->fieldNames',$this->fieldNames);
		foreach ($_POST as $key => $value) {
			if (in_array($key,$this->fieldNames)) {
				$this->fields[$key] = $value;
			}
		}
	}
	/**
	 * Sets all the fields mentioned in fields. Throws an exception if a field does not exist
	 *
	 * @param array $fields Keys are field names, values the value to which the field is set
	 * @throws \Exception
	 */
	public function setFields(array $fields) {
		foreach ($fields as $key => $value) {
			if (in_array($key,$this->fieldNames)) {
				$this->fields[$key] = $value;
			} else {
				throw new \Exception($key.' is not a field of table '.$this->table);
			}
		}
	}
	/**
	 * Checks if there is a record with primaty key $value
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function primaryKeyExists($value):bool {
		$count = \isLib\Ldb::exec('SELECT * FROM '.$this->table.' WHERE '.$this->primaryKey.'=:value', array(':value' => $value));
		return ($count > 0);
	}
	/**
	 * Returns the name of the primary key. Tables without primary key cannot be created
	 *
	 * @return string
	 */
	public function primaryKeyName():string {
		return $this->primaryKey;
	}
}
