<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;

class PostController extends Controller
{
    public function index()
    {
        $data['title'] = "Artikel";
        return view('post.index', $data);
    }

    public function data(Request $request)
	{
        $keyword = $request->search['value'];
        $start = $request->post('start');
        $length = $request->post('length');

        $columns = array(
            2 => 'posts.title',
            3 => 'posts.tags',
            4 => 'posts.created_date'
        );

        $order = $columns[$request->order[0]['column']];
        $dir = $request->order[0]['dir'];

		$list = Post::selectRaw('
            posts.id,
            posts.title,
            posts.tags,
            posts.created_date
        ');
        if(!empty($keyword)){
            $keyword = '%'.$keyword .'%';
            $query = $list->where(function($q) use($keyword) {
                $q->where('posts.title', 'LIKE', $keyword)
                ->orWhere('posts.tags', 'LIKE', $keyword)
                ->orWhere('posts.created_date', 'LIKE', $keyword)
                ;
            });
        }

        $count = count($list->get());

        if (isset($start) AND $start != '') {
            $list = $list->offset($start)->limit($length);
        }

        $list = $list->orderBy($order, $dir);
        $list = $list->get();

        $data = array();
        $no = $request->post('start') + 1;

        if(!empty($list)){
            foreach ($list as $key => $value) {
                $row = array();
                $row[] = $no;
                 //add html for action
                 $row[] = '
                 <a class="btn btn-sm btn-info" href="javascript:void(0)" title="Detail" onclick="detail(\''.$value->id.'\')"><i class="fas fa-search"></i></a>
                 <a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit('."'".$value->id."'".')"><i class="fas fa-edit"></i></a>
                 <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Delete" onclick="deletes('."'".$value->id."','".$value->title."'".')"><i class="fas fa-trash-alt"></i></a>';
                $row[] = $value->title;
                $row[] = $value->tags;
                $row[] = !empty($value->created_date) ? date("d-m-Y H:i:s", strtotime($value->created_date)) : "";
                $data[] = $row;
                $no++;
            }
        }

        $response = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $count,
            "recordsFiltered" => $count,
            "data" => $data,
        );

        return response()->json($response, 200);
    }

    public function getBase64ImageSize($base64Image){ //return memory size in B, KB, MB
        try{
            $size_in_bytes = (int) (strlen(rtrim($base64Image, '=')) * 3 / 4);
            $size_in_kb    = $size_in_bytes / 1024;
            // $size_in_mb    = $size_in_kb / 1024;

            return $size_in_kb;
        }
        catch(Exception $e){
            return $e;
        }
    }

    public function uploadBase64($image, $path, $max_size, $type_allow){

        $file_size = $this->getBase64ImageSize($image);

        if($file_size <= $max_size){

            $image_name = md5(uniqid(rand(), true).date('YmdHis'));
            $ext = explode(';', $image);
            $ext = explode('/', $ext[0]);
            $ext = end($ext);
            $filename = $image_name.'.'.$ext;
            $image = explode(',', $image);
            $file = $path.$filename;

            if(in_array($ext, explode('|', $type_allow))){
                $status = file_put_contents($file, base64_decode($image[1]));
                if($status !== false){
                    chmod($file,0777);
                    $result['status'] = true;
                    $result['file'] = $filename;
                    $result['message'] = "Sukses Upload";
                }else{
                    $result['status'] = false;
                    $result['file'] = '';
                    $result['message'] = "Gagal Upload";
                }
            }else{
                $result['status'] = false;
                $result['file'] = '';
                $result['message'] = 'Format file yang diperbolehkan adalah ('.(str_replace('|', ', ', $type_allow)).')';
            }

        }else{
            $result['status'] = false;
            $result['file'] = '';
            $result['message'] = 'Maksimal besar file adalah '.$max_size.'KB';
        }

        return $result;

    }

