<?php
require_once __DIR__ . '/vendor/autoload.php';
use \ForceUTF8\Encoding;

/**
 * DataServiceDB
 *
 * {DESCRIPTION}
 *
 * @package DataService
 * @author akirk
 * @copyright Copyright (c) 2013 NPEU
 * @version 0.1

 **/
class DataServiceDB extends DataService
{
	public $dao;
	public $base_sql;
	public $fields;
	public $main_table;

	public function __construct($params = array())
	{
        parent::__construct($params);
	}

	public function hasField($name)
	{
		if (!$this->fields) {
			$this->listFields();
		}
		return in_array($name, $this->fields);
	}

	public function listFields()
	{

		$query = $this->dao->prepare('DESCRIBE ' . $this->main_table);
		$query->execute();
		$this->fields = $query->fetchAll(PDO::FETCH_COLUMN);
	}

	public function processOrder($value)
	{
		// Allow for FIELD ordering:
		if (strpos($value, ' FIELD') !== false) {
			return $value;
		}

		$return = '';
		$parts = explode(' ', $value);
		if (!$this->hasField($parts[0])) {
			return false;
		}
		$return = $parts[0];
		if (!empty($parts[1])) {
			$dir = strtoupper($parts[1]);
			if (in_array($dir, array('ASC', 'DESC'))) {
				$return .= ' ' . $dir;
			}
		}
		return $return;
	}

	public function run($get = array())
	{
		$get = parent::run($get);
        #echo "<pre>\n"; var_dump($get); echo "</pre>\n";exit;

		$dao    = $this->dao;
		$sql    = $this->base_sql;
		$wheres = $this->getBaseWheres();
		$values = array();
		$i      = is_array($wheres) ? count($wheres) : 0;
		foreach ($get as $key => $value) {
            $value = Encoding::fixUTF8(urldecode($value));
			//$value = utf8_decode(urldecode($value));
			$key   = str_replace('!', '', $key, $count);
			if ($partial = $this->getField($key, $value)) {
				#echo "<pre>\n";var_dump($partial);echo "</pre>\n";
				// Ignore and/or on first item:
				if ($i > 0) {
					$and_or = (bool) $count ? ' OR ' : ' AND ';
				}  else {
					$and_or = '';
				}
				$wheres[] = $and_or . $partial['sql'];
				$values += $partial['values'];

				#echo "<pre>\n"; var_dump($values); echo "</pre>\n"; exit;
				/*$val_key          = ':' . str_replace('!', 'N', $key) . $i;
				   $wheres[$key][]   = $and_or . sprintf($sql_map[$key], $val_key);
				   $values[$val_key] = $value;*/
				$i++;
			}

			/*$key = str_replace('~', '', $key, $count);
			   if (array_key_exists($key, $sql_map)) {
			   // Ignore and/or on first item:
			   if ($i > 0 && isset($wheres[$key])) {
			   $and_or = (bool) $count ? ' OR ' : ' AND ';
			   }  else {
			   $and_or = '';
			   }
			   // Prepared statements don't like ! in placeholders, so replace with N:
			   $val_key          = ':' . str_replace('!', 'N', $key) . $i;
			   $wheres[$key][]   = $and_or . sprintf($sql_map[$key], $val_key);
			   $values[$val_key] = $value;
			   $i++;
			   }*/
		}

		if (is_array($wheres) && count($wheres) > 0) {
			$sql .= "\nWHERE " . implode("\n", $wheres);
		}

		if ($this->group) {
			$sql .= "\nGROUP BY :group";
			$values[':group'] = $this->group;
		}

		if ($this->order && $order = $this->processOrder($this->order)) {
			$sql .= "\nORDER BY " . $order;
		}

		if ($this->limit) {
			$sql .= "\nLIMIT 0, :limit";
			$values[':limit'] = (int) $limit;
			#$sth->bindParam(':calories', $calories, PDO::PARAM_INT);
		}


		$sql .= ';';

		#echo "<pre>\n"; var_dump($wheres); echo "</pre>\n";
		#echo "<pre>\n"; var_dump($sql); echo "</pre>\n";
		#echo "<pre>\n"; var_dump($values); echo "</pre>\n";
		#exit;

		$stmt = $dao->prepare($sql);
		#echo "<pre>\n"; var_dump($stmt); echo "</pre>\n";exit;

		// Limit places ints as string when passing $values to execute, so bind instead:
		foreach ($values as $key => $value) {

			$type = PDO::PARAM_STR;
			if (is_int($value)) {
				$type = PDO::PARAM_INT;
			}
			$stmt->bindValue($key, $value, $type);
		}
		$stmt->execute();
		#echo "<pre>\n"; var_dump($stmt); echo "</pre>\n";exit;

		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		#echo "\$data: <pre>\n"; var_dump($data); echo "</pre>\n";exit;
		$this->data = $this->postQuery($data);
		#echo "<pre>\n"; var_dump($this->data); echo "</pre>\n";exit;
	}

