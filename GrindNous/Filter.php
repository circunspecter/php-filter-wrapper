<?php
/**
 * Copyright (c) 2013 https://github.com/circunspecter
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace GrindNous;

class Filter
{
	const SANITIZE = 'SANITIZE';
	const VALIDATE = 'VALIDATE';

	/**
    * Indicates whether or not to continue applying the filters over a field when the first one fails.
    * @var bool
    */
	public static $multipleFieldErrors = FALSE;

	protected $data = array();
	protected $filters = array();
	protected $errors = array();

	protected $filtered = FALSE;

	/**
	 * @param array $data Field name and value pairs.
	 * @return object
	 */
	public static function factory(array $data)
	{
		return new self($data);
	}

	protected function __construct(array $data = array())
	{
		$this->data($data);
	}

	/**
	 * @param mixed $fields string|array with field(s) that apply the filter; if TRUE, the filter will be applied to all given fields.
	 * @param callable|int $filter Callback function or {@link http://www.php.net/manual/en/filter.filters.php filter} to apply.
	 * @param mixed $options Filter options; {@link http://www.php.net/manual/en/function.filter-var.php filter_var}.
	 * @return object
	 */
	public function sanitize($fields, $filter, $options = NULL)
	{
		return $this->add($fields, $filter, $options, NULL, self::SANITIZE);
	}

	/**
	 * @param mixed $fields string|array with field(s) that apply the filter; if TRUE, the filter will be applied to all given fields.
	 * @param mixed $filter Callback function, int {@link http://www.php.net/manual/en/filter.filters.php filter} or class filter to apply.
	 * @param mixed $options Filter options; {@link http://www.php.net/manual/en/function.filter-var.php filter_var}.
	 * @param string|array $error Error message or indexed array containing an error message string with ":field" keyword, followed by an associative array containing the field names and his human-readable versions.
	 * @return object
	 */
	public function validate($fields, $filter, $options = NULL, $error = NULL)
	{
		return $this->add($fields, $filter, $options, $error, self::VALIDATE);
	}

	protected function add($fields, $filter, $options = NULL, $error = NULL, $type = NULL)
	{
		$fields = $this->prepare_fields($fields);

		foreach($fields as $field)
		{
			$this->filters[$field][] = array(
				'filter' => $filter,
				'options' => $options,
				'error' => $error,
				'type' => $type
			);
		}

		return $this;
	}

	protected function prepare_fields($fields)
	{
		if($fields === TRUE)
			$fields = array_keys($this->data);
		elseif( !is_array($fields))
			$fields = array($fields);

		return $fields;
	}

	/**
	 * Indicates if all the filters have been applied correctly.
	 *
	 * @return bool
	 */
	public function ok()
	{
		return $this->errors() === array();
	}

	/**
	 * @return array Field name and error pairs.
	 */
	public function errors()
	{
		return $this->run()->errors;
	}

	/**
	 * @param array $data Set data and reset class.
	 * @return mixed Filter object if data given, otherwise, field name and filtered value pairs or FALSE if empty.
	 */
	public function data(array $data = array())
	{
		if( !empty($data))
		{
			$this->data = $data;
			$this->filters = array();
			$this->errors = array();
			$this->filtered = FALSE;
			return $this;
		}
		else
		{
			return (empty($this->data)) ? FALSE : $this->run()->data ;
		}
	}

	protected function run()
	{
		if( !$this->filtered AND !empty($this->data) AND !empty($this->filters))
		{
			foreach($this->filters as $field_name => $field_filter)
			{
				$field_value = $this->data[$field_name];

				foreach($field_filter as $filter_data)
				{
					$filter = $filter_data['filter'];
					$options = $filter_data['options'];
					$error = $filter_data['error'];
					$type = $filter_data['type'];

					if(is_callable($filter))
					{
						$result = filter_var($field_value, FILTER_CALLBACK, array('options' => $filter));
					}
					else
					{
						if($filter === 272 AND is_string($options))
						{
							$result = filter_var($field_value, $filter, array("options" => array("regexp" => $options)));
						}
						elseif($filter === 258)
						{
							$result = filter_var($field_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== NULL;
						}
						elseif($type === self::VALIDATE AND is_string($filter) AND method_exists($this, $filter))
						{
							$result = $this->$filter($field_value, $options);
						}
						else
						{
							$result = filter_var($field_value, $filter, $options);
						}
					}

					if($type === self::SANITIZE)
					{
						$field_value = $result;
					}
					elseif($type === self::VALIDATE AND $result === FALSE)
					{
						$this->error($field_name, empty($error) ? "Invalid :field" : $error );

						if(self::$multipleFieldErrors === FALSE) break;
					}
				}

				$this->data[$field_name] = $field_value;
			}

			$this->filtered = TRUE;
		}

		return $this;
	}

	protected function error($field_name, $error)
	{
		$field_replacement = $field_name;
		if(is_array($error) AND count($error) === 2)
		{
			if(is_array($error[1]) AND isset($error[1][$field_name])) $field_replacement = $error[1][$field_name];
			$error = $error[0];
		}

		$error = strtr($error, array(':field' => $field_replacement));

		if(self::$multipleFieldErrors === FALSE)
		{
			$this->errors[$field_name] = $error;
		}
		else
		{
			$this->errors[$field_name][] = $error;
		}
	}

	protected function not_empty($value)
	{
		$value = trim($value);
		return !empty($value);
	}

	protected function min_length($value, $length)
	{
		return strlen(utf8_decode($value)) >= $length;
	}

	protected function max_length($value, $length)
	{
		return strlen(utf8_decode($value)) <= $length;
	}

	protected function exact_length($value, $length)
	{
		return strlen(utf8_decode($value)) === $length;
	}

	protected function valid_email($value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL) AND preg_match('/@.+\./', $value);
	}
}