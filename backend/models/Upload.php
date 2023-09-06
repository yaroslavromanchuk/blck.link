<?php

namespace backend\models;

use Yii;
use yii\web\UploadedFile;
use yii\imagine\Image;
use Imagine\Gd;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;

class Upload
{
    /**
     * 
     * @param type $model - модель
     * @param type $id - ід
     * @param type $folder - папка куда хранить файл, от корня  /frontend/web/images/
     * @param array $crop - [100, 100] - размер обрезки
     * @return string
     */
    public static  function createImage($model, $id, $folder='', $crop = []) {
        $dir = Yii::getAlias('@app/../frontend/web/images/').($folder?$folder.'/':'');
      //  Yii::$app->controller->createDirectory(Yii::getAlias('@app/../frontend/web/images').($folder?'/'.$folder:'')); //создаст папку если ее нет!     
                  $fileName = $id.'_'.Yii::$app->getSecurity()->generateRandomString(8) . '.' . $model->file->extension;
                  $img = $dir . $fileName;
                 //  $watermark = Yii::getAlias('@app/../frontend/web/images/watermark.png'); // 200x200
                    $model->file->saveAs($img);
                    $model->file = $fileName; // без этого ошибка
                    
                    if($crop){
                    $size = getimagesize($img); // Определяем размер картинки
                    $imageWidth = $size[0]; // Ширина картинки
                    $imageHeight = $size[1]; // Высота картинки
                     if($imageWidth != $imageHeight or $imageWidth > $crop[0] or $imageHeight > $crop[1]){
                  Image::getImagine()->open($img)->thumbnail(new Box($crop[0], $crop[1]))->save($img, ['quality' => 90]);
                }
                    }
                 
        return $fileName;
    }

	/**
	 *
	 * @param type $model
	 * @param type $current_image
	 * @param string $folder - папка куда хранить файл, от корня  /frontend/web/images/
	 * @param array $crop - [100, 100] - размер обрезки
	 * @return string
	 * @throws \yii\base\Exception
	 */
     public static  function updateImage($model, $current_image, $folder='', $crop = []) {
                    
                        $dir = Yii::getAlias('@app/../frontend/web/images/').($folder?$folder.'/':'');
      //  Yii::$app->controller->createDirectory($dir); //создаст папку если ее нет!
                      
                     if(is_file($dir.$current_image) && file_exists($dir.$current_image))
                        {
                            //удаляем файл
                            unlink($dir.$current_image);
                           // $model->image = '';
                        }

                  $fileName = $model->id.'_'.Yii::$app->getSecurity()->generateRandomString(9) . '.' . $model->file->extension;
                  $img = $dir.$fileName;
                  

                    $model->file->saveAs($img);
                    $model->file = $fileName; // без этого ошибка
                    
                    if($crop){
                    $size = getimagesize($img); // Определяем размер картинки
                    $imageWidth = $size[0]; // Ширина картинки
                    $imageHeight = $size[1]; // Высота картинки
                     if($imageWidth != $imageHeight or $imageWidth > $crop[0] or $imageHeight > $crop[1]){
                  Image::getImagine()->open($img)->thumbnail(new Box($crop[0], $crop[1]))->save($img, ['quality' => 90]);
                }
                    }
                   
        return $fileName;
    }
     public static function createImageAll($file, $id) {
       $dir = Yii::getAlias('@app/../frontend/web/images/');
        Yii::$app->controller->createDirectory(Yii::getAlias('@app/../frontend/web/images/')); //создаст папку если ее нет!
         $fileName = $id.Yii::$app->getSecurity()->generateRandomString(8).'.'.$file->extension;
         $img = $dir . $fileName;
                 //  $watermark = Yii::getAlias('@app/../frontend/web/images/watermark.png'); // 200x200
        $file->saveAs($dir . $fileName);
      //  $size = getimagesize($img); // Определяем размер картинки
                  //  $imageWidth = $size[0]; // Ширина картинки
                 //   $imageHeight = $size[1]; // Высота картинки
                  //  $watermarkPositionLeft = $imageWidth - 250; // Новая позиция watermark по оси X (горизонтально)
                  //  $watermarkPositionTop = $imageHeight - 350;  // Новая позиция watermark по оси Y (вертикально)
                  //  $img = Image::watermark($img, $watermark, [$watermarkPositionLeft, $watermarkPositionTop])->save($img, ['quality' => 90]);
                   //$mig =  Image::getImagine()->open($dir . $fileName);
                   //$mig->save($dir . $fileName, ['quality' => 90]);
                  // Yii::$app->controller->createDirectory(Yii::getAlias('@app/../frontend/web/uploads/cars/1000')); //создаст папку если ее нет!
                  //  Image::thumbnail($img, 1000, 600)->save(Yii::getAlias('@app/../frontend/web/uploads/cars/1000/') . $fileName);
                    
                  // Yii::$app->controller->createDirectory(Yii::getAlias('@app/../frontend/web/uploads/cars/480')); //создаст папку если ее нет!
                   
                  // Image::thumbnail($img, 480, 288)->save(Yii::getAlias('@app/../frontend/web/uploads/cars/480/') . $fileName);
                   
                 //  Yii::$app->controller->createDirectory(Yii::getAlias('@app/../frontend/web/uploads/cars/180')); //создаст папку если ее нет!
                 //  Image::thumbnail($img, 180, 108)->save(Yii::getAlias('@app/../frontend/web/uploads/cars/180/') . $fileName, ['quality' => 90]);
        return $img;
    }
    
    
    public static function deleteImage($image) {
        if(file_exists(Yii::getAlias('@app/../frontend/web/images/'.$image)))
            {
                //удаляем файл
                unlink(Yii::getAlias('@app/../frontend/web/images/'.$image));
            }
        return true;
    }
    
    
}