# odwp

Library for rapid development of [WordPress](https://wordpress.org/) plug-ins.

## Usage

Usage is simple:

```php
/**
 * My simple plug-in.
 */
class MySimplePlugin extends \odwp\SimplePlugin {
    protected $id = 'my-simple-plugin';
    protected $version = '0.1';
    protected $texdomain = 'my-simple-plugin';

    public function get_title($suffix = '', $sep = ' - ') {
        if (empty($suffix)) {
            return __('My Simple Plugin', $this->get_textdomain());
        }

        return sprintf(
            __('My Simple Plugin%s%s', $this->get_textdomain()),
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
