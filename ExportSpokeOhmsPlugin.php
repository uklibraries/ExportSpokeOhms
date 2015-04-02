<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms
 */

require_once 'jobs' . DIRECTORY_SEPARATOR . 'ExportSpokeOhms_Job.php';

class ExportSpokeOhmsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'admin_items_show_sidebar',
        'define_routes',
    );

    protected $_filters = array(
        'response_contexts',
        'action_contexts',
    );

    public function hookAdminItemsShowSidebar($args)
    {
        echo get_view()->partial(
            'export-ohms-panel.php',
            array()
        );
    }

    public function hookDefineRoutes($args)
    {
        $args['router']->addRoute(
            'export_spoke_ohms_route',
            new Zend_Controller_Router_Route(
                'items/export-ohms/:id',
                array(
                    'module'     => 'export-spoke-ohms',
                    'controller' => 'items',
                    'action'     => 'export',
                ),
                array(
                    'id' => '\d+',
                )
            )
        );
    }

    public function filterResponseContexts($contexts)
    {
        $contexts['spoke-ohms'] = array(
            'suffix'  => 'spoke-ohms',
            'headers' => array('Content-Type' => 'application/xml'),
        );
        return $contexts;
    }

    public function filterActionContexts($contexts, $args)
    {
        if ($args['controller'] instanceof ItemsController) {
            $contexts['show'][] = 'spoke-ohms';
        }
        return $contexts;
    }
}
