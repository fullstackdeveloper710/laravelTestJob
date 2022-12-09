<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use App\Models\User;
use DB;
use Config;
use Illuminate\Support\Str;
use File;
use Illuminate\Support\Facades\Validator;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Token;
use Illuminate\Support\Carbon;
class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
       
    }



    public function index(){
        $data =User::select('name','description','type')->orderBy('id', 'DESC')->paginate(10);
        return response()->json(['status' => true, 'message' => 'success','data'=>$data]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try
        {
                $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:1|max:50',
                'description' =>  'required|string|min:1|max:250',
                'file' => 'required|file|max:5000',
                'type'  => 'required|numeric|min:1|max:3',
                ]);

                if ($validator->fails()) {    
                return response()->json($validator->messages(), 400);
                }
                if($request->hasFile('file')){
                    $file = $request->file('file');
                    $file = \File::get($file);
                }else{
                    $file = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$request->input('file'))); 
                    
                }

                if ($file){ 

                    $filename = Str::random(10).'.png'; 
                    $post = new User();
                    $post->name = $request['name'];
                    $post->description = $request['description'];
                    $post->file = $filename;
                    $post->type = $request['type'];
                    $saved=$post->save();
                    if($saved){
                        $response =  Storage::put('files/'.$filename, $file);
                        $lastRecord=array('name'=> $post->name,'description'=>$post->description,'type'=>$post->type);
                        return response()->json(['status' => true, 'message' => 'success','data'=> $lastRecord ]);
                    }else{
                        return response()->json(['status' => false, 'error' => 'Error in saving record']);
                    }
                }else { 
                    return response()->json(['status' => false,'error'=>'File not found']);
                }

        }catch(ModelNotFoundException $e){

            return response()->json(['status' => false,'error'=>$e]);
        }

    
    }


    /***
     * 
     * SHow a single record detail
     ****/
    public function show($id){

            try
            {
            $data = User::findOrFail($id);
            if(isset($data) && !empty($data)){
                $rendToken = Str::random(20); 
                $p=url('api/file');
                $file = Crypt::encryptString($data->file);
                $file=$p.'/'.$file.'/'.$rendToken;
                $lastRecord=array('name'=> $data->name,'description'=>$data->description,'type'=>$data->type,'file'=>$file);
                
               
                /****
                 * 
                 * Create dand save token in DB
                 * ***/
                $token = new Token();
                $token->token = $rendToken;
                $token->file=$data->file;
                $token->file_id=$data->id;
                $token->save();


                echo '<pre>';
                print_r($lastRecord);
                die();
               
                return response()->json(['status' => true, 'message' => 'success','data'=>$lastRecord]);

            }else{
                return response()->json(['status' => false, 'message' => 'No recored found']);

            }

            }catch(ModelNotFoundException $e)
            {
                die('ddd');
            return response()->json(['status' => false, 'message' => 'error','data'=>$e]);

            }

        }


    /*****
     * 
     * Delete last 30 days above  records
     * ***/
    public function destroy(){

        try
        {
        $files = User::select('id','file')->whereDate('created_at', '<=', now()->subDays(30))->get()->toArray();
        $totFile=0;
        if(isset($files) && !empty($files))  {
            $totFile=count($files);
            foreach($files as $file){
                $delete=User::where('id',$file['id'])->delete();
                if($delete){
                    Storage::delete('files/'.$file['file']);
                }
            }

          }
          return response()->json(['status' => true, 'message' => 'success','deleted_records'=>$totFile]);
        }catch(ModelNotFoundException $e)
        {
        return response()->json(['status' => false, 'message' => 'error','data'=>$e]);

        }

    }


    /****
     * 
     * perview images
     * 
     * ***/
    public function preview($file,$token){
        try
        {
            
        $file = Crypt::decryptString($file);
        $tokenExist=Token::where('token',$token)->first();  
        if(isset($tokenExist) && !empty($tokenExist)){
        $date= $tokenExist->created_at;
        $curentDate  = Carbon::now();

        $from_time = strtotime($date); 
        $to_time = strtotime($curentDate); 
        $diff_minutes = round(abs($from_time - $to_time) / 60,2);

        echo $diff_minutes;

        if($diff_minutes<10){
           
            $path = storage_path('app/files/'.$file);
            if (file_exists($path)) {
            $file = File::get($path);
            $type = File::mimeType($path);
            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);
            return $response;
            }else{
            return response()->json(['status' => false, 'message' => 'File not found']);
            }
        }else{


            $tt= Token::where('token',$token)->delete();
            return response()->json(['status' => false, 'message' =>'Token Expired']);
        }

    }else{

        return response()->json(['status' => false, 'message' =>'Token Expired']);
    }
        }catch(ModelNotFoundException $e)
        {
            return response()->json(['status' => false, 'message' =>$e]);

        }
    
        
    }


}
