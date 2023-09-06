<?php

/* @var $this yii\web\View */

//$this->title = 'My Yii Application';
?>
<div class="site-index">
    <?php
echo '<pre>';
print_r(Yii::$app->authManager->getRoles());
//print_r(Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId()));
   // print_r(Yii::$app->user->identity->role->name);    
//print_r(geoip_record_by_name(Yii::$app->request->userIP));
//print_r($_SESSION);
echo '</pre>';
//echo geoip_country_code_by_name(Yii::$app->request->userIP);
//echo geoip_region_by_name(Yii::$app->request->userIP);
//geoip_region_name_by_code();
//print_r(geoip_region_name_by_code(geoip_country_code_by_name(Yii::$app->request->userIP), geoip_region_by_name(Yii::$app->request->userIP)));



//echo '<br>' . geoip_country_name_by_name(Yii::$app->request->userIP);
?>
</div>
