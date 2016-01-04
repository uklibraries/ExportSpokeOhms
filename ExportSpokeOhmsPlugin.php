<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms
 */

define('DS', DIRECTORY_SEPARATOR);
require_once "jobs" . DS . "ExportSpokeOhms_Job.php";
require_once "models" . DS . "Output" . DIRECTORY_SEPARATOR . "SpokeOhms.php";
$pluginDir = dirname(dirname(__FILE__));
require_once $pluginDir . DS . "RecursiveSuppression" . DS . "models" . DS . "SuppressionChecker.php";

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
        $item = get_record_by_id('Item', $args['item']['id']);
        $checker = new SuppressionChecker($item);
        if ($checker->exportable()) {
            $subitemCount = $this->getSubitemCount($item);
            $exportable = $subitemCount <= 200;
            echo get_view()->partial(
                'export-ohms-panel.php',
                array('exportable' => $exportable)
            );
        }
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

    private function getSubitemCount($item) {
        $count = 1;
        $objects = get_db()->getTable('ItemRelationsRelation')->findByObjectItemId($item->id);
        $objectRelations = array();
        foreach ($objects as $object) {
            if ($object->getPropertyText() !== "Is Part Of") {
                continue;
            }
            if (!($subitem = get_record_by_id('item', $object->subject_item_id))) {
                continue;
            }
            $count += $this->getSubitemCount($subitem);
        }
        return $count;
    }
}
