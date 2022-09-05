<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Image;

class MyHelperProvider extends ServiceProvider
{
    static public function info(){
        $object = (object) [
            'company_name'=>'UptaxDB',
            'sms_api_key' => 'C20075025fd5d0c2dfd492.97321186',
            'sms_sender_id' => '8809612446206',
            'sms_credit' => 100,
            'short_url' => '',
            'main_site' => '',
        ];
        return $object;
    }
    static public function slugify($text){
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // transliterate
        //$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // trim
        $text = trim($text, '-');
        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // lowercase
        $text = strtolower($text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }
    static public function photoUpload($photoData,$folderName,$width=null,$height=null){

        $photoOrgName=self::slugify($photoData->getClientOriginalName());
        $photoType=$photoData->getClientOriginalExtension();
        //$fileType = $photoData->getClientOriginalName();
        $fileName =substr($photoOrgName,0,-4).date('d-m-YH-i-s').'.'.$photoType;
        $path2 = $folderName. date('Y/m/d');
        //return $path2;
        if (!is_dir($path2)) {
            mkdir("$path2", 0777, true);
        }
        if ($width!=null && $height!=null){ // width & height mention-------------------
            $img = \Image::make($photoData);
            $img->resize($width, $height);
            $img->save($folderName. date('Y/m/d/') . $fileName);
        }elseif ($width!=null){ // only width mention-------------------
            $img = \Image::make($photoData);
            $img->resize($width,null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($folderName. date('Y/m/d/') . $fileName);
        }else{
            $img = \Image::make($photoData);
            $img->save($folderName. date('Y/m/d/') . $fileName);
        }
        return $photoUploadedPath=$folderName . date('Y/m/d/') . $fileName;

    }

        static public function fileUpload($filedata,$folderName){

            $fileType = $filedata->getClientOriginalExtension();
            $fileName = rand(1, 1000) . date('dmyhis') . "." . $fileType;
            $path2 = $folderName. date('Y/m/d');
            if (!is_dir($path2)) {
                mkdir("$path2", 0777, true);
            }
            $filedata->move($path2 , $fileName);
            return $photoUploadedPath=$path2.'/'. $fileName;

    }

    public static function bn2en($number) {
        $bn = array("১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০");
        $en = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");
        return str_replace($bn, $en, $number);
    }

    public static function en2bn($number) {
        $bn = array("১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯", "০");
        $en = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0");
        return str_replace($en, $bn, $number);
    }
}
