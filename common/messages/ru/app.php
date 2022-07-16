<?php
return yii\helpers\ArrayHelper::map(
	\common\models\Message::find(['autoload' => true])
		->where('lang_id = 3')
		->all(),
	'name',
	'translate');