	protected function parseValue($value, $field, $sql_fragment)
	{
        // Hacky fix to make the regix easier.
        // I've change the !_, notation for _NOT_, _AND_, _OR_ but the NOT was more complicated to
        // convert, so I've allowed the new syntax through till this point, then I'm converting it
        // back so the rest of the logic remains as-is.
        $value = str_replace('_NOT_', '!', $value);

        // Similar to above, allow , through too. I think/hope these are less likely to cause
        // problems - the main reason for changing the notation was to allow _ in values (i.e.
        // usernames should be allowed these characters)
        $value = str_replace(',', '_OR_', $value);

		$i      = 0;
		$j      = 0;
		$sql    = $value;
		$values = array();
		#$sql_fragment = '`' . $field . '` ' . $sql_fragment;
		// Switch NOT operator for NOT LIKE or !=

        #echo "<pre>\n";var_dump($value);echo "</pre>\n";
        #echo "<pre>\n";var_dump($field);echo "</pre>\n";
        #echo "<pre>\n";var_dump($sql_fragment);echo "</pre>\n";



		$not    = strpos($sql_fragment, 'LIKE') !== false ? 'NOT ' : '!';

		// general processing here

		#if (preg_match_all('/\(?([!~><=])?([^!~><=)]+)\)?/', $value, $matches, PREG_SET_ORDER)) {
		if (preg_match_all('/\(?([!><=])?([^!><=)]+)\)?/', $value, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				#echo "<pre>\n";var_dump($match);echo "</pre>\n";
				$delim = false;
				$op    = false;
				if (strpos($match[2], '_OR_') !== false) {
					$delim = '_OR_';
					$op    = ' OR ';
				} elseif (strpos($match[2], '_AND_') !== false) {
					$delim = '_AND_';
					$op    = ' AND ';
				}
                #echo "delim<pre>\n";var_dump($delim);echo "</pre>\n";
                #echo "op<pre>\n";var_dump($op);echo "</pre>\n";

				$segment = $match[2];
				$segment = str_replace($delim, $op, $segment);
				#echo "Seg1<pre>\n";var_dump($segment);echo "</pre>\n";
				if ($delim) {
					$vals = explode($delim, $match[2]);
				} else {
					$vals = array($segment);
				}
                #echo "vals<pre>\n";var_dump($vals);echo "</pre>\n";
				foreach ($vals as $val) {
					// Copy $val as they may diverge and we need the original too:
					$v      = $val;
					// Process any comparisons:
					$comp   = '';
					$number = str_replace(array('gte', 'gt', 'lte', 'lt'), '', $val);
					if (is_numeric($number)) {
						$comp   = str_replace($number, '', $val);
						$v      = $number;
						switch ($comp) {
							case 'gte':
								$comp = '>=';
								break;
							case 'gt':
								$comp = '>';
								break;
							case 'lte':
								$comp = '<=';
								break;
							case 'lt':
								$comp = '<';
								break;
							default:
								$comp = '=';
						} // switch
					}


					// Copy $sql_fragment as they may diverge and we need the original too:
					$fragment = $sql_fragment;
					// Copy $val_key as they may diverge and we need the original too:
					$val_key  = ':' . str_replace('.', '', $field) . $i;
					$vk       = $val_key;
					#echo "<pre>\n";var_dump($val_key);echo "</pre>\n";
					// If there's a comp, add it to $val_key for later replacement:
					if (!empty($comp)) {
						$val_key = $comp . ' ' . $val_key;
					}

					// Add the actual key and value to return array:
					$values[$vk] = $v;
					// Complete fragment and replace in segment:
					if ($match[1] == '!') {
						#$fragment = 'NOT ' . $fragment;
						$fragment = $not . $fragment;
					}
					$fragment = $field . ' ' . $fragment;
					// Dodgy temporary _ replacement:
					#$segment = str_replace($val, sprintf($fragment, str_replace('_', '|', $val_key)), $segment);
					$segment = str_replace($val, sprintf($fragment, $val_key), $segment);
					#echo "Seg2<pre>\n";var_dump($segment);echo "</pre>\n";
					$i++;
				}
				// If it's not the first set of matches, add AND operator:
				if ($j > 0) {
					$segment = 'AND ' . $segment;
				}
				// Update $sql with processed fragment:
				$sql = str_replace($match[2], $segment, $sql);
				// Tidy any leftover operators:
				// Dodgy temporary _ replacement:
				/*if (preg_match_all('/`.*?`/', $sql, $matches)) {
					foreach ($matches as $match) {
						$sql = str_replace($match[0], str_replace('_', '|', $match[0]), $sql);
					}
				}*/
				#$sql = str_replace('~', ' ', $sql);
				if ($not == 'NOT ') {
					$sql = str_replace('!', ' ', $sql);
				} else {
					$sql = trim($sql, '!');
				}
				// Undo dodgy temporary _ replacement:
				#$sql = str_replace('|', '_', $sql);

				$j++;
			}
		}

		$return = array('sql'=>$sql, 'values'=>$values);
		#echo "<pre>\n"; var_dump($return); echo "</pre>\n"; exit;
		return $return;
	}
}