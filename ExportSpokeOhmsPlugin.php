<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms
 */

class ExportSpokeOhmsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_filters = array(
        'response_contexts',
        'action_contexts',
    );

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
