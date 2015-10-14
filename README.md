# odwp

	> This code is still under initial development so use it on your __own__ risk.

Tiny library for [WordPress](https://wordpress.org/) plug-ins.

## Usage

Usage is simple:

```php
/**
 * My simple plugin.
 */
class MySimplePlugin extends \odwp\SimplePlugin {
    protected $id = 'od-downloads-plugin';
    protected $version = '0.5';
    protected $texdomain = 'oddp';
    protected $options = array(
        'main_downloads_dir' => 'wp-content/downloads/',
        'downloads_page_id' => 0,
        'downloads_thumb_size_width' => 146,
        'downloads_shortlist_max_count' => 2
    );

    public function get_title($suffix = '', $sep = ' - ') {
        if (empty($suffix)) {
            return __('My Plugin', $this->get_textdomain());
        }

        return sprintf(
            __('My Plugin%s%s', $this->get_textdomain()),
            $sep,
            $suffix
        );
    }
} // End of MySimplePlugin

// ===========================================================================
// Plugin initialization

global $my_plugin;
$my_plugin = new MySimplePlugin();
```
