<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Landingpage;

class LandingpageController extends Controller
{
    public function index()
    {
        $data['title'] = "Halaman Utama";

        $detail = Landingpage::where('id', 1)->first();

        $html = "";
        $path = env("APP_WEB_URL")."upload/homescreen/";
        if(!empty($detail->logo_image)){
            $type = pathinfo($path.$detail->logo_image, PATHINFO_EXTENSION);
            $base_64_logo = base64_encode(file_get_contents($path.$detail->logo_image));
            $base_64_logo = 'data:image/' . $type . ';base64,' .$base_64_logo;
        }else{
            $base_64_logo = '';
        }
        $html.= '<div class="form-group">
                    <label for="file_logo">Logo</label>
                    <br>
                    <div class="row">
                        <div class="col" style="text-align: center;">
                            <label id="label_logo" for="logo" style="cursor: pointer;'.(!empty($detail->logo_image) ? 'display:none;' : '').'">
                                <img style="width:100px; height:100px; border:1px dashed #C3C3C3;" src="'.asset('/assets/img/upload-images.png"').'" />
                            </label>

                            <input type="file" name="logo" id="logo" style="display:none;" onchange="readURLLogo(this)" accept="image/*"/>

                            <img style="width:100px; height:100px; border:1px dashed #C3C3C3; margin-bottom: 5px; '.(!empty($detail->logo_image) ? '' : 'display:none;').'" id="show_logo" '.(!empty($detail->logo_image) ? 'src="'.$path.$detail->logo_image.'"' : '').' />
                            <br>
                            <div style="height: 40px;">
                                <span id="remove_logo" class="btn btn-warning" onclick="removeLogo()" style="cursor: pointer; margin-bottom: 5px; '.(!empty($detail->logo_image) ? '' : 'display:none;').'">
                                    Hapus
                                </span>
                                <span class="msg_logo" id="msg_logo" style="color: red;"></span>
                            </div>

                            <input type="hidden" id="file_logo_value" name="file_logo_value" value="'.$base_64_logo.'"/>
                            <input type="hidden" id="file_logo_value_old" name="file_logo_value_old" value="'.$detail->logo_image.'"/>
                        </div>
                    </div>';
        $html.= '</div>';
        $path = env("APP_WEB_URL")."upload/homescreen/";
        if(!empty($detail->top_section_video_image)){
            $type = pathinfo($path.$detail->top_section_video_image, PATHINFO_EXTENSION);
            $base_64_images = base64_encode(file_get_contents($path.$detail->top_section_video_image));
            $base_64_images = 'data:image/' . $type . ';base64,' .$base_64_images;
        }else{
            $base_64_images = '';
        }
        $html.= '<div class="form-group">
                    <label for="file_image">Thumbnail Video Slider</label>
                    <br>
                    <div class="row">
                        <div class="col" style="text-align: center;">
                            <label id="label_images" for="images" style="cursor: pointer;'.(!empty($detail->top_section_video_image) ? 'display:none;' : '').'">
                                <img style="width:260px; height:150px; border:1px dashed #C3C3C3;" src="'.asset('/assets/img/upload-images.png"').'" />
                            </label>

                            <input type="file" name="images" id="images" style="display:none;" onchange="readURL(this)" accept="image/*"/>

                            <img style="width:260px; height:150px; border:1px dashed #C3C3C3; margin-bottom: 5px; '.(!empty($detail->top_section_video_image) ? '' : 'display:none;').'" id="show_images" '.(!empty($detail->top_section_video_image) ? 'src="'.$path.$detail->top_section_video_image.'"' : '').' />
                            <br>
                            <div style="height: 40px;">
                                <span id="remove" class="btn btn-warning" onclick="removeImage()" style="cursor: pointer; margin-bottom: 5px; '.(!empty($detail->top_section_video_image) ? '' : 'display:none;').'">
                                    Hapus
                                </span>
                                <span class="msg_images" id="msg_images" style="color: red;"></span>
                            </div>

                            <input type="hidden" id="file_image_value" name="file_image_value" value="'.$base_64_images.'"/>
                            <input type="hidden" id="file_image_value_old" name="file_image_value_old" value="'.$detail->top_section_video_image.'"/>
                        </div>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="top_section_video_url">Link Thumbnail Video Slider</label>';
        $html.=     '<textarea id="top_section_video_url" name="top_section_video_url" class="form-control">'.$detail->top_section_video_url.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="top_section">Judul Slider *</label>';
        $html.=     '<textarea id="top_section" name="top_section" class="form-control textarea" style="background-color: ">'.$detail->top_section.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="second_section">Sub Judul Slider *</label>';
        $html.=     '<textarea id="second_section" name="second_section" class="form-control textarea">'.$detail->second_section.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="main_section">Tentang IWF *</label>';
        $html.=     '<textarea id="main_section" name="main_section" class="form-control textarea">'.$detail->main_section.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="instagram">Link Instagram</label>';
        $html.=     '<input type="instagram" id="instagram" name="instagram" class="form-control" value="'.$detail->instagram.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="twitter">Link Twitter</label>';
        $html.=     '<input type="twitter" id="twitter" name="twitter" class="form-control" value="'.$detail->twitter.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="facebook">Link Facebook</label>';
        $html.=     '<input type="facebook" id="facebook" name="facebook" class="form-control" value="'.$detail->facebook.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="contact_us">Kontak Kami *</label>';
        $html.=     '<textarea id="contact_us" name="contact_us" class="form-control textarea">'.$detail->contact_us.'</textarea>';
        $html.= '</div>';

        $data['html'] = $html;

        return view('landingpage.index', $data);
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

    public function edit(Request $request){

        $validation = true;
        $validation_text = '';

        if(empty($request->top_section)){
            $validation = $validation && false;
            $validation_text.= '<li>Judul Slider dibutuhkan</li>';
        }

        if(empty($request->second_section)){
            $validation = $validation && false;
            $validation_text.= '<li>Sub Judul Slider dibutuhkan</li>';
        }

        if(empty($request->main_section)){
            $validation = $validation && false;
            $validation_text.= '<li>Tentang IWF dibutuhkan</li>';
        }

        if(empty($request->contact_us)){
            $validation = $validation && false;
            $validation_text.= '<li>Kontak Kami dibutuhkan</li>';
        }

        if($validation){
            $results = true;
            $uploadlogo['status'] = true;
            $uploadlogo['file'] = "";
            $upload['status'] = true;
            $upload['file'] = "";

            if(!empty($request->file_logo_value)){
                $path = env("WEB_UPLOAD")."homescreen/";
                $max_size = '1024'; // in KB
                $type_allow = 'jpg|JPG|png|PNG|jpeg|JPEG|gif|GIF';
                $uploadlogo = $this->uploadBase64($request->file_logo_value, $path, $max_size, $type_allow);
            }

            if(!empty($request->file_image_value)){
                $path = env("WEB_UPLOAD")."homescreen/";
                $max_size = '1024'; // in KB
                $type_allow = 'jpg|JPG|png|PNG|jpeg|JPEG|gif|GIF';
                $upload = $this->uploadBase64($request->file_image_value, $path, $max_size, $type_allow);
            }

            if($upload['status']){
                $date = date('Y-m-d H:i:s');
                $user_id = auth()->user()->id;

                $post = Landingpage::where('id', 1)->first();

                $post->top_section = $request->top_section;
                $post->top_section_video_url = $request->top_section_video_url;
                $post->second_section = $request->second_section;
                $post->main_section = $request->main_section;
                $post->instagram = $request->instagram;
                $post->twitter = $request->twitter;
                $post->facebook = $request->facebook;
                $post->contact_us = $request->contact_us;
                if(!empty($request->file_logo_value)){
                    $post->logo_image = !empty($uploadlogo['file']) ? $uploadlogo['file'] : NULL;
                }
                if(!empty($request->file_image_value)){
                    $post->top_section_video_image = !empty($upload['file']) ? $upload['file'] : NULL;
                }
                $response = $post->save();

                if ($response) {
                    $result["status"] = TRUE;
                    $result["message"] = 'Sukses menambahkan data';

                    $path = env("WEB_UPLOAD")."homescreen/";
                    if(!empty($request->file_logo_value) AND !empty($request->file_logo_value_old)){
                        @unlink($path.$request->file_logo_value_old);
                    }
                    if(!empty($request->file_image_value) AND !empty($request->file_image_value_old)){
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
                    $path = env("WEB_UPLOAD")."homescreen/";
                    @unlink($path.$request->file_logo_value);
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
                @unlink(env("WEB_UPLOAD")."homescreen/".$upload['file']);
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
}
