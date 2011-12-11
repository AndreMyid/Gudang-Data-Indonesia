<?php
$a = $_GET['a'];
$id = $_GET['id'];
$list = false;
$catalog = new catalog();
$catalog->action = $a;
switch ($a)
{
	case 'delete':
		$catalog->delete($id);
		break;
	case 'create':
		$ret = $catalog->create($_POST);
		break;
	case 'update':
		$ret = $catalog->update($id, $_POST);
		break;
	default:
		$list = true;
		$ret = $catalog->browse();
}
echo(sprintf('<a href="?a=%1$s">%2$s</a>',
	$list ? 'create' : 'browse',
	$list ? 'Baru' : 'Daftar'
));
echo($ret);

/**
 * Perform catalog operation
 */

class catalog
{
	var $path_name; // real path name without ending (back)slash
	var $file_name; // file name with extension
	var $file_path; // full file name with path
	var $entries; 	// array of entries, indexed from 1
	var $fields; 	// definition of field used in meta
	var $index; 	// index of active record
	var $action; 	// action performed: browse, create, update, delete

	function __construct($path_name = '', $file_name = '')
	{
		// define dir, file, and fullname
		if (!$path_name) $path_name = dirname(__FILE__);
		if (!$file_name) $file_name = 'catalog.json';
		$this->path_name = $path_name;
		$this->file_name = $file_name;
		$this->file_path = $this->path_name . '/' . $this->file_name;

		// define fields
		$this->fields = json_decode('{
			"file_name":"",
			"title":{"size":30},
			"description":{"input":"text"},
			"license":"",
			"keys":"",
			"fields":{"input":"text"},
			"author":{"size":30},
			"author_email":{"size":30},
			"source":{"size":30},
			"source_url":{"size":30},
			"created":""
		}', true);

		// create if not exists
		if (!file_exists($this->file_path))
		{
			$handle = fopen($this->file_path, 'w');
			fclose($handle);
		}

		// read the whole entries
		$this->entries = json_decode(file_get_contents($this->file_path), true);
	}

	/**
	 * Browse catalog
	 */
	function browse()
	{
		if ($this->entries)
		{
			// header
			$thead .= '<tr>';
			$thead .= '<th>&nbsp;</th>';
			foreach ($this->fields as $col => $field)
			{
				$thead .= sprintf('<th>%1$s</th>', $col);
			}
			$thead .= '</tr>';

			// body
			foreach ($this->entries as $row => $entry)
			{
				$tbody .= '<tr>';
				$tbody .= sprintf('<td><a href="?a=update&id=%1$s">E</a> ' .
					'<a href="?a=delete&id=%1$s">H</a></td>', $row);
				foreach ($this->fields as $col => $field)
					$tbody .= sprintf('<td>%1$s</td>', $entry[$col]);
				$tbody .= '</tr>';
			}

			// wrapup
			$thead = '<thead>' . $thead . '</thead>';
			$tbody = '<tbody>' . $tbody . '</tbody>';
			$ret = '<table>' . $thead . $tbody . '</table>';
		}
		return($ret);
	}

	/**
	 * Create entry
	 */
	function create($posted)
	{
		$ret = $this->update(0, $posted);
		return($ret);
	}

	/**
	 * Update entry
	 */
	function update($index, $posted)
	{
		// define index: if not exists, assume create
		$this->index = $index;
		if (!array_key_exists($index, $this->entries))
		{
			$this->action = 'create';
			$this->index = 0;
		}

		if (!$posted) // normal operation, not POST
		{
			// body
			foreach ($this->fields as $col => $field)
			{
				if (!is_array($field)) $field = array();
				$value = $this->index ? $this->entries[$this->index][$col] : '';
				$size = $field['size'] ? $field['size'] : 20;
				$input = sprintf('<input id="%1$s" name="%1$s" ' .
					'type="text" value="%2$s" size="%3$s" />', $col, $value, $size);

				// input text
				if ($field['input'] == 'text')
					$input = sprintf('<textarea id="%1$s" name="%1$s">' .
						'%2$s</textarea>', $col, $value);

				// final
				$tbody .= sprintf('<tr><th>%1$s</th><td>%2$s</td></tr>',
					$col, $input);
			}

			// output html
			$url = sprintf('?a=%1$s&id=%2$s', $this->action, $this->index);
			$ret .= sprintf('<form method="post" action="%1$s">', $url);
			$ret .= '<table><tbody>' . $tbody . '</tbody></table>';
			$ret .= '<input type="submit"><input type="reset">';
			$ret .= '</form>';
		}
		else // POST operation
		{
			// get active record (or last index if not exists)
			if (!$this->index)
			{
				$last_index = 0;
				foreach ($this->entries as $row => $entry)
					if ($row > $last_index) $last_index = $row;
				$last_index++;
				$entry = &$this->entries[$last_index];
			}
			else
				$entry = &$this->entries[$this->index];

			// push value, write, and redirect
			foreach ($this->fields as $col => $field)
				$entry[$col] = $posted[$col];
			$this->write();
			header('Location: ?a=browse');
		}
		return($ret);
	}

	/**
	 * Delete entry
	 */
	function delete($index)
	{
		$this->index = $index;
		if (array_key_exists($this->index, $this->entries))
		{
			unset($this->entries[$this->index]);
			$this->write();
		}
		header('Location: ?a=browse');
	}

	/**
	 * Write all
	 */
	function write()
	{
		file_put_contents($this->file_path, json_encode($this->entries));
	}

}