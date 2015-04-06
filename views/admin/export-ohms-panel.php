<div class="panel">
  <h4>Export OHMS</h4>
  <div>
    <a href="<?php echo $this->url('items/export-ohms/' . metadata('item', 'id')); ?>" class="big button" name="export">Export OHMS</a>
<?php
$item = get_record_by_id('Item', metadata('item', 'id'));
$output = new Output_SpokeOhms($item);
if ($output->filePathExists()):
?>
<ul>
  <li><a href="<?php echo $this->baseUrl() . '/' . $output->filePath(); ?>"><?php echo $output->filePath(); ?></a></li>
</ul>
<?php
endif;
?>
  </div>
</div>
