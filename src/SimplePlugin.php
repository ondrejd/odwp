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
 * @version 0.1
 */
abstract class SimplePlugin {
    /**
     * @var string $id ID of the plug-in.
     */
    protected $id;

    /**
     * @var string version Plug-in's version.
     */
    protected $version;

    /**
     * @var string version Plug-in's textdomain.
     */
    protected $texdomain;

    /**
     * @var array $options Default options.
     */
    protected $options;

    /**
     * @since 0.1
     * @param string $size (Optional.)
     * @return string Returns URL to the plug-in's icon.
     */
    public function icon($size = '32') {
        if (!defined('WP_PLUGIN_URL')) {
            if (function_exists('wp_plugin_directory_constants')) {
                throw new \Exception('It looks like there is no WordPress loaded!');
            }
            wp_plugin_directory_constants();
        }

        return WP_PLUGIN_URL . '/' . $this->id('/icon' . $size . '.png');
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
