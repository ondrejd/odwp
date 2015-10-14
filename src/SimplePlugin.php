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
 *
 *     public function get_title($suffix = '', $sep = ' - ') {
 *         if (empty($suffix)) {
 *             return __('My Plugin', $this->get_textdomain());
 *         }
 *
 *         return sprintf(
 *             __('My Plugin%s%s', $this->get_textdomain()),
 *             $sep,
 *             $suffix
 *         );
 *     }
 * }
 * </pre>
 *
 * @author Ondřej Doněk, <ondrej.donek@ebrana.cz>
 * @version 0.1.6
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
     * The plug-in's title.
     * @var string $title
     */
    protected $title;

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
     * If `TRUE` than default options page in WP administration will be used.
     * @var boolean $enable_default_options_page
     */
    protected $enable_default_options_page = true;

    /**
     * Holds Latte templating engine.
     * @var \Mustache_Engine $tplEngine
     */
    protected $tplEngine;

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

        // Check if exists `cache` directory and try to create it if not.
        $cache_path = $this->get_path('cache');
        if (!file_exists($this->get_path('cache'))) {
            @mkdir($cache_path, 0777);
        }

        if (!is_dir($cache_path) || !is_writable($cache_path)) {
            throw new \Exception('You need to create cache directorry for Latte Templating Engine.');
        }

        // Initialize Latte for templating
        // @link https://doc.nette.org/cs/2.3/templating
        $this->tplEngine = new \Mustache_Engine(array(
//            'template_class_prefix' => '__MyTemplates_',
            'cache' => $cache_path,
            'cache_file_mode' => 0666,
            'cache_lambda_templates' => true,
            'charset' => 'UTF-8'
        ));
        //$this->latte->setTempDirectory($cache_path);

        // Initialize options
        $this->init_options();

        // Initialize the localization
        if (!empty($this->textdomain)) {
            load_plugin_textdomain($this->textdomain, true, $this->get_id());
        }

        // Plug-in's activation/deactivation
        if (method_exists($this, 'activate')) {
            register_activation_hook(__FILE__, array($this, 'activate'));
        }

        if (method_exists($this, 'deactivate')) {
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        }

        // Initialize plugin's widgets
        add_action('widgets_init', array($this, 'init_widgets'));

        // Below are things required only in WP administration...
        if(!is_admin()) {
            return;
        }

        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // Register TinyMCE buttons
        if (method_exists($this, 'register_tinymce_buttons')) {
            add_action('init', array($this, 'register_tinymce_buttons'));
        }

        // Use default options page
        if ($this->enable_default_options_page === true) {
            add_action('admin_menu', array($this, 'register_admin_menu'));
            add_action('admin_menu', array($this, 'register_admin_options_page'));
        }
    }

    /**
     * Returns plug-in's ID (with optional suffix).
     *
     * @since 0.1.3
     * @param string $suffix (Optional).
     * @return string
     */
    function get_id($suffix = '') {
        $ret = $this->id;
        if (!empty($suffix)) {
            $ret .= $suffix;
        }

        return $ret;
    }

    /**
     * Returns URL to the plug-in's icon.
     *
     * @since 0.1.3
     * @param string $size (Optional.)
     * @return string
     */
    public function get_icon($size = '32') {
        if (!defined('WP_PLUGIN_URL')) {
            if (!function_exists('wp_plugin_directory_constants')) {
                throw new \Exception('It looks like there is no WordPress loaded!');
            }
            wp_plugin_directory_constants();
        }

        return WP_PLUGIN_URL . '/' . $this->get_id('/icon' . $size . '.png');
    }

    /**
     * Returns path to the plugin's directory. If `$file` is provided
     * than is appended to the end of the path.
     *
     * @since 0.1.3
     * @param string $file (Optional).
     * @return string
     */
    public function get_path($file = '') {
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
     * Returns array with options of the plug-in.
     *
     * @since 0.1
     * @return array
     */
    public function get_options() {
        if (!function_exists('get_option')) {
            throw new \Exception('It looks like there is no WordPress loaded!');
        }

        return get_option($this->id . '-options');
    }

    /**
     * Returns title of the plug-in.
     *
     * @since 0.1.3
     * @param string $suffix (Optional.)
     * @param string $sep (Optional.)
     * @return string
     */
    abstract public function get_title($suffix = '', $sep = ' - ');

    /**
     * Returns the template.
     *
     * @since 0.1.3
     * @param string $tpl Name of template file.
     * @param array $params
     * @return string
     */
    public function get_template($tpl, $params = array()) {
        $path = $tpl;

        if (!file_exists($path)) {
            $path = $this->get_path('templates' . DIRECTORY_SEPARATOR . $tpl . '.latte');
        }

        if (!file_exists($path)) {
            $path = $this->get_core_path('templates' . DIRECTORY_SEPARATOR . $tpl . '.latte');
        }

        if (!file_exists($path)) {
            throw new \Exception('Template "' . $path . '" was not found!');
        }

        $tpl = $mustache->loadTemplate($tpl);
        //return $this->latte->renderToString($path, $params);
    }

    /**
     * @internal
     * @since 0.1.5
     * @param string $tpl Name of template file.
     * @param array $params
     * @return string
     */
    public function get_core_path($file = '') {
        $ret = dirname(__DIR__);

        if (!empty($file)) {
            $ret .= DIRECTORY_SEPARATOR . $file;
        }

        return $ret;
    }

    /**
     * Returns textdomain for localizing the plug-in.
     *
     * @since 0.1.4
     * @return string
     */
    public function get_textdomain() {
        return $this->textdomain;
    }

    /**
     * @deprecated
     * @since 0.1
     * @param string $suffix (Optional).
     * @return string Returns plug-in's ID (with optional suffix).
     */
    public function id($suffix = '') {
        return $this->get_id($suffix);
    }

    /**
     * @deprecated
     * @since 0.1
     * @param string $size (Optional.)
     * @return string Returns URL to the plug-in's icon.
     */
    public function icon($size = '32') {
        return $this->get_icon($size);
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

        $options_id = $this->get_id('-options');
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
     * @since 0.1.2
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
     * @deprecated
     * @since 0.1
     * @param string $file (Optional).
     * @return string Returns path to the plugin's directory. If `$file` is
     *                provided than is appended to the end of the path.
     */
    public function path($file = '') {
        return $this->get_path($file);
    }

    /**
     * @deprecated
     * @since 0.1
     * @param string $tpl Name of template file.
     * @param array $params
     * @return string Returns the template.
     */
    public function template($tpl, $params = array()) {
        return $this->get_template($tpl, $params);
    }

    /**
     * Registers administration menu for the plugin.
     *
     * @since 0.1.3
     * @return void
     */
    public function register_admin_menu() {
        add_menu_page(
            $this->get_title(),
            $this->get_title(),
            'edit_posts',
            $this->get_id(),
            array($this, 'render_admin_page'),
            $this->get_icon('16'),
            11
        );
    }

    /**
     * Registers administration menu for the plugin.
     *
     * @since 0.1.3
     * @return void
     */
    public function register_admin_options_page() {
        add_submenu_page(
            $this->get_id(),
            $this->get_title(),
            $this->get_title(__('Settings', $this->get_textdomain())),
            'manage_options',
            $this->get_id('-settings'),
            array($this, 'render_admin_options_page')
        );
    }

    /**
     * Renders default main page (in WP administration).
     *
     * @since 0.1.5
     * @return string
     */
    public function render_admin_page() {
        $params = array(
            'icon' => $this->get_icon(),
            'title' => $this->get_title(__('Settings', $this->get_textdomain()))
        );

        return $this->get_template('admin_page', $params);
    }

    /**
     * Renders default options page (in WP administration).
     *
     * @since 0.1.3
     * @return string
     */
    public function render_admin_options_page() {
        $params = array(
            'icon' => $this->get_icon(),
            'title' => $this->get_title(__('Settings', $this->get_textdomain()))
        );

        return $this->get_template('admin_options_page', $params);
    }
} // End of SimplePlugin
