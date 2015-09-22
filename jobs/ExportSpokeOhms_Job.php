<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms
 */

define('DS', DIRECTORY_SEPARATOR);
$pluginDir = dirname(dirname(dirname(__FILE__)));
require_once $pluginDir . DS . "RecursiveSuppression" . DS . "models" . DS . "SuppressionChecker.php";

class ExportSpokeOhms_Job extends Omeka_Job_AbstractJob
{
    public function perform()
    {
        $item = get_record_by_id('Item', $this->_options['itemId']);
        $elementId = NULL;
        $itemType = $item->getItemType()->name;
        $checker = new SuppressionChecker($item);

        if (!$checker->exportable()) {
            return;
        }

        $output = new Output_SpokeOhms($item);

        $plugin_dir = dirname(dirname(__FILE__));
        $export_dir = $plugin_dir . DIRECTORY_SEPARATOR . 'exports';

        switch ($itemType) {
        case 'interviews':
            $title = metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true));
            $identifier = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));
            $interview_file = $export_dir . DIRECTORY_SEPARATOR . metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true)) . '.xml';
            $ichecker = new SuppressionChecker($item);
            if ($ichecker->exportable()) {
                $output = new Output_SpokeOhms($item);
                file_put_contents($interview_file, $output->render());
                chmod($interview_file, fileperms($export_dir) | 16);
            }
            break;
        case 'series':
            $title = metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true));
            $identifier = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));
            mkdir($export_dir, 0775, true);
            chmod($export_dir, fileperms($export_dir) | 16);
            $zip_file = $export_dir
                      . DIRECTORY_SEPARATOR
                      . $identifier
                      . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zip_file, ZipArchive::CREATE) !== true) {
                return;
            }

            $objects = get_db()->getTable('ItemRelationsRelation')->findByObjectItemId($item->id);
            foreach ($objects as $object) {
                if ($object->getPropertyText() !== "Is Part Of") {
                    continue;
                }
                if (!($subitem = get_record_by_id('item', $object->subject_item_id))) {
                    continue;
                }
                $interview_file = metadata($subitem, array('Dublin Core', 'Identifier'), array('no_filter' => true)) . '.xml';
                $ichecker = new SuppressionChecker($subitem);
                if ($ichecker->exportable()) {
                    $output = new Output_SpokeOhms($subitem);
                    $zip->addFromString($interview_file, $output->render());
                }
            }

            $zip->close();
            chmod($zip_file, fileperms($zip_file) | 16);
            break;
        case 'collections':
            $title = metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true));
            $identifier = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));
            mkdir($export_dir, 0775, true);
            chmod($export_dir, fileperms($export_dir) | 16);
            $zip_file = $export_dir
                      . DIRECTORY_SEPARATOR
                      . $identifier
                      . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zip_file, ZipArchive::CREATE) !== true) {
                return;
            }

            $objects = get_db()->getTable('ItemRelationsRelation')->findByObjectItemId($item->id);
            foreach ($objects as $object) {
                if ($object->getPropertyText() !== "Is Part Of") {
                    continue;
                }
                if (!($subitem = get_record_by_id('item', $object->subject_item_id))) {
                    continue;
                }

                $subobjects = get_db()->getTable('ItemRelationsRelation')->findByObjectItemId($subitem->id);
                foreach ($subobjects as $subobject) {
                    if ($subobject->getPropertyText() !== "Is Part Of") {
                        continue;
                    }
                    if (!($interview = get_record_by_id('item', $subobject->subject_item_id))) {
                        continue;
                    }
                    $interview_file = metadata($interview, array('Dublin Core', 'Identifier'), array('no_filter' => true)) . '.xml';
                    $ichecker = new SuppressionChecker($interview);
                    if ($ichecker->exportable()) {
                        $output = new Output_SpokeOhms($interview);
                        $zip->addFromString($interview_file, $output->render());
                    }
                }
            }

            $zip->close();
            chmod($zip_file, fileperms($zip_file) | 16);
            break;
        }
    }
}
