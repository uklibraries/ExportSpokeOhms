<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms
 */

class ExportSpokeOhms_ItemsController extends Omeka_Controller_AbstractActionController
{
    public function exportAction()
    {
        $itemId = $this->_getParam('id');
        Zend_Registry::get('bootstrap')->getResource('jobs')->sendLongRunning(
            'ExportSpokeOhms_Job', array(
                'itemId' => $itemId,
            )
        );
        return $this->_helper->redirector->gotoRoute(
            array(
                'controller' => 'items',
                'action'     => 'show',
                'id'         => $itemId,
            ),
            'default'
        );
    }
}
