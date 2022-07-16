<?php
Yii::$app->response->format = \yii\web\Response::FORMAT_XML;  
        //повторно т.к. может не сработать
        header("Content-type: text/xml");
        header("Charset: UTF-8");
echo $model;
Yii::$app->end(); 
