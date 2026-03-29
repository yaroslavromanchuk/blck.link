<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\imagine\Image;
use Imagine\Gd;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;

class Upload
{
    /**
     * 
     * @param ActiveRecord $model - модель
     * @param int $id - ід
     * @param string $folder - папка куда хранить файл, от корня  /frontend/web/images/
     * @param array $crop - [100, 100] - размер обрезки
     * @return string
     */
    public static  function createImage(ActiveRecord $model, int $id, string $folder='', array $crop = []) {
        $dir = Yii::getAlias('@app/../frontend/web/images/').($folder?$folder.'/':'');
      //  Yii::$app->controller->createDirectory(Yii::getAlias('@app/../frontend/web/images').($folder?'/'.$folder:'')); //создаст папку если ее нет!     
                  $fileName = $id.'_'.Yii::$app->getSecurity()->generateRandomString(8) . '.' . $model->file->extension;
                  $img = $dir . $fileName;
                 //  $watermark = Yii::getAlias('@app/../frontend/web/images/watermark.png'); // 200x200
                    $model->file->saveAs($img);
                    $model->file = $fileName; // без этого ошибка
                    
                    if($crop) {
                    $size = getimagesize($img); // Определяем размер картинки
                    $imageWidth = $size[0]; // Ширина картинки
                    $imageHeight = $size[1]; // Высота картинки

                      if($imageWidth != $imageHeight || $imageWidth > $crop[0] || $imageHeight > $crop[1]) {
                            Image::getImagine()->open($img)
                                ->thumbnail(new Box($crop[0], $crop[1]))
                                ->save($img, ['quality' => 90]);
                      }
                  }
                 
        return $fileName;
    }

	/**
	 *
	 * @param ActiveRecord $model
	 * @param string $current_image
	 * @param string $folder - папка куда хранить файл, от корня  /frontend/web/images/
	 * @param array $crop - [100, 100] - размер обрезки
	 * @return string
	 * @throws \yii\base\Exception
	 */
     public static  function updateImage(ActiveRecord $model, string $current_image, string $folder='', array $crop = []): string
     {
         $baseDir = Yii::getAlias('@frontend/web/images');
         $dir = rtrim($baseDir . '/' . trim($folder, '/'), '/') . '/';
         
         
         if (!is_dir($dir)) {
             throw new \RuntimeException('Відсутній каталог:' . $dir);
         }
         
         // $dir = Yii::getAlias('@app/../frontend/web/images/').($folder?$folder.'/':'');
         
       //  $fileName = $model->id . '_' . Yii::$app->getSecurity()->generateRandomString(9) . '.' . $model->file->extension;

        // безпечна назва файлу
         $fileName = sprintf(
             '%d_%s.%s',
             (int) $model->id,
             Yii::$app->security->generateRandomString(8),
             $model->file->extension
         );
         
         $path = $dir . $fileName;
         
         if (!$model->file->saveAs($path)) {
             throw new \RuntimeException('Не вдалося зберегти файл');
         }

         // видалити старе зображення
         if ($current_image
             && $current_image != '2565_XZEVWO7R.jpg'
             && is_file($dir . $current_image)
         ) {
             @unlink($dir . $current_image);
         }
         
         $model->file = $fileName; // без этого ошибка

         //crop / resize
         if ($crop && count($crop) === 2) {
             [$targetW, $targetH] = $crop;
             $size = getimagesize($path); // Определяем размер картинки
             
             if ($size) {
                 [$width, $height] = $size;
                 
                 if ($width > $targetW || $height > $targetH) {
                     Image::thumbnail(
                         $path,
                         $targetW,
                         $targetH
                     )->save($path, ['quality' => 90]);
                 }
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