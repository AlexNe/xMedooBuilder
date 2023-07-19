<?php

namespace XModule\XMedooBuilder\DB;

class TableQueryController extends QueryController {
	protected bool $have_in_db = false;

	public function data(): TableDataModel {
		return $this->owner;
	}

	public function is_exist(): bool {
		return $this->have_in_db;
	}

	public function load_by_id($id): TableQueryController {
//		$this->owner->__row_id($id);
		$row = $this->get([$this->table_index_name => $id]);
		if(!is_null($row)) {
			$this->have_in_db = true;
			foreach ($row as $k => $v) $this->owner->set($k,$v);
		}
		else $this->owner[$this->table_index_name] = $id;
		return $this;
	}

	public function load_by_column($column, $value): TableQueryController {
		$row = $this->get([$column => $value]);
		//if(isset($this->row[$this->table_index_name])) $this->owner->__row_id($this->row[$this->table_index_name]);
		if(!is_null($row)) {
			$this->have_in_db = true;
			foreach ($row as $k => $v) $this->owner[$k] = $v;
		}
		return $this;
	}

	public function save(): TableQueryController {
		$row = $this->owner->to_array();
		if(count($row) > 0) {
			if (is_null($this->owner->__row_id())) {
				$this->owner->__row_id(($this->row[$this->table_index_name] ?? null));
				$data = [];
				foreach ($row as $key => $value) { if(in_array($key, $this->owner->__get_table_columns())) $data[$key] = $value; }
				$result = $this->insert($data)->last_id();
				if(is_numeric($result) && $result > 0) {
					$this->owner[$this->table_index_name] = $result;
					$this->have_in_db = true;
				}
			}
			else {
				$data = [];
				foreach ($row as $key => $value) { if(in_array($key, $this->owner->__get_table_columns())) $data[$key] = $value; }
				$this->update($data, [$this->table_index_name => $this->owner->__row_id()]);
			}
		}
		return $this;
	}

	public function hard_insert(): TableQueryController {
		$this->owner->__row_id_unset();
		$this->save();
		return $this;
	}

	public function save_as_duplicate(): TableQueryController {
		$this->owner->__row_id_unset();
		unset($this->owner[$this->table_index_name]);
		$this->save();
		return $this;
	}

	public function save_as_new(): TableQueryController {
		return $this->save_as_duplicate();
	}
}