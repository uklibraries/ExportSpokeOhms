<?php
/**
 * Export SPOKEdb OHMS
 *
 * @copyright 2015 Michael Slone <m.slone@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @package Omeka\Plugins\ExportSpokeOhms */

class Output_SpokeOhms
{
    public function __construct($item)
    {
        $this->_item = $item;
        $this->_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ROOT/>');
        $this->_itemType = $item->getItemType()->name;
        if ('interviews' === $item->getItemType()->name) {
            $this->generateXML();
        }
    }

    public function parents()
    {
        $subjects = get_db()->getTable('ItemRelationsRelation')->findBySubjectItemId($this->_item->id);
        $results = array();
        foreach ($subjects as $subject) {
            if ($subject->getPropertyText() !== "Is Part Of") {
                continue;
            }
            if (!($superItem = get_record_by_id('item', $subject->object_item_id))) {
                continue;
            }
            $results[] = $superItem;
        }
        return $results;
    }

    public function exportable()
    {
        $exportable = false;
        switch($this->_itemType) {
        case "collections":
            $raw_suppression = metadata($this->_item, array('Item Type Metadata', 'Collection Suppressed'), array('no_filter' => true));
            $raw_suppression = str_replace('&quot;', '"', $raw_suppression);
            $suppression = json_decode($raw_suppression, true);
            $exportable = $suppression['description'] ? false : true;
            break;
        case "series":
            $raw_suppression = metadata($this->_item, array('Item Type Metadata', 'Series Suppressed'), array('no_filter' => true));
            $raw_suppression = str_replace('&quot;', '"', $raw_suppression);
            $suppression = json_decode($raw_suppression, true);
            if ($suppression['description']) {
                $exportable = false;
            }
            else {
                $exportable = true;
                foreach ($this->parents() as $parent) {
                    $parent_raw_suppression = metadata($parent, array('Item Type Metadata', 'Collection Suppressed'), array('no_filter' => true));
                    $parent_raw_suppression = str_replace('&quot;', '"', $parent_raw_suppression);
                    $parent_suppression = json_decode($parent_raw_suppression, true);
                    if ($parent_suppression['recursive']) {
                        $exportable = false;
                    }
                }
            }
            break;
        case "interviews":
            $suppression = metadata($this->_item, array('Item Type Metadata', 'Interview Suppressed'), array('no_filter' => true));
            if (strlen($suppression) > 0) {
                $exportable = false;
            }
            else {
                $exportable = true;
                foreach ($this->parents() as $parent) {
                    $parent_raw_suppression = metadata($parent, array('Item Type Metadata', 'Series Suppressed'), array('no_filter' => true));
                    $parent_raw_suppression = str_replace('&quot;', '"', $parent_raw_suppression);
                    $parent_suppression = json_decode($parent_raw_suppression, true);
                    if ($parent_suppression['recursive']) {
                        $exportable = false;
                    }
                    else {
                        $parentOutput = new Output_SpokeOhms($parent);
                        foreach ($parentOutput->parents() as $grandparent) {
                            $grandparent_raw_suppression = metadata($grandparent, array('Item Type Metadata', 'Collection Suppressed'), array('no_filter' => true));
                            $grandparent_raw_suppression = str_replace('&quot;', '"', $grandparent_raw_suppression);
                            $grandparent_suppression = json_decode($grandparent_raw_suppression, true);
                            if ($grandparent_suppression['recursive']) {
                                $exportable = false;
                            }
                        }
                    }
                }
            }
            break;
        }
        return $exportable;
    }

    public function filePathExists()
    {
        $extension = '.zip';
        if ('interviews' === $this->_item->getItemType()->name) {
            $extension = '.xml';
        }
        $filePath = dirname(dirname(dirname(__FILE__)))
                  . DIRECTORY_SEPARATOR
                  . 'exports'
                  . DIRECTORY_SEPARATOR
                  . $this->getDcField('Identifier')
                  . $extension;
        return file_exists($filePath);
    }

    public function filePath()
    {
        if (!isset($this->_filePath)) {
            $extension = '.zip';
            if ('interviews' === $this->_item->getItemType()->name) {
                $extension = '.xml';
            }
            $this->_filePath = 'exports/'
                             . $this->getDcField('Identifier')
                             . $extension;
        }
        return $this->_filePath;
    }

