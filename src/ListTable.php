<?php
/**
* Tiny library for WordPress plug-ins.
*
* @author Ondrej Donek, <ondrejd@gmail.com>
* @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
*/

namespace odwp;

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * ...
 *
 * @author Ondřej Donek, <ondrejd@gmail.com>
 * @since 0.2
 */
abstract class ListTable extends \WP_List_Table {

    /**
     * Holds Twig templating engine.
     * @var \Twig_Environment $tplEngine
     */
    protected $twig;

	/**
	 * @var array $column_headers
	 */
	protected $columns;

	/**
	 * @var array $column_headers
	 */
	protected $items;

	/**
	 * @var string $column_headers
	 */
	protected $page;

    /**
     * Constructor.
     *
     * @since 0.2
	 * @param \Twig_Environment $twig
     * @return void
     */
    public function __construct($id, $items, \Twig_Environment $twig) {
		$this->page = $page;
		$this->items = $items;
		$this->twig = $twig;

		add_action('admin_head', array($this, 'render_admin_header'));
		add_filter('set-screen-option', 'set_option', 10, 3);
	}

	/**
     * @since 0.2
	 * @return array Returns table columns.
	 */
	abstract public function get_columns();

	/**
     * @since 0.2
	 * @return array Returns hidden table columns.
	 */
	abstract public function get_hidden_columns();

	/**
     * @since 0.2
	 * @return array Returns sortable table columns.
	 */
	abstract public function get_sortable_columns();

	/**
     * @since 0.2
	 * @return string Returns name of defaultly sorted column.
	 */
	abstract public function get_default_sort_column();

	/**
     * @since 0.2
	 * @return array Returns table items.
	 */
	abstract public function get_items();

	/**
     * @since 0.2
	 * @return string Returns message that no items are in the table.
	 */
	abstract public function no_items();

	/**
	 * Returns supported bulk actions.
	 *
     * @since 0.2
	 * @return array
	 */
	function get_bulk_actions() {
		$actions = array(
			'delete' => 'Delete'
		);

		return $actions;
	}

	/**
	 * Retrieve contents for checkbox cell.
	 *
     * @since 0.2
	 * @param mixed $item
	 * @param string $column
	 * @return string
	 */
	public function cell_checkbox($item, $column) {
		return sprintf(
			'<input type="checkbox" name="book[]" value="%s"/>', $item[$column]
		);
    }

	/**
	 * Retrieve contents for normal cell.
	 *
     * @since 0.2
	 * @param mixed $item
	 * @param string $column
	 * @return string
	 */
	public function cell_default($item, $column) {
		$ret = $this->val($item, $column);

		return is_null($ret) ? '' : $ret;
	}

	/**
	 * Retrieve contents for cell with actions within.
	 *
     * @since 0.2
	 * @param mixed $item
	 * @param string $column
	 * @return string
	 */
	public function cell_with_actions($item, $column) {
		return sprintf('%s', $item[$column]);
    }

	/**
	 * Prepare table items.
	 *
     * @since 0.2
	 * @return void
	 */
	function prepare_items() {
		$this->columns = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns()
		);

		$this->items = $this->get_items();

		usort($this->example_data, array($this, 'usort_reorder'));

		//paging
		//$per_page = $this->get_items_per_page('books_per_page', 5);
		//$current_page = $this->get_pagenum();
	}

	/**
	 * @param xxx $status
	 * @param string $option
	 * @param mixed $value
	 * @return mixed
	 */
	function set_option($status, $option, $value) {
		return $value;
	}

	/**
	 * Render table.
	 *
     * @since 0.2
	 * @return string
	 */
	public function render() {
		$params = array();

		$this->prepare_items();
		
		// ... TBD ...

		return $this->twig->render('list_table.twig', $params);
	}

	/**
	 * Render admin header.
	 *
     * @since 0.2
	 * @return void
	 */
	public function render_admin_header() {
		//$page = (isset($_GET['page'])) ? esc_attr($_GET['page']) : false;
		$page = filter_input(INPUT_GET, 'page');
		if ($this->page != $page) {
			return;
		}

		echo '<style type="text/css">';
		echo $this->twig->render('list_table.css.twig', array());
		echo '</style>';
	}

	/**
	 * @internal
     * @since 0.2
	 * @param mixed $item1
	 * @param mixed $item2
	 * @return integer
	 */
	protected function usort_reorder($item1, $item2) {
		$orderby = filter_input(INPUT_GET, 'orderby');
		if (empty($orderby)) {
			return 0;
		}

		$order = filter_input(INPUT_GET, 'order');
		if (empty($orderby)) {
			return 0;
		}

		$first = $this->val($item1, $orderby);
		$second = $this->val($item2, $orderby);
		if (is_null($first) && is_null($second)) {
			return 0;
		}

		$result = strcmp($first, $second);

		return ($order === 'asc') ? $result : -$result;
	}

	/**
	 * @internal
	 * @since 0.2
	 * @param mixed $data
	 * @param string $key
	 * @return mixed
	 */
	protected function val($data, $key) {
		if (is_array($data)) {
			if (array_key_exists($key, $data)) {
				return $a[$key];
			}
		}

		if (is_object($data)) {
			if (property_exists($data, $key)) {
				return $a->$key;
			}
		}
	}
}
