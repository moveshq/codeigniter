<?php

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014-2019 British Columbia Institute of Technology
 * Copyright (c) 2019-2020 CodeIgniter Foundation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author     CodeIgniter Dev Team
 * @copyright  2019-2020 CodeIgniter Foundation
 * @license    https://opensource.org/licenses/MIT	MIT License
 * @link       https://codeigniter.com
 * @since      Version 4.0.0
 */

namespace CodeIgniter\Format;

use CodeIgniter\Format\Exceptions\FormatException;

/**
 * Format Class
 *
 * @package CodeIgniter\Format
 */
class Format
{

	/**
	 * Configuration class instance.
	 *
	 * @var \Config\Format
	 */
	protected $config;

	//--------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param \Config\Format $config
	 */
	public function __construct($config)
	{
		$this->config = $config;
	}

	//--------------------------------------------------------------------

	/**
	 * A Factory method to return the appropriate formatter for the given mime type.
	 *
	 * @param string $mime
	 *
	 * @return CodeIgniter\Format\FormatterInterface
	 */
	public function getFormatter(string $mime): FormatterInterface
	{
		if (! array_key_exists($mime, $this->config->formatters))
		{
			throw FormatException::forInvalidMime($mime);
		}

		$class = $this->config->formatters[$mime];

		if (! class_exists($class))
		{
			throw FormatException::forInvalidFormatter($class);
		}

		return new $class();
	}

	/**
	 * Get instance of format configuration class
	 *
	 * @return \Config\Format
	 */
	public function getConfig(): object
	{
		if (! $this->config instanceof \Config\Format)
		{
			return $this->config;
		}

		return new \Config\Format();
	}
}