    public function generateXML()
    {
        $this->_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ROOT/>');
        $record = $this->_xml->addChild('record');
        $record->addAttribute('id', $this->getDcField('Identifier'));
        $record->addAttribute('dt', date('Y-m-d'));
        $record->addChild('version', '4');
        $date = $record->addChild('date', $this->getField('Interview Date'));
        $date->addAttribute('format', 'yyyy-mm-dd');
        $date_nf = $record->addChild('date_nonpreferred_format'); #, $this->getField('Interview Date'));
        $record->addChild('cms_record_id', $this->getDcField('Identifier'));
        $record->addChild('title', $this->getDcField('Title'));
        $record->addChild('accession', $this->getDcField('Identifier'));
        $record->addChild('duration');
        $csMetadata = $this->getCollectionAndSeries();
        $record->addChild('collection_id', $csMetadata['collection']['id']);
        $record->addChild('collection_name', $csMetadata['collection']['label']);
        $record->addChild('series_id', $csMetadata['series']['id']);
        $record->addChild('series_name', $csMetadata['series']['label']);
        # Hardcoded, but this is only needed for one repository
        $record->addChild('repository', 'Louie B. Nunn Center for Oral History, University of Kentucky');
        $record->addChild('repository_url');
        foreach ($this->getFields('Interview LC Subject') as $subject) {
            $record->addChild('subject', $subject);
        }
        foreach ($this->getNames($this->getFields('Interviewee Name')) as $interviewee) {
            $record->addChild('interviewee', $interviewee);
        }
        foreach ($this->getNames($this->getFields('Interviewer Name')) as $interviewer) {
            $record->addChild('interviewer', $interviewer);
        }
        $record->addChild('file_name', $this->getField('Interview Cache File'));
        $record->addChild('sync');
        $record->addChild('type', 'interview');
        $record->addChild('description', $this->getField('Interview Summary'));
        $record->addChild('rel');
        $record->addChild('transcript');
        $record->addChild('rights', $this->getField('Interview Rights'));
        $record->addChild('usage', $this->getField('Interview Usage'));
    }

    public function getDcField($field)
    {
        return metadata($this->_item, array('Dublin Core', $field), array('no_filter' => true));
    }

    public function getDcFields($field)
    {
        return metadata($this->_item, array('Dublin Core', $field), array('all' => true, 'no_filter' => true));
    }

    public function getField($field)
    {
        return metadata($this->_item, array('Item Type Metadata', $field), array('no_filter' => true));
    }

    public function getFields($field)
    {
        return metadata($this->_item, array('Item Type Metadata', $field), array('all' => true, 'no_filter' => true));
    }

    public function render()
    {
        return $this->_xml->asXML();
    }

    public function getNames($list) {
        $people = array();
        $keys = array('first', 'middle', 'last');
        foreach ($list as $text) {
            $person = json_decode(str_replace('&quot;', '"', $text), true);
            $pieces = array();
            foreach ($keys as $key) {
                if (isset($person[$key]) and strlen($person[$key]) > 0) {
                    $pieces[] = $person[$key];
                }
            }
            $people[] = implode(' ', $pieces);
        }
        return $people;
    }

    public function getCollectionAndSeries()
    {
        $metadata = array(
            'series' => array(),
            'collection' => array(),
        );
        $subjects = get_db()->getTable('ItemRelationsRelation')->findBySubjectItemId($this->_item->id);
        foreach ($subjects as $subject) {
            if ($subject->getPropertyText() !== "Is Part Of") {
                continue;
            }
            if (!($subitem = get_record_by_id('item', $subject->object_item_id))) {
                continue;
            }
            $label = metadata($subitem, array('Dublin Core', 'Title'), array('no_filter' => true));
            $metadata['series'] = array(
                'id' => metadata($subitem, array('Dublin Core', 'Identifier'), array('no_filter' => true)),
                'label' => $label,
                'item_id' => $subject->object_item_id,
            );
            break;
        }

        $subjects = get_db()->getTable('ItemRelationsRelation')->findBySubjectItemId($metadata['series']['item_id']);
        foreach ($subjects as $subject) {
            if ($subject->getPropertyText() !== "Is Part Of") {
                continue;
            }
            if (!($subitem = get_record_by_id('item', $subject->object_item_id))) {
                continue;
            }
            $label = metadata($subitem, array('Dublin Core', 'Title'), array('no_filter' => true));
            $metadata['collection'] = array(
                'id' => metadata($subitem, array('Dublin Core', 'Identifier'), array('no_filter' => true)),
                'label' => $label,
                'item_id' => $subject->object_item_id,
            );
            break;
        }

        return $metadata;
    }

    private $_item;
    private $_xml;
    private $_filePath;
}