    public function addView(Request $request){

        $html = '<div class="form-group">';
        $html.=     '<label for="title">Judul *</label>';
        $html.=     '<input type="text" id="title" name="title" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="tags">Tag</label>';
        $html.=     '<input type="text" id="tags" name="tags" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="content">Konten *</label>';
        $html.=     '<textarea id="content" name="content" class="form-control textarea"></textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">
                    <label for="file_image">Banner</label>
                    <br>
                    <div class="row">
                        <div class="col" style="text-align: center;">
                            <label id="label_images" for="images" style="cursor: pointer;">
                                <img style="width:360px; height:100px; border:1px dashed #C3C3C3;" src="'.asset('/assets/img/upload-images.png"').' />
                            </label>

                            <input type="file" name="images" id="images" style="display:none;" onchange="readURL(this)" accept="image/*"/>

                            <img style="width:360px; height:100px; border:1px dashed #C3C3C3; margin-bottom: 5px; display:none;" id="show_images" />
                            <br>
                            <div style="height: 40px;">
                                <span id="remove" class="btn btn-warning" onclick="removeImage()" style="cursor: pointer; margin-bottom: 5px; display:none;">
                                    Hapus
                                </span>
                                <span class="msg_images" id="msg_images" style="color: red;"></span>
                            </div>

                            <input type="hidden" id="file_image_value" name="file_image_value"/>
                        </div>
                    </div>';
        $html.= '</div>';


        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function add(Request $request){

        $validation = true;
        $validation_text = '';

        if(empty($request->title)){
            $validation = $validation && false;
            $validation_text.= '<li>Judul dibutuhkan</li>';
        }else{
            $check_data = Post::select('id')->where('title', $request->title)->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Judul <b>'.$request->name.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->content)){
            $validation = $validation && false;
            $validation_text.= '<li>Konten dibutuhkan</li>';
        }

        if($validation){
            $results = true;
            $upload['status'] = true;
            $upload['file'] = "";

            if(!empty($request->file_image_value)){
                $path = env("WEB_UPLOAD")."post/";
                $max_size = '1024'; // in KB
                $type_allow = 'jpg|JPG|png|PNG|jpeg|JPEG|gif|GIF';
                $upload = $this->uploadBase64($request->file_image_value, $path, $max_size, $type_allow);
            }

            if($upload['status']){
                $date = date('Y-m-d H:i:s');
                $user_id = auth()->user()->id;

                $post = new Post;

                $post->title = $request->title;
                $post->content = $request->content;
                $post->slug_url = preg_replace("/[^A-Za-z0-9 -]/", '', str_replace(" ", "-",strtolower($request->title)));
                $post->tags = $request->tags;
                $post->category = "PAGE";
                $post->banner_image = !empty($upload['file']) ? $upload['file'] : NULL;
                $post->created_by = $user_id;
                $post->created_date = $date;
                $response = $post->save();

                if ($response) {
                    $result["status"] = TRUE;
                    $result["message"] = 'Sukses menambahkan data';
                } else {
                    $result["status"] = FALSE;
                    $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                    $result["message"].= '<li>Gagal menambahkan data</li>';
                    $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
                    $result["message"].= '</div>';
                }
            }else{
                $result["status"] = FALSE;
                $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                $result["message"].= '<li>'.$upload['message'].'</li>';
                $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
                $result["message"].= '</div>';
                @unlink(env("WEB_UPLOAD")."post/".$upload['file']);
            }

        }else{
            $result["status"] = FALSE;
            $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            $result["message"].= $validation_text;
            $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
            $result["message"].= '</div>';
        }

        return response()->json($result, 200);
    }

    public function editView(Request $request, $id){

        $detail = Post::where('id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="title">Nama *</label>';
        $html.=     '<input type="text" id="title" name="title" class="form-control" value="'.$detail->title.'">';
        $html.=     '<input type="hidden" id="id" name="id" class="form-control" value="'.$id.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="tags">Tag</label>';
        $html.=     '<input type="text" id="tags" name="tags" class="form-control" value="'.$detail->tags.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="content">Konten *</label>';
        $html.=     '<textarea id="content" name="content" class="form-control textarea">'.$detail->content.'</textarea>';
        $html.= '</div>';
        $path = env("APP_WEB_URL")."upload/post/";
        if(!empty($detail->banner_image)){
            $type = pathinfo($path.$detail->banner_image, PATHINFO_EXTENSION);
            $base_64_images = base64_encode(file_get_contents($path.$detail->banner_image));
            $base_64_images = 'data:image/' . $type . ';base64,' .$base_64_images;
        }else{
            $base_64_images = '';
        }
        $html.= '<div class="form-group">
                    <label for="file_image">Banner</label>
                    <br>
                    <div class="row">
                        <div class="col" style="text-align: center; height: 245px;">
                            <label id="label_images" for="images" style="cursor: pointer;'.(!empty($detail->banner_image) ? 'display:none;' : '').'">
                                <img style="width:360px; height:100px; border:1px dashed #C3C3C3;" src="'.asset('/assets/img/upload-images.png"').'" />
                            </label>

                            <input type="file" name="images" id="images" style="display:none;" onchange="readURL(this)" accept="image/*"/>

                            <img style="width:360px; height:100px; border:1px dashed #C3C3C3; margin-bottom: 5px; '.(!empty($detail->banner_image) ? '' : 'display:none;').'" id="show_images" '.(!empty($detail->banner_image) ? 'src="'.$path.$detail->banner_image.'"' : '').' />
                            <br>
                            <div style="height: 40px;">
                                <span id="remove" class="btn btn-warning" onclick="removeImage()" style="cursor: pointer; margin-bottom: 5px; '.(!empty($detail->banner_image) ? '' : 'display:none;').'">
                                    Hapus
                                </span>
                                <span class="msg_images" id="msg_images" style="color: red;"></span>
                            </div>

                            <input type="hidden" id="file_image_value" name="file_image_value" value="'.$base_64_images.'"/>
                            <input type="hidden" id="file_image_value_old" name="file_image_value_old" value="'.$detail->banner_image.'"/>
                        </div>
                    </div>';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function edit(Request $request){

        $validation = true;
        $validation_text = '';

        if(empty($request->title)){
            $validation = $validation && false;
            $validation_text.= '<li>Judul dibutuhkan</li>';
        }else{
            $check_data = Post::select('id')->where('title', $request->title)->whereNotIn('id', [$request->id])->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Judul <b>'.$request->title.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->content)){
            $validation = $validation && false;
            $validation_text.= '<li>Konten dibutuhkan</li>';
        }

        if($validation){
            $results = true;
            $upload['status'] = true;
            $upload['file'] = "";

            if(!empty($request->file_image_value)){
                $path = env("WEB_UPLOAD")."post/";
                $max_size = '1024'; // in KB
                $type_allow = 'jpg|JPG|png|PNG|jpeg|JPEG|gif|GIF';
                $upload = $this->uploadBase64($request->file_image_value, $path, $max_size, $type_allow);
            }

            if($upload['status']){
                $date = date('Y-m-d H:i:s');
                $user_id = auth()->user()->id;

                $post = Post::where('id', $request->id)->first();

                $post->title = $request->title;
                $post->content = $request->content;
                if($post->slug_url == ""){
                    $post->slug_url = preg_replace("/[^A-Za-z0-9 -]/", '', str_replace(" ", "-",strtolower($request->title)));
                }
                $post->tags = $request->tags;
                $post->category = "PAGE";
                if(!empty($request->file_image_value)){
                    $post->banner_image = !empty($upload['file']) ? $upload['file'] : NULL;
                }
                $post->updated_by = $user_id;
                $post->updated_date = $date;
                $response = $post->save();

                if ($response) {
                    $result["status"] = TRUE;
                    $result["message"] = 'Sukses menambahkan data';
                    if(!empty($request->file_image_value) AND !empty($request->file_image_value_old)){
                        $path = env("WEB_UPLOAD")."post/";
                        @unlink($path.$request->file_image_value_old);
                    }
                } else {
                    $result["status"] = FALSE;
                    $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                    $result["message"].= '<li>Gagal menambahkan data</li>';
                    $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
                    $result["message"].= '</div>';
                    $path = env("WEB_UPLOAD")."post/";
                    @unlink($path.$request->file_image_value);
                }
            }else{
                $result["status"] = FALSE;
                $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                $result["message"].= '<li>'.$upload['message'].'</li>';
                $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
                $result["message"].= '</div>';
                @unlink(env("WEB_UPLOAD")."post/".$upload['file']);
            }

        }else{
            $result["status"] = FALSE;
            $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            $result["message"].= $validation_text;
            $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
            $result["message"].= '</div>';
        }

        return response()->json($result, 200);
    }

    public function detail(Request $request, $id){
        $detail = Post::where('id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="title">Judul</label>';
        $html.=     '<div id="title" class="detail-value">'.$detail->title.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="tags">Tag</label>';
        $html.=     '<div id="tags" class="detail-value">'.$detail->tags.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="content">Konten</label>';
        $html.=     '<div id="content" class="detail-value">'.$detail->content.'</div>';
        $html.= '</div>';
        $path = env("APP_WEB_URL")."upload/post/";
        $html.= '<div class="form-group">
                    <label for="file_image">Banner *</label>
                    <br>
                    <div class="row">
                        <div class="col" style="text-align: center; height: 245px;">
                            <img style="width:360px; height:100px; border:1px dashed #C3C3C3; margin-bottom: 5px; '.(!empty($detail->banner_image) ? '' : 'display:none;').'" id="show_images" '.(!empty($detail->banner_image) ? 'src="'.$path.$detail->banner_image.'"' : '').' />
                        </div>
                    </div>';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function delete(Request $request, $id){

        $detail = Post::where('id', $id)->first();

        $delete = Post::where('id', $id)->delete();
        if($delete){
            $path = env("WEB_UPLOAD")."post/";
            @unlink($path.$detail->banner_image);

            $result["status"] = TRUE;
            $result["message"] = 'Successfully deleted data';
        }else{
            $result["status"] = FALSE;
            $result["message"] = 'Failed deleted data';
        }

        return response()->json($result, 200);

    }
}
