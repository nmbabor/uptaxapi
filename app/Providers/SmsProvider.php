<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MyHelper;
use GuzzleHttp\Client;

class SmsProvider extends ServiceProvider
{
    static public $errorCode = [1002,1003,1004,1005,1006,1007,1008,1009,1010,1011];
    static public $error = [
        1002 => 'Sender Id/Masking Not Found',
        1003 => 'API Not Found',
        1004 => 'SPAM Detected',
        1005 => 'Internal Error',
        1006 => 'Internal Error',
        1007 => 'Balance Insufficient',
        1008 => 'Message is empty',
        1009 => 'Message Type Not Set (text/unicode)',
        1010 => 'Invalid User & Password',
        1011 => 'Invalid User Id'
    ];


    static public function single($number, $text){
        $number = substr($number, -10);
        $number = '880'.$number;
        $txt = str_replace("[",'(',$text);
        $txt = str_replace("{",'(',$txt);
        $txt = str_replace("]",')',$txt);
        $txt = str_replace("}",')',$txt);
        $txt = str_replace("~",'-',$txt);
        $txt = str_replace("^",'-',$txt);
        $txt = str_replace("\\",'/',$txt);
        $txt = str_replace("|",'/',$txt);
        $msg = rawurlencode($txt);
        $smsCount = self::creditCost($txt);
        if(\MyHelper::info()->sms_credit<$smsCount){
            return 'Insufficient sms credit.';
        }
        $client = new Client();
        $url = "http://esms.mimsms.com/smsapi?api_key=".MyHelper::info()->sms_api_key.'&type=text&contacts='.$number.'&senderid='.MyHelper::info()->sms_sender_id.'&msg='.$msg;
        $request = $client->get($url);
        $response = $request->getBody()->getContents();
        if(is_numeric($response) && in_array($response,self::$errorCode)){
            return self::$error[$response];
        }

        $status = substr($response,0,13);
        $input=[];
        if(\Auth::check()){
            $input['created_by']=\Auth::user()->id;
        }
        if($status=='SMS SUBMITTED') {
            $ext = substr($response, 0, 20);
            $submitId = str_replace($ext,'',$response);
            $input['submit_id']=$submitId;
            //SmsSubmitId::create($input);
            self::smsCreditUpdate($smsCount);

            return "True";
        }else{
            return "false";
        }
    }
    static public function oneToMany($numbers,$message)
    {

        $txt = str_replace("[",'(',$message);
        $txt = str_replace("{",'(',$txt);
        $txt = str_replace("]",')',$txt);
        $txt = str_replace("}",')',$txt);
        $txt = str_replace("~",'-',$txt);
        $txt = str_replace("^",'-',$txt);
        $txt = str_replace("\\",'/',$txt);
        $txt = str_replace("|",'/',$txt);
        $contacts = '';
        $totalNum = 0;
        foreach($numbers as $key => $number){
            $number = substr($number, -10);
            $number = '880'.$number;
            if($key>0){
                $contacts.='+'.$number;
            }else{
                $contacts = $number;
            }
            $totalNum++;
        }
        $smsCount = self::creditCost($txt,$totalNum);
        if(\MyHelper::info()->sms_credit<$smsCount){
            return 'Insufficient sms credit.';
        }

        $client = new Client();
        $url = 'http://esms.mimsms.com/smsapi';
        $body = [
            "api_key" => MyHelper::info()->sms_api_key,
            "senderid" => MyHelper::info()->sms_sender_id,
            "type"=>'text',
            "msg" => $txt,
            "contacts"=>$contacts
        ];
        $request = $client->request('POST', $url, [
            'form_params' => $body
        ]);
        $response = $request->getBody()->getContents();
        if(is_numeric($response) && in_array($response,self::$errorCode)){
            return self::$error[$response];
        }

        $status = substr($response,0,13);
        if($status=='SMS SUBMITTED') {
            $ext = substr($response, 0, 20);
            $submitId = str_replace($ext,'',$response);
           // SmsSubmitId::create(['submit_id'=>$submitId,'created_by'=>\Auth::user()->id]);
            self::smsCreditUpdate($smsCount);
            return "True";
        }else{
            return "false";
        }
    }
    static public function manyToMany($numbers,$messages)
    {

        $smsBody = [];
        foreach($numbers as $key => $number){
            $number = substr($number, -10);
            $number = '880'.$number;
            $smsBody[]=[
                'to'=>$number,
                'message'=>$messages[$key],
            ];
        }
        $smsCount = self::creditCost($messages,count($numbers));
        if(\MyHelper::info()->sms_credit<$smsCount){
            return 'Insufficient sms credit.';
        }
        $client = new Client();
        $url = 'http://esms.mimsms.com/smsapimany';
        $body = [
            "api_key" => MyHelper::info()->sms_api_key,
            "senderid" => MyHelper::info()->sms_sender_id,
            "messages" => json_encode($smsBody)
        ];
        $request = $client->request('POST', $url, [
            'form_params' => $body
        ]);
        $response = $request->getBody()->getContents();
        if(is_numeric($response) && in_array($response,self::$errorCode)){
            return self::$error[$response];
        }

        $status = substr($response,0,13);
        if($status=='SMS SUBMITTED') {
            $ext = substr($response, 0, 20);
            $submitId = str_replace($ext,'',$response);


            $actionInfo=[
                'submit_id'=>$submitId,
            ];
            if(\Auth::check()){
                $actionInfo+=['created_by'=>\Auth::user()->id];
            }

           // SmsSubmitId::create($actionInfo);
            self::smsCreditUpdate($smsCount);

            return "True";
        }else{
            return "false";
        }



    }

