<?php
namespace PhpMetrics;

class Module extends \ZRay\ZRayModule {

    public function config() {
        return array(
            'extension' => array(
                'name' => 'phpmetrics',
            ),
            // Prevent those default panels from being displayed
            'defaultPanels' => array(
            ),
            // configure all custom panels
            'panels' => array(
                'files' => array(
                    'display'       => true,
                    'alwaysShow' => true,
                    'logo'          => 'logo.png',
                    'menuTitle' 	=> 'PhpMetrics',
                    'panelTitle'	=> 'PhpMetrics',
                ),
            )
        );
    }
}
