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
 * @version 0.1.7
 */
abstract class SimplePlugin {
    /**
     * Identifier of the plug-in.
     * @var string $id
     */
    protected $id;

    /**
     * Default options of the plug-in.
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
     * Position in WP administration menu.
     * @var integer $admin_menu_position
     */
    protected $admin_menu_position;

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
     * Holds `TRUE` if textdomain is already initialized.
     * @var boolean $locales_initialized
     */
    private $locales_initialized;

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

        // Initialize Mustache for templating
        // @link https://github.com/bobthecow/mustache.php
        $this->tplEngine = new \Mustache_Engine(array(
            'cache' => $cache_path,
            'cache_file_mode' => 0666,
            'cache_lambda_templates' => true,
            'charset' => 'UTF-8'
        ));

        // Initialize options
        $this->init_options();

        // Initialize the localization
        $this->init_locales();

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
     * @return string
     */
    public function get_template($tpl) {
        $path = $tpl;

        if (!file_exists($path)) {
            $path = $this->get_path('templates' . DIRECTORY_SEPARATOR . $tpl . '.mustache');
        }

        if (!file_exists($path)) {
            $path = $this->get_core_path('templates' . DIRECTORY_SEPARATOR . $tpl . '.mustache');
        }

        if (!file_exists($path)) {
            throw new \Exception('Template "' . $path . '" was not found!');
        }

        return file_get_contents($path);
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
     * Initialize the localization.
     * 
     * @return void
     */
    public function init_locales() {
        if (!empty($this->textdomain) && $this->locales_initialized !== true) {
            load_plugin_textdomain($this->textdomain, true, $this->get_id());
            $this->locales_initialized = true;
        }
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
            empty($this->admin_menu_position) ? null : $this->admin_menu_position
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
     * @return void
     */
    public function render_admin_page() {
        $tpl = $this->get_template('admin_page');
        $params = array(
            'icon' => $this->get_icon(),
            'title' => $this->get_title()
        );

        echo $this->tplEngine->render($tpl, $params);
    }

    /**
     * Renders default options page (in WP administration).
     *
     * @since 0.1.3
     * @return void
     */
    public function render_admin_options_page() {
        $tpl = $this->get_template('admin_options_page');
        $default = $this->options;
        $current = $this->get_options();
        $params = array(
            'icon' => $this->get_icon(),
            'title' => $this->get_title(__('Settings', $this->get_textdomain())),
            'form_url' => get_bloginfo('url') . '/wp-admin/admin.php?page=' . $this->get_id('-settings')
        );

        // Update options if necessarry
        if (filter_input(INPUT_POST, 'submit')) {
            $res = $this->save_options($default);

            $params['message'] = true;
            $params['message_id'] = 'message_'.rand(0, 99);

            if ($res === true) {
                $params['message_type'] = 'updated';
                $params['message_text'] = __(
                    'Options were successfully updated!',
                    $this->get_textdomain()
                );
                $current = $this->get_options();
            }
            else {
                $params['message_type'] = 'error';
                $params['message_text'] = __(
                    'Options were <b>NOT</b> successfully updated!',
                    $this->get_textdomain()
                );
            }
        }

        // Prepare options for rendering
        $params['options'] = $this->prepare_options_for_render($default, $current);

        // Render template
        echo $this->tplEngine->render($tpl, $params);
    }

    /**
     * @internal
     * @since 0.1.7
     * @param array $default Default options.
     * @param array $current Currently set options.
     * @return array Returns prepared options for rendering.
     */
    private function prepare_options_for_render($default, $current) {
        $params = array();

        foreach ($default as $option) {
            $param = array();
            $param['key'] = $option->key;
            $param['label'] = $option->label;

            $key = $option->key;
            $value = array_key_exists($key, $current) ? $current[$key] : $default[$key];

            switch ($option->type) {
                case \odwp\PluginOption::TYPE_BOOL:
                    $param['is_bool'] = true;
                    $param['value'] = boolval($value);
                    break;

                case \odwp\PluginOption::TYPE_NUMBER:
                    $param['is_number'] = true;
                    $param['value'] = intval($value);
                    break;

                default:
                case \odwp\PluginOption::TYPE_STRING:
                    $param['is_string'] = true;
                    $param['value'] = strval($value);
                    break;
            }

            if (!empty($option->description)) {
                $param['description'] = $option->description;
            }

            $params[] = $param;
        }

        return $params;
    }

    /**
     * @internal
     * @since 0.1.7
     * @param array $default Default options.
     * @return boolean `TRUE` if saving was successfull otherwise `FALSE`.
     */
    private function save_options($default) {
        $updated = array();
        $updated['latest_used_version'] = $this->version;

        foreach ($default as $option) {
            $new_val = filter_input(INPUT_POST, 'option-' . $option->key);

            if ($option->type == \odwp\PluginOption::TYPE_BOOL) {
                $new_val = !is_null($new_val);
            }
            else if (is_null($new_val)) {
                $new_val = $option->value;
            }

            $updated[$option->key] = $new_val;
        }

        return update_option($this->get_id('-options'), $updated);
    }
}
