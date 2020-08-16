<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <div class="pull-right">
                <button type="submit" form="form-backup" data-toggle="tooltip" title="<?php echo $button_backup; ?>" class="btn btn-default"><i class="fa fa-download"></i></button>
                <button type="submit" form="form-restore" data-toggle="tooltip" title="<?php echo $button_restore; ?>" class="btn btn-default"><i class="fa fa-upload"></i></button>
            </div>
            <h1><?php echo $heading_title; ?></h1>
            <ul class="breadcrumb">
                <?php foreach ($breadcrumbs as $breadcrumb) { ?>
                <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
    <div class="container-fluid">
            <div id="foks_ie"></div>
    </div>

</div>
<?php echo $footer; ?>