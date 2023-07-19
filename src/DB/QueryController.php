<?php

namespace XModule\XMedooBuilder\DB;

use XModule\XMedooBuilder\MedooBuilder;

class QueryController {
	protected int $db_index;
	protected string $table_name;
	protected string $table_index_name;
	protected ?array  $columns_array = null;
	protected ?array  $where_array = null;
	protected ?array  $join_array = null;
	protected ?\PDOStatement $last_statement;
	protected ?TableDataModel $owner;

	public function __construct($tab_name = null, $tab_idx = null, $db_index=0, TableDataModel $owner = null) {
		$this->db_index = $db_index;
		if (!is_null($tab_name)) $this->table_name = $tab_name;
		if (!is_null($tab_idx)) $this->table_index_name = $tab_idx;
		if (!is_null($owner)) $this->owner = $owner;
	}

	public function where($where): QueryController {
		$this->where_array = $where;
		return $this;
	}

	public function limit($offset_or_count, $count = null): QueryController {
		if(is_null($count)) $this->where_array["LIMIT"] = $offset_or_count;
		else $this->where_array["LIMIT"] = [$offset_or_count, $count];
		return $this;
	}

	public function order(array $order): QueryController {
		$this->where_array["ORDER"] = $order;
		return $this;
	}

	public function desc($column): QueryController {
		$this->where_array["ORDER"] = [$column, "DESC"];
		return $this;
	}

	public function asc($column): QueryController {
		$this->where_array["ORDER"] = [$column, "ASC"];
		return $this;
	}

	/**
	 * Load data to owner ModelData class
	 * @return $this
	 */
	public function load(): QueryController {
		if(!is_null($this->owner)){
			foreach ($this->row() as $col => $val ) {
				$this->owner->set($col, $val);
			}
		}
		return $this;
	}

//	public function item() : TableDataModel {
//		$columns = is_null($this->columns_array)?"*":(count($this->columns_array)>0?$this->columns_array:"*");
//		return MedooBuilder::db($this->db_index)->get($this->table_name, $columns, $this->where_array);
//	}

	public function get($where) {
		return MedooBuilder::db($this->db_index)->get($this->table_name, "*", $where);
	}

	public function row() {
		$columns = is_null($this->columns_array)?"*":(count($this->columns_array)>0?$this->columns_array:"*");
		return MedooBuilder::db($this->db_index)->get($this->table_name, $columns, $this->where_array);
	}

	public function rows(): ?array {
		$columns = is_null($this->columns_array)?"*":(count($this->columns_array)>0?$this->columns_array:"*");
		if(is_null($this->join_array))
			return MedooBuilder::db($this->db_index)->select($this->table_name, $columns, $this->where_array);
		return MedooBuilder::db($this->db_index)->select($this->table_name, $this->join_array, $columns, $this->where_array);
	}

	public function select($columns = null, $where = null): QueryController {
		$this->columns_array = ['*'];
		$this->where_array = [];
		if(!is_null($columns)) $this->columns_array = $columns;
		if(!is_null($where)) $this->where_array = $where;
		return $this;
	}

	public function update($data, array $where = null): QueryController {
		if(!is_null($where)) $this->where_array = $where;
		$this->last_statement = MedooBuilder::db($this->db_index)->update($this->table_name, $data, $this->where_array);
		return $this;
	}

	public function insert($data): QueryController {
		$this->last_statement = MedooBuilder::db($this->db_index)->insert($this->table_name, $data);
		return $this;
		// if (!is_null($this->last_statement) && $this->last_statement->errorCode() == '00000')
		// return MedooBuilder::medoo()->id();
	}

	public function last_id(): ?string {
		return MedooBuilder::db($this->db_index)->id();
	}

	public function last_statement(): ?\PDOStatement {
		return $this->last_statement;
	}

	public function delete(array $where = null) : ?\PDOStatement {
		if(!is_null($where)) $this->where_array = $where;
		return MedooBuilder::db($this->db_index)->delete($this->table_name, $this->where_array);
	}
	public function delete_by_id($id) : ?\PDOStatement {
		return MedooBuilder::db($this->db_index)->delete($this->table_name, [$this->table_index_name => $id]);
	}

	public function count_rows($column, $data): ?int {
		return MedooBuilder::db($this->db_index)->count($this->table_name, $column, $data);
	}

}