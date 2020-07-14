<?php namespace Electrica\Parser;

use Backend;
use Electrica\Parser\Widgets\Popup;
use System\Classes\PluginBase;

/**
 * Parser Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Parser',
            'description' => 'No description provided yet...',
            'author'      => 'Electrica',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {

    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return []; // Remove this line to activate

        return [
            'Electrica\Parser\Components\MyComponent' => 'myComponent',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'electrica.parser.some_permission' => [
                'tab' => 'Parser',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'parser' => [
                'label'       => 'Parser',
                'url'         => Backend::url('electrica/parser/parser'),
                'icon'        => 'icon-leaf',
                'permissions' => ['electrica.parser.*'],
                'order'       => 500,
            ],
        ];
    }

    public function registerFormWidgets()
    {
        return [];
        return [
            'Electrica\Parser\Widgets\Popup' => [
                'label' => 'Popup',
                'code' => 'popup'
            ]
        ];
    }
}
