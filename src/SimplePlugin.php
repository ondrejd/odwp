<?php
/**
* Tiny library for WordPress plug-ins.
*
* @author Ondrej Donek, <ondrejd@gmail.com>
* @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License 2.0
*/

namespace odwp;

/**
 * Parent class for WordPress plug-ins.
 *
 * Usage:
 * <pre>
 * class MySimplePlugin extends \odwp\SimplePlugin {
 *     protected $id = 'od-downloads-plugin';
 *     protected $version = '0.5';
 *     protected $texdomain = 'oddp';
 *     protected $options = array(
 *         'main_downloads_dir' => 'wp-content/downloads/',
 *         'downloads_page_id' => 0,
 *         'downloads_thumb_size_width' => 146,
 *         'downloads_shortlist_max_count' => 2
 *     );
 * }
 * </pre>
 *
 * @author Ondřej Doněk, <ondrej.donek@ebrana.cz>
 * @version 0.1.1
 */
abstract class SimplePlugin {
    /**
     * Identifier of the plug-in.
     * @var string $id
     */
    protected $id;

    /**
     * Default options of the plugin.
     * @var array $options
     */
    protected $options;

    /**
     * Textdomain of the plug-in.
     * @var string $textdomain
     */
    protected $texdomain;

    /**
     * Version of the plug-in.
     * @var string $version
     */
    protected $version;

    /**
     * Widgets provided by the plug-in. Array should contains class names of the widgets.
     * @var array $widgets
     */
    protected $widgets;

    /**
     * Constructor.
     *
     * @since 0.1.1
     * @return void
     */
    public function __construct() {
        if (
            !function_exists('load_plugin_textdomain') ||
            !function_exists('register_activation_hook') ||
            !function_exists('register_deactivation_hook') ||
            !function_exists('add_action') ||
            !function_exists('is_admin')
        ) {
            throw new \Exception('It looks like there is no WordPress loaded!');
        }

        // Initialize options
        $this->init_options();

        // Initialize the localization
        if (!empty($this->textdomain)) {
            load_plugin_textdomain($this->textdomain, $this->path());
        }

        // Plug-in's activation/deactivation
        if (method_exists($this, 'activate')) {
            register_activation_hook(__FILE__, array($this, 'activate'));
        }

        if (method_exists($this, 'deactivate')) {
            register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
        }

        // Initialize plugin's widgets
        add_action('widgets_init', array($this, 'init_widgets'));

        // Below are things required only in WP administration...
        if(!is_admin()) {
            return;
        }

        // Register admin menu
        if (function_exists($this, 'register_admin_menu')) {
            add_action('admin_menu', array($this, 'register_admin_menu'));
        }

        // Register TinyMCE buttons
        if (method_exists($this, 'register_tinymce_buttons')) {
            add_action('init', array($this, 'register_tinymce_buttons'));
        }
    }

    /**
     * Returns array with options of the plug-in.
     *
     * @return array
     */
    public function get_options() {
        if (!function_exists('get_option')) {
            throw new \Exception('It looks like there is no WordPress loaded!');
        }

        return get_option($this->id . '-options');
    }

    /**
     * @since 0.1
     * @param string $suffix (Optional).
     * @return string Returns plug-in's ID (with optional suffix).
     */
    public function id($suffix = '') {
        $ret = $this->id;
        if (!empty($suffix)) {
            $ret .= $suffix;
        }

        return $ret;
    }

    /**
     * @since 0.1
     * @param string $size (Optional.)
     * @return string Returns URL to the plug-in's icon.
     */
    public function icon($size = '32') {
        if (!defined('WP_PLUGIN_URL')) {
            if (!function_exists('wp_plugin_directory_constants')) {
                throw new \Exception('It looks like there is no WordPress loaded!');
            }
            wp_plugin_directory_constants();
        }

        return WP_PLUGIN_URL . '/' . $this->id('/icon' . $size . '.png');
    }

    /**
     * Initialize plugin's options
     *
     * @since 0.1.1
     * @return array
     */
    public function init_options() {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            throw new \Exception('It looks like there is no WordPress loaded!');
        }

        if (!is_array($this->options)) {
            $this->options = array();
        }

        $options_id = $this->id('-options');
        $options = get_option($options_id);
        $need_update = false;

        if($options === false) {
            $need_update = true;
            $options = array();
        }

        foreach($this->options as $key => $value) {
            if(!array_key_exists($key, $options)) {
                $options[$key] = $value;
            }
        }

        if(!array_key_exists('latest_used_version', $options)) {
            $options['latest_used_version'] = $this->version;
            $need_update = true;
        }

        if($need_update === true) {
            update_option($options_id, $options);
        }

        return $options;
    }

    /**
     * Initializes widgets (if are any defined).
     *
     * @return void
     */
    public function init_widgets() {
        if (!is_array($this->widgets)) {
            $this->widgets = array();
        }

        if (count($this->widgets) > 0) {
            return;
        }

        foreach ($this->widgets as $widget) {
            if (class_exists($widget)) {
                register_widget($widget);
            }
        }
    }

    /**
     * @since 0.1
     * @param string $file (Optional).
     * @return string Returns path to the plugin's directory. If `$file` is
     *                provided than is appended to the end of the path.
     */
    public function path($file = '') {
        if (!defined('WP_PLUGIN_DIR')) {
            if (function_exists('wp_plugin_directory_constants')) {
                throw new \Exception('It looks like there is no WordPress loaded!');
            }
            wp_plugin_directory_constants();
        }

        // TODO There is probably better constant than WP_CONTENT_DIR!
        $path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->id;
        if (!empty($file)) {
            $path .= DIRECTORY_SEPARATOR . $file;
        }

        return $path;
    }

    /**
     * @since 0.1
     * @param string $tpl Name of template file.
     * @param array $params
     * @return string Returns the template.
     */
    public function template($tpl, $params = array()) {
        $path = $tpl;
        if (!file_exists($path)) {
            $path = $this->path('templates' . DIRECTORY_SEPARATOR . $path . '.phtml');
            if (!file_exists($path)) {
                return '';
            }
        }

        throw new \Exception('Not implemented yet (use Latte)!');

        ob_start();
        extract($params);
        include $path;
        $ret = ob_get_clean();

        return $ret;
    }
} // End of SimplePlugin
