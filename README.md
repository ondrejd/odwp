# odwp

	> This code is still under initial development so use it on your __own__ risk.

Tiny library for [WordPress](https://wordpress.org/) plug-ins.

## Usage

Usage is simple:

```php
/**
 * My simple plug-in.
 */
class MySimplePlugin extends \odwp\SimplePlugin {
    protected $id = 'my-plugin';
    protected $version = '0.1';
    protected $texdomain = 'my-plugin';

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
