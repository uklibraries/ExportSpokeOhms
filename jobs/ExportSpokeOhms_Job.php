<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms
 */

class ExportSpokeOhms_Job extends Omeka_Job_AbstractJob
{
    public function perform()
    {
        $item = get_record_by_id('Item', $this->_options['itemId']);
        $elementId = NULL;
        $itemType = $item->getItemType()->name;
        if ('interviews' === $itemType) {
            $plugin_dir = dirname(dirname(__FILE__));
            $export_dir = $plugin_dir . DIRECTORY_SEPARATOR . 'exports';
            $title = metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true));
            $identifier = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));
            $interview_file = $export_dir . DIRECTORY_SEPARATOR . metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true)) . '.xml';
            $output = new Output_SpokeOhms($item);
            file_put_contents($interview_file, $output->render());
            chmod($interview_file, fileperms($export_dir) | 16);
            return;
        }

        $field = 'Interview Collection';
        if ('series' === $itemType) {
            $field = 'Interview Series';
        }
        $interviewType = get_record('ItemType', array('name' => 'interviews'));
        $element = NULL;
        foreach ($interviewType->Elements as $interviewTypeElement) {
            if ($field === $interviewTypeElement->name) {
                $element = $interviewTypeElement;
                break;
            }
        }
        if (isset($element)) {
            $elementId = $element['id'];
        }

        if (isset($elementId)) {
            $plugin_dir = dirname(dirname(__FILE__));
            $export_dir = $plugin_dir . DIRECTORY_SEPARATOR . 'exports';
            $title = metadata($item, array('Dublin Core', 'Title'), array('no_filter' => true));
            $identifier = metadata($item, array('Dublin Core', 'Identifier'), array('no_filter' => true));

            $relatedItems = get_records('Item', array(
                'advanced' => array(
                    array(
                        'element_id' => $elementId,
                        'type' => 'is exactly',
                        'terms' => $title,
                    ),
                )
            ));

            if (count($relatedItems) > 0) {
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
                foreach ($relatedItems as $relatedItem) {
                    $interview_file = metadata($relatedItem, array('Dublin Core', 'Identifier'), array('no_filter' => true)) . '.xml';
                    $output = new Output_SpokeOhms($relatedItem);
                    $zip->addFromString($interview_file, $output->render());
                }
                $zip->close();
                chmod($zip_file, fileperms($zip_file) | 16);
            }
        }
    }
}
