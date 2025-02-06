<?php
/* @var $data */
foreach ($data as $type => $dat) {

    echo '<div class="panel panel-info">';
    if (current($dat)['ownership_type_id'] != 5) {
        echo '<p class="panel-heading">' . current($dat)['type_name'] . ' (cумма % блоку має == 100)</p>';
    }

    echo '<div class="panel-body">';

    foreach ($dat as $datum) { ?>
        <div class="input-group">
            <span class="input-group-addon"><?=$datum['artist_name']?></span>
            <div class="form-group required">
                <input type="text" class="form-control" name="Percentage[<?=$datum['ownership_id']?>][<?=$datum['ownership_type_id']?>][<?=$datum['id']?>]" value="<?=$datum['percentage']?>" aria-describedby="button-addon2" aria-required="true" aria-invalid="false">
                <div class="help-block"></div>
            </div>
        </div>
    <?php
    }

    echo '</div></div>';
}



