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
        if ("interviews" === $item->getItemType()->name) {
            $this->generateXML();
        }
    }

    public function generateXML()
    {
        $this->_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><ROOT/>');
        $record = $this->_xml->addChild('record');
        $record->addAttribute('id', $this->getDcField('Identifier'));
        $record->addAttribute('dt', date('Y-m-d'));
        $record->addChild('version', '4');
        $date = $record->addChild('date', $this->getField('Interview Standard Date'));
        $date->addAttribute('format', 'yyyy-mm-dd');
        $date_nf = $record->addChild('date_nonpreferred_format', $this->getField('Interview Date'));
        $record->addChild('cms_record_id', $this->getDcField('Identifier'));
        $record->addChild('title', $this->getField('Interview Title'));
        $record->addChild('accession', $this->getField('Interview Accession Number'));
        $record->addChild('duration');
        $record->addChild('collection_id', $this->getField('Interview Collection Identifier'));
        $record->addChild('collection_name', $this->getField('Interview Collection'));
        $record->addChild('series_id', $this->getField('Interview Series Identifier'));
        $record->addChild('series_name', $this->getField('Interview Series'));
        $record->addChild('repository', $this->getDcField('Publisher'));
        $record->addChild('repository_url');
        foreach ($this->getFields('Interview LC Subject') as $subject) {
            $record->addChild('subject', $subject);
        }
        foreach ($this->getFields('Interviewee') as $interviewee) {
            $record->addChild('interviewee', $interviewee);
        }
        foreach ($this->getFields('Interviewer') as $interviewer) {
            $record->addChild('interviewer', $interviewer);
        }
        $record->addChild('file_name', $this->getField('Interview Cache File'));
        $record->addChild('sync');
        $record->addChild('type', 'interview');
        $record->addChild('description', $this->getField('Interview Summary'));
        $record->addChild('rel');
        $record->addChild('transcript');
        $record->addChild('rights', $this->getField('Interview Rights Statement'));
        $record->addChild('usage', $this->getField('Interview Usage Statement'));
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

    private $_item;
    private $_xml;
}
