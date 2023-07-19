<?php
namespace XModule\XMedooBuilder\PhpConstructor;

use XModule\XMedooBuilder\MedooBuilder;

class CodeGenerator {
	protected int    $connection_index = 0;
	protected string $main_class       = "";
	protected string $main_file        = "";

	protected string $tabs_namespace   = "";
	protected string $main_namespace   = "";
	protected string $main_class_name  = "Table";
	protected string $main_dir         = "";

	protected ?array $table_list = null;

	/**
	 * @param $main_class          - ClassName with namespace of main App Tables class
	 * @param $main_file            - Path to this class file
	 * @param int $connection_index - index of connection, default = 0
	 */
	public function __construct($main_class, $main_file, int $connection_index = 0) {
		$this->connection_index = $connection_index;
		$this->main_class = $main_class;
		$this->main_file = $main_file;
		$this->tabs_namespace = $main_class;
		$main_namespace_tmp = explode("\\",$this->main_class);
		$this->main_class_name = array_pop($main_namespace_tmp);
		$this->main_namespace = implode("\\",$main_namespace_tmp);
		$this->main_dir = dirname($main_file);
	}

	public function write_all() {
		$data = $this->render_all();
		$debug = [];
		foreach($data["php"]??[] as $file => $txt) {
			$debug[] = ">>>>>>> FILE: " . $file;
			$debug = array_merge($debug, explode(PHP_EOL, $txt));
			if(!is_dir(dirname($file))) mkdir(dirname($file));
			if(is_dir(dirname($file))) {
				file_put_contents($file, $txt);
			}
			$debug[] = "";
		}
		return $debug;
	}

	public function render_all() {
		$table_list = is_null($this->table_list)?$this->table_list=$this->get_table_list():$this->table_list;

		$_main = $this->render_main_class();
		$php = [$this->main_file => $_main];

		$_tabs_dirname = $this->main_dir.DIRECTORY_SEPARATOR.$this->main_class_name.DIRECTORY_SEPARATOR;
		foreach ($table_list as $tab) {
			$php[$_tabs_dirname.$this->reformat_classname($tab).".php"]=$this->render_table_class($tab);

		}

		return [
			"main_class" => $this->main_class,
			"main_file" => $this->main_file,
			"main_namespace" => $this->main_namespace,
			"main_class_name" => $this->main_class_name,
			"tabs_namespace" => $this->tabs_namespace,
			"main_dir" => $this->main_dir,
			"php" => $php
		];
	}

	public function render_table_class($table_name) {
		$columns_data = $this->get_column_list($table_name);
		$columns_list = array_column($columns_data,'COLUMN_NAME');
		$columns_list_txt = '"'.implode('","',$columns_list).'"';
		$primary_key = array_values(array_filter($columns_data, function($c){ return ($c["COLUMN_KEY"]??"")=="PRI";}))[0]["COLUMN_NAME"]??"";

		$class_name = $this->reformat_classname($table_name);
//		$const_tab_name =
		$txt = ["<?php","","namespace {$this->tabs_namespace};",""];
		$txt[] = "use {$this->main_class};";
		$txt[] = "";
		$txt[] = "class {$class_name} extends {$this->main_class_name} {";
		$txt[] = "  const __NAME = \"{$table_name}\";";
		$txt[] = "  const __INDEX = \"{$primary_key}\";";

		$txt[] = "";
		foreach ($columns_list as $col) {
			$_name = strtoupper($col);
			$txt[] = "  const {$_name} = \"{$col}\";";
		}

		$txt[] = "";
		$txt[] = "  public function __construct(\$input=null) {";
		$txt[] = "      \$this->__set_table_columns([{$columns_list_txt}]);";
		$txt[] = "      parent::__construct(self::__NAME, self::__INDEX, {$this->connection_index}, \$input);";
		$txt[] = "  }";


		foreach ($columns_list as $col) {
			$txt[] = "";
			$txt[] = "  public function {$col}(\$value=null) {";
			$txt[] = "      if(is_null(\$value)) return \$this[\"{$col}\"];";
			$txt[] = "      else return \$this->set(\"{$col}\", \$value);";
			$txt[] = "  }";
		}

		$txt[] = "";
		$txt[] = "}";
		return implode(PHP_EOL, $txt);
	}

	public function render_main_class() {
		$table_list = is_null($this->table_list)?$this->table_list=$this->get_table_list():$this->table_list;
		$static_func_list = [];
		foreach ($table_list as $tab) {
			$static_func_list = array_merge($static_func_list,$this->render_tab_create_function($tab));
		}
		return $this->inject_main_code($static_func_list);
	}

	protected function inject_main_code($inject_list) {
		$file = $this->main_file;
		if (is_file($file)) {
			$content   = file_get_contents($file);
			$lines     = explode(PHP_EOL, $content);
			$new_lines = [];
			$is_inject = false;
			foreach ($lines as $line) {
				if (trim($line) == "###AUTO_GENERATE_BLOCK_END") {
					$is_inject = false;
				}
				if (!$is_inject) {
					$new_lines[] = $line;
				}
				if (trim($line) == "###AUTO_GENERATE_BLOCK_START") {
					$is_inject = true;
					$new_lines = array_merge($new_lines, $inject_list);
				}
			}
			$new_content = implode(PHP_EOL, $new_lines);
//			file_put_contents($file,$new_content);
			return $new_content;
		}
		return null;
	}

	protected function render_tab_create_function($table_name): array {
		$TAB = $this->reformat_classname($table_name);
		$CS  = $this->main_class_name;
		$txt =  [
			"       static function {$TAB}(\$input=null): {$CS}\\{$TAB} {",
			"           return new {$CS}\\{$TAB}(\$input);",
			"       }"];
		return $txt;
	}

	protected function get_table_list(): array {
		$list = MedooBuilder::db($this->connection_index)->query("show tables")->fetchAll();
		return array_column($list, 0);
	}

	protected function get_column_list($table_name){
		$db_name = MedooBuilder::all_options()[$this->connection_index]["database"] ?? "";
		$data = MedooBuilder::db($this->connection_index)->query(
			"SELECT COLUMN_NAME,COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '{$db_name}' AND TABLE_NAME ='{$table_name}'")->fetchAll();
		$column_names = array_column($data, "COLUMN_NAME");
		return array_combine($column_names, $data);
	}

	protected function reformat_classname($class_name): string {
		$tmp = explode("_", $class_name);
		return implode("", array_map("ucfirst", $tmp));
	}
}