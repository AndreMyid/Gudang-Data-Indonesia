<?php
/**
 * Gudang Data Indonesia
 *
 * @author		Ivan Lanin <ivan@lanin.org>
 * @author		Agastiya S. Mohammad <agastiya@gmail.com>
 * @author		Wida Sari <wida.sari@yahoo.com>
 * @since		2010-11-13 23:35
 * @last_update 2011-12-10 23:59 - IL
 */

interface output
{
	function out($data);
}

class csv implements output
{
	function out($result)
	{
		$ret = '';
		$rows = count($result);
		if(!empty($result[0]))
		{
			foreach ($result[0] as $column => $value)
				$head .= ($head ? CSV_SEP : '') . $column;
			$ret .= $head . LF;
		}

		foreach ($result as $rows)
		{
			$row = '';
			foreach ($rows as $column => $value)
				$row .= ($row ? CSV_SEP : '') . $value;
			$ret .= $row . LF;
		}

		header("Content-Disposition: attachment; filename=file.csv");
		header("Pragma: no-cache");
		header("Expires: 0");
		return $ret;
	}
}

class html implements output
{
	function out($result)
	{
		$ret  = '<table>';
		$ret .= '<tr>';
		$first_row = reset($result);
		foreach ($first_row as $column => $value)
			$ret .= '<th>' . $column . '</th>';
		$ret .= '</tr>';
		foreach ($result as $rows)
		{
			$ret .= '<tr>';
			foreach ($rows as $column => $value)
				$ret .= '<td>' . $value . '</td>';
			$ret .= '</tr>';
		}
		$ret .= '</table>';
		return $ret;
	}
}

class xml implements output
{
	/**
	 * Array to XML
	 */
	private function array_to_xml(&$array)
	{
		$ret = '';
		foreach ($array as $key => $value)
		{
			$keyName = is_numeric($key) ? 'elm' . $key : $key;
			if (!is_array($value))
			{
				$ret .= sprintf('<%1$s>%2$s</%1$s>', $keyName, $value) . LF;
			}
			else
			{
			//	$ret .= sprintf('<%1$s>', $keyName) . LF;
				$ret .= '<data>'.$this->array_to_xml($value).'</data>';
			//	$ret .= sprintf('</%1$s>', $keyName) . LF;
			}
		}
		return($ret);
	}

	/**
	 * output XML
	 */
	function out($apiData)
	{
		$ret  = '<?xml version="1.0"?>' . LF;
		$ret .= '<gdi status="1">' . LF;
		$ret .= $this->array_to_xml($apiData);
		$ret .= '</gdi>';
		header('Content-type: text/xml');
		return($ret);
	}
}

class json implements output
{
	/**
	 * output JSON
	 */
	function out($apiData)
	{
		$data = array('gdi'=>$apiData);
		$ret  = $this->json_encode_pretty($data);
		header('Content-type: application/json');
		return($ret);
	}
	/**
	 * Input an object, returns a json-ized string of said object, pretty-printed
	 *
	 * @param mixed $obj The array or object to encode
	 * @return string JSON formatted output
	 * @credit https://gist.github.com/773216
	 */
	private function json_encode_pretty($obj, $indentation = 0)
	{
		$padding0 = str_repeat("  ", $indentation);
		$padding1 = str_repeat("  ", $indentation + 1);
		switch (gettype($obj))
		{
			case 'object':
				$obj = get_object_vars($obj);
			case 'array':
				if (!isset($obj[0]))
				{
					$arr_out = array();
					foreach ($obj as $key => $val)
						$arr_out[] = '"' . addslashes($key) . '": ' .
							$this->json_encode_pretty($val, $indentation + 1);
					if (count($arr_out) < 2)
						return
							"{\n" . $padding0 .
							implode(',', $arr_out) .
							$padding0 . "\n}";
					return
						"\n" . $padding0 .
						"{\n" . $padding1 .
						implode(",\n". $padding1, $arr_out) . "\n" .
						$padding0 . "}";
				}
				else
				{
					$arr_out = array();
					$ct = count($obj);
					for ($j = 0; $j < $ct; $j++)
						$arr_out[] = $this->json_encode_pretty($obj[$j], $indentation + 1);
					if (count($arr_out) < 2)
						return
							"[\n" . $padding0 .
							implode(',', $arr_out) .
							$padding0 . "\n]";
					return
						"\n" . $padding0 .
						"[\n" . $padding1 .
						implode(",\n". $padding1, $arr_out) . "\n" .
						$padding0 . "]";
				}
				break;
			case 'NULL':
				return 'null';
				break;
			case 'boolean':
				return $obj ? 'true' : 'false';
				break;
			case 'integer':
			case 'double':
				return $obj;
				break;
			case 'string':
			default:
				$obj = str_replace(array('\\','"',), array('\\\\','\"'), $obj);
				return '"' . $obj . '"';
				break;
		}
	}
}

class graph implements output
{
	/**
	 * output graph
	 */
	function out($apiData)
	{
		$data = array('gdi'=>$apiData);
		return $apiData;
	}
}

class meta implements output
{
	/**
	 * output meta
	 */
	function out($apiData)
	{
		$data = array('gdi'=>$apiData);

		$first_key = "";
		foreach($apiData as $index=>$data)
		{
			$counter = 0;
			foreach($data as $i=>$d)
			{
				if($counter == sizeof($data))
					$counter = 0;

				if($first_key == "") $first_key = $i;
				if($i == $first_key)
					$ticks[] = $d;
				else
				{
					if(is_array($columns))
					{
						if(in_array($counter, $columns))
						{
							$plot_data[$i][$data[$first_key]] = $d;
							$series[$i] = "{label:'".$i."'}";
						}
					}
					else
					{
						$plot_data[$i][$data[$first_key]] = $d;
						$series[$i] = "{label:'".$i."'}";
					}
				}
				$counter++;
			}
		}

		$ticks_str = "['".(implode("','", $ticks))."']";

		$data_str = "[";
		foreach($plot_data as $index=>$data)
		{
			$data_strs[] .= "[".implode(",", $data)."]";
		}
		$data_str .= implode(",", $data_strs);
		$data_str .= "]";

		$series_str = "[".implode(",", $series)."]";

		$width = 30 * sizeof($ticks);
		if ($width > 800) $width = 800;

		return $apiData;
	}
}

?>