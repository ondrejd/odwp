<?php
/**
* Tiny library for WordPress plug-ins.
*
* @author Ondrej Donek, <ondrejd@gmail.com>
* @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
*/

namespace odwp;

/**
 * Simple class representing single plug-in's option.
 *
 * Usage:
 * <pre>
 * $my_option = new \odwp\PluginOption(
 *     'my_option',
 *     'My option',
 *     \odwp\PluginOption::TYPE_BOOL,
 *     'true',
 *     'Optional description of my option.'
 * );
 * </pre>
 *
 * @author Ondřej Doněk, <ondrej.donek@ebrana.cz>
 * @version 0.1.7
 */
class PluginOption {
	const TYPE_STRING = 'string';
	const TYPE_NUMBER = 'number';
	const TYPE_BOOL = 'bool';

	/**
	 * @var string $key
	 */
	public $key;

	/**
	 * @var string $label
	 */
	public $label;

	/**
	 * @var string $type
	 */
	public $type;

	/**
	 * @var string $value
	 */
	public $value;

	/**
	 * @var string $description
	 */
	public $description;

	/**
	 * Constructor.
	 *
	 * @since 0.1.7
	 * @param string $key
	 * @param string $label
	 * @param string $type (Optional.)
	 * @param string $value (Optional.)
	 * @param string $description (Optional.)
	 * @return void
	 */
	public function __construct($key, $label, $type = self::TYPE_STRING, $value = '', $description = '') {
		$this->key = $key;
		$this->label = $label;
		$this->type = $type;
		$this->value = $value;
		$this->description = $description;
	}
}