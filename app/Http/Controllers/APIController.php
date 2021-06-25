<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;
use App\Models\UploadFile;

class APIController extends Controller
{
    public $_accessToken = '';
    public $_tokenType = '';
    public $workflows = [];
    
    public function __construct()
    {
        // $this->workflows = [
        //     'CONVERT_FILES_TO_PDF' => env('CONVERT_FILES_TO_PDF'),
        //     'CONVERT_TO_HTML' => env('CONVERT_TO_HTML')
        // ];
        // test
    }

    public function uploadFile(Request $request) {
        try {
            $file = $request->file;
            $fileType = $request->fileType;
            $fileName = $request->fileName;
            $fileSize = $request->fileSize;
            // $fromLang = $request->fromLang;
            $targetLang = $request->targetLang;
            $uploadFile = UploadFile::create([
                'document_id' => '',
                'file_type' => $fileType,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'target_lang' => $targetLang
            ]);

            Storage::disk('uploads')->put('/' . $uploadFile->id . '/original/' . $fileName, file_get_contents($file));

            return response()->json([
                'uFileId' => $uploadFile->id
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function callWastonApi($inputFileName, $targetLang = 'en') {
        try {
            $ch = curl_init(env('LANGUAGE_TRANSLATOR_URL')."/v3/documents?version=2018-05-01");
            // send a file
            curl_setopt($ch, CURLOPT_HEADER, 1);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data;'));
            curl_setopt($ch, CURLOPT_USERPWD, "apikey:" . env('IBM_CLOUD_API_KEY'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                array(
                'file' => curl_file_create($inputFileName),
                // 'file' => '@' . realpath($inputFileName),
                'target' => $targetLang
                ));

                // output the response
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_HEADER, false );

            $result = curl_exec($ch);
            $message = '';
            if(curl_errno($ch)){
                $message = 'Curl error: ' . curl_error($ch)."<br>";
            }
            curl_close($ch);
            // print_r($result);exit;
            $result = json_decode($result, true);
            // print_r($result['document_id']);
            // exit;
            if($message == '') {
                return ['success'=>true, 'document_id'=>$result['document_id']];
            }else{
                return ['success'=>false, 'message'=>$message];
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    public function callWastonApiGetDocument($document_id) {
        try {
            $ch = curl_init(env('LANGUAGE_TRANSLATOR_URL')."/v3/documents/".$document_id."?version=2018-05-01");
            // send a file
            // curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_USERPWD, "apikey:" . env('IBM_CLOUD_API_KEY'));

                // output the response
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_HEADER, false );

            $result = curl_exec($ch);
            // print_r($result);exit;
            $message = '';
            if(curl_errno($ch)){
                $message = 'Curl error: ' . curl_error($ch)."<br>";
            }
            curl_close($ch);
            if($message == '') {
                $result = json_decode($result, true);
                return ['success'=>true, 'status'=>$result['status'], 'filename'=>$result['filename']];
            }else{
                return ['success'=>false, 'message'=>$message];
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    public function callWastonApiDownloadDocument($document_id, $outputFileName, $acceptString) {
        //Open file handler.
        $fp = fopen($outputFileName, 'w+');
        try {
            $ch = curl_init(env('LANGUAGE_TRANSLATOR_URL')."/v3/documents/".$document_id."/translated_document?version=2018-05-01");
            // send a file
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:'.$acceptString));
            // curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_USERPWD, "apikey:" . env('IBM_CLOUD_API_KEY'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $ch, CURLOPT_HEADER, false );
            
                // output the response
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_exec($ch);
            // print_r($result);exit;
            $message = '';
            if(curl_errno($ch)){
                $message = 'Curl error: ' . curl_error($ch)."<br>";
            }
            curl_close($ch);
            if (is_resource($fp)) {
                fclose($fp);
            }else{
                $message = 'Curl error: resource file 0kb';
            }
            if($message == '') {
                return ['success'=>true];
            }else{
                return ['success'=>false, 'message'=>$message];
            }
        } catch (\Throwable $th) {
            fclose($fp);
            return $th->getMessage();
        }
    }

    public function convertFile(Request $request) {
        try {
            $uFileId = $request->uFileId;
            $uploadFile = UploadFile::find($uFileId);

            if (!$uploadFile) {
                return response()->json([
                    'message' => 'File not exist on server'
                ], 500);
            }

            $fileName = $uploadFile->file_name;
            $arr = explode('.', $fileName);
            // $fName = $arr[0];
            $inputFileName = public_path() . '/uploads/' . $uploadFile->id . '/original/' . $uploadFile->file_name;
            // $outputFileName = public_path() . '/uploads/' . $uploadFile->id . '/html/' . $fName . '.html';

            $b = $this->callWastonApi($inputFileName, $uploadFile->target_lang);
            // $b = $this->callWastonApi($workflowId, $inputFileName, $outputFileName);
            // print_r($b);exit;
            if ($b['success'] == true) {
                // save to database
                $uploadFile->document_id = $b['document_id'];
                $uploadFile->save();
                return response()->json([
                    'message' => 'File uploading succeeded.'.$b['document_id']
                ], 200);
            } else {
                return response()->json([
                    'message' => 'File uploading failed.'. $b['message']
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        } 
    }

    public function translateHTML(Request $request) {
        try {
            $uFileId = $request->uFileId;
            $translatedEntityCnt = (int)$request->translatedEntityCnt;

            $uploadFile = UploadFile::find($uFileId);
            $res = $this->callWastonApiGetDocument($uploadFile->document_id);
            if ($res['success'] == true) {
                if($res['status'] == 'available'){
                    $outputFileName = public_path() . '/uploads/' . $uploadFile->id . '/output/' . $uploadFile->file_name;
                    $downloadFileName = 'uploads/' . $uploadFile->id . '/output/' . $uploadFile->file_name;
                    $outputDirname = dirname($outputFileName);
                    if (!is_dir($outputDirname)) {
                        mkdir($outputDirname, 0755, true);
                    }
                    
                    $res_download = $this->callWastonApiDownloadDocument($uploadFile->document_id, $outputFileName, $uploadFile->file_type);
                    if($res_download['success']){
                        return response()->json([
                            'isTranslationFinished' => true,
                            'translatedEntityCnt' => $translatedEntityCnt,
                            'fileName' => $res['filename'],
                            'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $downloadFileName
                            // 'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . 'uploads/' . $uploadFile->id . '/html/' . $uploadFile->file_name
                        ], 200);
                    }else{
                        return response()->json([
                            'isTranslationFinished' => false,
                            'translatedEntityCnt' => $translatedEntityCnt
                        ], 200);    
                    }
                }else if($res['status'] == 'processing'){
                    return response()->json([
                        'isTranslationFinished' => false,
                        'translatedEntityCnt' => $translatedEntityCnt
                    ], 200);
                }else if($res['status'] == 'failed'){
                    // return response()->json([
                    //     'isTranslationFinished' => false,
                    //     'translatedEntityCnt' => $translatedEntityCnt
                    // ], 200);
                    return response()->json([
                        'message' => 'API server File converting failed1.'
                    ], 500);
                }
                
            } else {
                return response()->json([
                    'message' => 'API server File converting failed2.'
                ], 500);
            }
            /*
            $fileName = $uploadFile->file_name;
            $arr = explode('.', $fileName);
            $fName = $arr[0];
            $outputHtmlFileName = public_path() . '/uploads/' . $uploadFile->id . '/html/' . $fName . '.html';

            $content = file_get_contents($outputHtmlFileName);
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            
            $cnt = 0;

            $entities = $xpath->query('//text()');
            $array = array();
            foreach($entities as $entity){
                $array[] = $entity;
            }
            $slicedArray = array_slice($array, $translatedEntityCnt);

            $tr = new GoogleTranslateClient([
                'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
                'default_target_translation' => 'en'
            ]);

            foreach ($slicedArray as $text) {
                if ($cnt == 200) break;

                $cnt ++;
                $translatedEntityCnt ++;
                
                if ($text->parentNode->tagName === 'script' || $text->parentNode->tagName === 'noscript' || $text->parentNode->tagName === 'style') {
                    continue;
                }
                if (trim($text->nodeValue)) {
                    try {
                        $outputs = $tr->translate($text->nodeValue, $uploadFile->target_lang);
                        $text->nodeValue = $outputs['text'];
                    } catch (\Throwable $th) {
                        $th = $th;
                    }
                }
            }

            $html = $dom->saveHTML();
            file_put_contents($outputHtmlFileName, $html);

            if ($translatedEntityCnt == count($entities)) {
                $outputPdfFileName = public_path() . '/uploads/' . $uploadFile->id . '/pdf/' . $fName . '.pdf';
                $pdfFileName = 'uploads/' . $uploadFile->id . '/pdf/' . $fName . '.pdf';
                $outputDirname = dirname($outputPdfFileName);
                if (!is_dir($outputDirname)) {
                    mkdir($outputDirname, 0755, true);
                }

                // $new_elm = $dom->createElement('style', 'body {font-family: DejaVu Sans;}');
                // $new_elm->setAttribute('type', 'text/css');

                // // Inject the new <style> Tag in the document head
                // $head = $dom->getElementsByTagName('head')->item(0);
                // $head->appendChild($new_elm);

                // $html = $dom->saveHTML();
                // file_put_contents($outputHtmlFileName, $html);

                $config = [
                    'mode' => '+aCJK', 
                    // "allowCJKoverflow" => true, 
                    "autoScriptToLang" => true,
                    // "allow_charset_conversion" => false,
                    "autoLangToFont" => true,
                ];

                // $mpdf = new PDF($config);
                // $mpdf->loadHTML($html);

                // $mpdf->SetAutoFont();
                // $mpdf->autoScriptToLang = true;
                // $mpdf->autoLangToFont   = true;

                PDF::loadHTML($html, $config)->save($outputPdfFileName);
                return response()->json([
                    'isTranslationFinished' => true,
                    'translatedEntityCnt' => $translatedEntityCnt,
                    'fileName' => $fName . '.pdf',
                    'url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/" . $pdfFileName
                ], 200);
            } else {
                return response()->json([
                    'isTranslationFinished' => false,
                    'translatedEntityCnt' => $translatedEntityCnt
                ], 200);
            }
            */
        } catch(\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }
}