<?php

namespace XModule\XMedooBuilder\DB;

use ArrayAccess;
use Countable;
use JsonSerializable;

class TableDataModel implements ArrayAccess, Countable, JsonSerializable {

	protected int                  $db_index;
	protected TableQueryController $table;
	protected                      $table_index_name = null;
	protected array $table_columns = [];
	protected array $row           = [];
	protected       $row_id        = null;


	public function __construct($table_name, $table_index, $db_index=0, $input = null) {
		$this->db_index = $db_index;
		$this->table_index_name = $table_index;
		$this->table = new TableQueryController($table_name , $table_index, $db_index, $this);

		if(is_array($input)) {
			$this->row = $input;
			if(isset($input[$table_index])) $this->row_id = $input[$table_index];
		} else if (is_numeric($input)) {
			$this->db()->load_by_id($input);
		}
	}

	public function db(): TableQueryController {
		return $this->table;
	}

	public function id($id = null) {
		if(is_null($id)) return $this->row_id;
		else return $this->db()->load_by_id($id);
	}

	public function to_array(): array {
		return $this->row;
	}

	public function set($column, $value) : TableDataModel {
		if(in_array($column,$this->table_columns)) {
			$this->row[$column] = $value;
			if($column == $this->table_index_name) $this->row_id = $value;
		}
		return $this;
	}

	public function __have_data(): bool {
		return count($this->row) > (isset($this->row_id[$this->table_index_name]) ? 1 : 0);
	}

	public function __row_id($val = null) {
		if(is_null($val)) return $this->row_id;
		else return $this->row_id=$val;
	}

	public function __row_id_unset(): TableDataModel {
		$this->row_id = null; return $this;
	}


	public function __get_table_columns(): array {
		return $this->table_columns;
	}

	protected function __set_table_columns($table_columns) {
		$this->table_columns = $table_columns;
	}

	public function offsetExists($offset): bool {
		return isset($this->row[$offset]);
	}

	public function offsetGet($offset) {
		return $this->row[$offset] ?? null;
	}

	public function offsetSet($offset, $value) {
		if (!is_null($offset)) $this->set($offset, $value);
	}

	public function offsetUnset($offset) {
		unset($this->row[$offset]);
	}

	public function count(): int {
		return count($this->row);
	}

	public function jsonSerialize() {
		return $this->row;
	}
}