    static public function deliveryReport()
    {
        $client = new Client();
        $deliveryUrl = "http://esms.mimsms.com/miscapi/".MyHelper::info()->sms_api_key."/"."getDLR/";
        try{
            $submitData = SmsSubmitId::get();
            foreach($submitData as $sdata) {
                $submitId = $sdata->submit_id;
                $userId = $sdata->created_by;
                $request = $client->get($deliveryUrl . $submitId);

                $response = $request->getBody()->getContents();
                if(is_numeric($response) && in_array($response,self::$errorCode)){
                    return self::$error[$response];
                }

                if(strpos($response, 'message') !== false){
                    $response=preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $response);
                }

                $xmls = simplexml_load_string($response);
                $json = json_encode($xmls);
                $array = json_decode($json, TRUE);
                if (isset($array['message'])) {

                    $messageArray = $array['message'];

                    if (isset($messageArray['ID'])) {
                        $smsText = rtrim($messageArray['SMS'],'"');
                        $smsText = ltrim($smsText,'"');
                        $smsText = ltrim($smsText,' ');
                        if(mb_strlen($smsText)==strlen($smsText)){
                            if(mb_strlen($smsText)>160){
                                $total_sms  = ceil(mb_strlen($smsText)/153);
                            }else{
                                $total_sms  = ceil(mb_strlen($smsText)/160);
                            }

                        }else{
                            if(mb_strlen($smsText)>160){
                                $total_sms  = ceil(mb_strlen($smsText)/67);
                            }else{
                                $total_sms  = ceil(mb_strlen($smsText)/70);
                            }
                        }
                        $input = [
                            'delivery_time' => date('Y-m-d H:i:s', strtotime($messageArray['DLRReceived'])),
                            'status' => $messageArray['DLRStatus'],
                            'sms_id' => $messageArray['ID'],
                            'sms' => $smsText,
                            'total_sms' => $total_sms,
                            'mobile_number' => $messageArray['MSISDN'],
                        ];
                        if($userId!=''){
                            $input['created_by']=$userId;
                        }
                        SmsLog::create($input);
                    } else {
                        foreach ($messageArray as $data) {
                            $smsText = rtrim($data['SMS'],'"');
                            $smsText = ltrim($smsText,'"');
                            $smsText = ltrim($smsText,' ');
                            if(mb_strlen($smsText)==strlen($smsText)){
                                if(mb_strlen($smsText)>160){
                                    $total_sms  = ceil(mb_strlen($smsText)/153);
                                }else{
                                    $total_sms  = ceil(mb_strlen($smsText)/160);
                                }

                            }else{
                                if(mb_strlen($smsText)>160){
                                    $total_sms  = ceil(mb_strlen($smsText)/67);
                                }else{
                                    $total_sms  = ceil(mb_strlen($smsText)/70);
                                }
                            }
                            $input = [
                                'delivery_time' => date('Y-m-d H:i:s', strtotime($data['DLRReceived'])),
                                'status' => $data['DLRStatus'],
                                'sms_id' => $smsText,
                                'sms' => $data['SMS'],
                                'total_sms' => $total_sms,
                                'mobile_number' => $data['MSISDN'],
                            ];
                            if($userId!=''){
                                $input['created_by']=$userId;
                            }
                            SmsLog::create($input);
                        }
                    }

                    if(count($messageArray)>99){
                        SmsSubmitId::where('submit_id',$submitId)->delete();
                        static::deliveryReport();
                    }else{
                        SmsSubmitId::where('submit_id',$submitId)->delete();
                    }

                }
            }
            return "true";
        }catch(Exception $e){
            return $e->errorInfo[2];
        }


    }

    static public function quickDeliveryReport($submitId)
    {

        $client = new Client();
        $deliveryUrl = "http://esms.mimsms.com/miscapi/".MyHelper::info()->sms_api_key."/getDLR/";
        try{

            $request = $client->get($deliveryUrl . $submitId);
            $response = $request->getBody()->getContents();
            $xmls = simplexml_load_string($response);
            $json = json_encode($xmls);
            $array = json_decode($json, TRUE);
            if (isset($array['message'])) {
                $messageArray = $array['message'];

                if (isset($messageArray['ID'])) {
                    $smsText = rtrim($messageArray['SMS'],'"');
                    $smsText = ltrim($smsText,'"');
                    $smsText = ltrim($smsText,' ');
                    if(mb_strlen($smsText)==strlen($smsText)){
                        if(mb_strlen($smsText)>160){
                            $total_sms  = ceil(mb_strlen($smsText)/153);
                        }else{
                            $total_sms  = ceil(mb_strlen($smsText)/160);
                        }

                    }else{
                        if(mb_strlen($smsText)>160){
                            $total_sms  = ceil(mb_strlen($smsText)/67);
                        }else{
                            $total_sms  = ceil(mb_strlen($smsText)/70);
                        }
                    }
                    $input = [
                        'delivery_time' => date('Y-m-d H:i:s', strtotime($messageArray['DLRReceived'])),
                        'status' => $messageArray['DLRStatus'],
                        'sms_id' => $messageArray['ID'],
                        'sms' => $smsText,
                        'total_sms' => $total_sms,
                        'mobile_number' => $messageArray['MSISDN'],
                    ];
                    if(\Auth::check()){
                        $input['created_by']=\Auth::user()->id;
                    }
                    SmsLog::create($input);
                } else {
                    foreach ($messageArray as $data) {
                        $smsText = rtrim($data['SMS'],'"');
                        $smsText = ltrim($smsText,'"');
                        $smsText = ltrim($smsText,' ');
                        if(mb_strlen($smsText)==strlen($smsText)){
                            if(mb_strlen($smsText)>160){
                                $total_sms  = ceil(mb_strlen($smsText)/153);
                            }else{
                                $total_sms  = ceil(mb_strlen($smsText)/160);
                            }

                        }else{
                            if(mb_strlen($smsText)>160){
                                $total_sms  = ceil(mb_strlen($smsText)/67);
                            }else{
                                $total_sms  = ceil(mb_strlen($smsText)/70);
                            }

                        }
                        $input = [
                            'delivery_time' => date('Y-m-d H:i:s', strtotime($data['DLRReceived'])),
                            'status' => $data['DLRStatus'],
                            'sms_id' => $data['ID'],
                            'sms' => $smsText,
                            'total_sms' => $total_sms,
                            'mobile_number' => $data['MSISDN'],
                        ];
                        if(\Auth::check()){
                            $input['created_by']=\Auth::user()->id;
                        }
                        SmsLog::create($input);
                    }
                }

            }

            return "true";
        }catch(\Exception $e){
            return $e->errorInfo[2];
        }


    }
    static public function creditCost($msg,$num=1){
        $smsCount=0;
        if(is_array($msg)){
            foreach($msg as $mesg){
                $mesg = str_replace(array("\t","\r","\0","\v"),'', $mesg);
                $strLength = mb_strlen($mesg, "UTF-8");
                $devider = 160;
                if($strLength>160){
                    $devider = 153;
                }
                if (strlen($mesg) != strlen(utf8_decode($mesg)))
                {
                    $devider = 70;
                    if($strLength>70){
                        $devider = 67;
                    }
                }
                $smsCount += ceil($strLength/$devider);
            }
        }else{
            $msg = str_replace(array("\t","\r","\0","\v"),'', $msg);
            $strLength = mb_strlen($msg, "UTF-8");
            $devider = 160;
            if($strLength>160){
                $devider = 153;
            }
            if (strlen($msg) != strlen(utf8_decode($msg)))
            {
                $devider = 70;
                if($strLength>70){
                    $devider = 67;
                }
            }
            $smsCount = ceil($strLength/$devider);
            $smsCount = $smsCount*$num;
        }
        return $smsCount;

    }
    static public function smsCreditUpdate($smsCount){
        return '';
        $info = PrimaryInfo::first();
        $info->update([
            'sms_credit'=>$info->sms_credit-$smsCount
        ]);
        return $info->sms_credit;
    }
    static public function smsBody($id){
        $textBody = SmsTemplate::where('sms_section_id',$id)->first();
        if($textBody==''){
            return '';
        }
        $placeholder = [ '{company_name}','{short_url}','{ims_url}'];
        $placeholderValue = [\MyHelper::info()->company_name, \MyHelper::info()->short_url, \MyHelper::info()->main_site];

        $body =  str_replace($placeholder,$placeholderValue,$textBody->body);
        return $body;
    }
}
