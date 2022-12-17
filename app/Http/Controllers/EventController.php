<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Setting;
use App\Models\Group;
use App\Models\EventGroup;
use App\Models\Sponsor;
use App\Models\Speaker;
use App\Models\EventSpeaker;
use App\Models\Provinces;
use App\Models\Regions;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\User;

class EventController extends Controller
{
    public function index()
    {
        $data['title'] = "Acara";
        return view('event.index', $data);
    }

    public function data(Request $request)
	{
        $keyword = $request->search['value'];
        $start = $request->post('start');
        $length = $request->post('length');

        $columns = array(
            0 => 'events.created_date',
            2 => 'events.name',
            3 => 'events.category',
            4 => 'events.startdate',
            5 => 'events.enddate',
            6 => 'events.quota',
            7 => 'pendaftar',
            8 => 'is_openeds',
            9 => 'is_active'
        );

        $order = $columns[$request->order[0]['column']];
        $dir = $request->order[0]['dir'];

		$list = Event::selectRaw('
            events.id,
            events.name,
            events.category,
            events.startdate,
            events.enddate,
            events.quota,
            events.is_active,
            (SELECT COUNT(em1.id) FROM event_member AS em1 WHERE em1.event_id = events.id) AS pendaftar,
            (SELECT COUNT(em2.id) FROM event_member AS em2 WHERE em2.event_id = events.id AND em2.is_opened = 1) AS is_openeds
        ');
        if(!empty($keyword)){
            $keyword = '%'.$keyword .'%';
            $query = $list->where(function($q) use($keyword) {
                $q->where('events.name', 'LIKE', $keyword)
                ->orWhere('events.category', 'LIKE', $keyword)
                ->orWhere('events.startdate', 'LIKE', $keyword)
                ->orWhere('events.enddate', 'LIKE', $keyword)
                ->orWhere('events.quota', 'LIKE', $keyword)
                ->orWhere('events.is_active', 'LIKE', ($keyword == "%Yes%" ? "%1%" : ($keyword == "%No%" ? "%0%" : "")))
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
                 $row[] = '<a class="btn btn-sm btn-success" href="/event/event-user-export/'.$value->id.'" target="_blank" title="User Per Acara"><i class="fas fa-file-excel"></i></a>
                 <a class="btn btn-sm btn-info" href="javascript:void(0)" title="Detail" onclick="detail(\''.$value->id.'\')"><i class="fas fa-search"></i></a>
                 <a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit('."'".$value->id."'".')"><i class="fas fa-edit"></i></a>
                 <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Delete" onclick="deletes('."'".$value->id."','".$value->name."'".')"><i class="fas fa-trash-alt"></i></a>';
                $row[] = $value->name;
                $row[] = !empty($value->category) ? ucwords(strtolower($value->category)) : "";
                $row[] = !empty($value->startdate) ? date("d-m-Y H:i:s", strtotime($value->startdate)) : "";
                $row[] = !empty($value->enddate) ? date("d-m-Y H:i:s", strtotime($value->enddate)) : "";
                $row[] = $value->quota;
                // (!empty($detail->prices) ? number_format($detail->prices, 0, ",", ".") : "")
                $row[] = (!empty($value->pendaftar) ? number_format($value->pendaftar, 0, ",", ".") : 0);
                $row[] = (!empty($value->is_openeds) ? number_format($value->is_openeds, 0, ",", ".") : 0);
                $row[] = $value->is_active == 1 ? "Yes" : "No";

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

        $setting = Setting::where('setting_key', 'iwf_events')->first();
        $iwf_events = explode(';', $setting->setting_value);

        $category = explode("/", "CONFERENCE/WORKSHOP/EVENT/QA/SEMINAR/MASTERCLASS/OTHER");
        $group = Group::orderBy('position')->get();
        $sponsor = Sponsor::orderBy('show_order')->get();
        $speaker = Speaker::get();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="category">Kategori *</label>';
        $html.=     '<select id="category" name="category" class="form-control"">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($category)){
            foreach ($category as $key => $value) {
        $html.=         '<option value="'.$value.'">';
        $html.=             $value;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="period_code">Periode *</label>';
        $html.=     '<select id="period_code" name="period_code" class="form-control"">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($iwf_events)){
            foreach ($iwf_events as $key => $value) {
        $html.=         '<option value="'.$value.'">';
        $html.=             $value;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="event_group">Kelompok *</label>';
        $html.=     '<select id="event_group" name="event_group" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($group)){
            foreach ($group as $key => $value) {
        $html.=         '<option value="'.$value->id.'">';
        $html.=             $value->group_name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="mode">Mode *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode1" value="OFFLINE" checked>
                        <label class="form-check-label" for="mode1">
                            Offline
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode2" value="ONLINE" >
                        <label class="form-check-label" for="mode2">
                            Online
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="highlight">Highlight *</label>';
        $html.=     '<textarea id="highlight" name="highlight" class="form-control textarea"></textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="description">Deskipsi *</label>';
        $html.=     '<textarea id="description" name="description" class="form-control textarea"></textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="is_active">Status *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="is_active" id="is_active1" value="1" checked>
                        <label class="form-check-label" for="is_active1">
                            Aktif
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="is_active" id="is_active2" value="0" >
                        <label class="form-check-label" for="is_active2">
                            Tidak Aktif
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="startdate">Tanggal Mulai *</label>';
        $html.=     '<input type="text" id="startdate" name="startdate" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="enddate">Tanggal Selesai *</label>';
        $html.=     '<input type="text" id="enddate" name="enddate" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="last_register">Pendaftaran Terakhir *</label>';
        $html.=     '<input type="text" id="last_register" name="last_register" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="location">Lokasi *</label>';
        $html.=     '<input type="text" id="location" name="location" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="quota">Kuota *</label>';
        $html.=     '<input type="text" id="quota" name="quota" class="form-control curr">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="prices">Harga <i>(*kosongkan jika gratis)</i></label>';
        $html.=     '<input type="text" id="prices" name="prices" class="form-control curr">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="sponsor">Sponsor</label>';
        $html.=     '<div><select id="sponsor" name="sponsor[]" class="form-control" multiple="multiple" style="width:100%">';
        if(!empty($sponsor)){
            foreach ($sponsor as $key => $value) {
        $html.=         '<option value="'.$value->id.'">';
        $html.=             $value->full_name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select></div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="speaker">Pembicara</label>';
        $html.=     '<div><select id="speaker" name="speaker[]" class="form-control" multiple="multiple" style="width:100%">';
        if(!empty($speaker)){
            foreach ($speaker as $key => $value) {
        $html.=         '<option value="'.$value->id.'">';
        $html.=             $value->full_name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select></div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="video_meeting_link">Link Pertemuan Online</label>';
        $html.=     '<textarea id="video_meeting_link" name="video_meeting_link" class="form-control"></textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="registered_information">Info Registrasi</label>';
        $html.=     '<textarea id="registered_information" name="registered_information" class="form-control textarea"></textarea>';
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

        if(empty($request->name)){
            $validation = $validation && false;
            $validation_text.= '<li>Nama dibutuhkan</li>';
        }else{
            $check_data = Event::select('id')->where('name', $request->name)->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nama <b>'.$request->name.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->category)){
            $validation = $validation && false;
            $validation_text.= '<li>Kategori dibutuhkan</li>';
        }

        if(empty($request->period_code)){
            $validation = $validation && false;
            $validation_text.= '<li>Periode dibutuhkan</li>';
        }

        if(empty($request->event_group)){
            $validation = $validation && false;
            $validation_text.= '<li>Kelompok dibutuhkan</li>';
        }

        if(empty($request->mode)){
            $validation = $validation && false;
            $validation_text.= '<li>Mode dibutuhkan</li>';
        }

        if(empty($request->highlight)){
            $validation = $validation && false;
            $validation_text.= '<li>Highlight dibutuhkan</li>';
        }

        if(empty($request->description)){
            $validation = $validation && false;
            $validation_text.= '<li>Deskipsi dibutuhkan</li>';
        }

        if(!isset($request->is_active) && $request->is_active == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Status dibutuhkan</li>';
        }

        if(empty($request->startdate)){
            $validation = $validation && false;
            $validation_text.= '<li>Tanggal Mulai dibutuhkan</li>';
        }

        if(empty($request->enddate)){
            $validation = $validation && false;
            $validation_text.= '<li>Tanggal Selesai dibutuhkan</li>';
        }

        if(empty($request->last_register)){
            $validation = $validation && false;
            $validation_text.= '<li>Pendaftaran Terakhir dibutuhkan</li>';
        }

        if(empty($request->location)){
            $validation = $validation && false;
            $validation_text.= '<li>Lokasi dibutuhkan</li>';
        }

        if(empty($request->quota)){
            $validation = $validation && false;
            $validation_text.= '<li>Kuota dibutuhkan</li>';
        }

        // if(empty($request->file_image_value)){
        //     $validation = $validation && false;
        //     $validation_text.= '<li>Banner dibutuhkan</li>';
        // }


        if($validation){
            $results = true;
            $upload['status'] = true;
            $upload['file'] = "";

            if(!empty($request->file_image_value)){
                $path = env("WEB_UPLOAD")."event/";
                $max_size = '1024'; // in KB
                $type_allow = 'jpg|JPG|png|PNG|jpeg|JPEG|gif|GIF';
                $upload = $this->uploadBase64($request->file_image_value, $path, $max_size, $type_allow);
            }

            if($upload['status']){
                $date = date('Y-m-d H:i:s');
                $user_id = auth()->user()->id;

                $event = new Event;

                $event->name = $request->name;
                $event->category = $request->category;
                $event->period_code = $request->period_code;
                $event->mode = $request->mode;
                $event->highlight = $request->highlight;
                $event->description = $request->description;
                $event->is_active = $request->is_active;
                $event->startdate = !empty($request->startdate) ? $request->startdate.":00" : NULL;
                $event->enddate = !empty($request->enddate) ? $request->enddate.":00" : NULL;
                $event->last_register = !empty($request->last_register) ? $request->last_register.":00" : NULL;
                $event->location = $request->location;
                $event->quota = !empty($request->quota) ? str_replace(".","",$request->quota) : NULL;
                $event->prices = !empty($request->prices) ? str_replace(".","",$request->prices) : NULL;
                $event->sponsors = !empty($request->sponsor) ? implode(",", $request->sponsor) : NULL;
                $event->shortlink = preg_replace("/[^A-Za-z0-9 -]/", '', str_replace(" ", "-",strtolower($request->name)));
                $event->video_meeting_link = $request->video_meeting_link;
                $event->registered_information = $request->registered_information;
                $event->banner_image = !empty($upload['file']) ? $upload['file'] : NULL;
                $event->created_by = $user_id;
                $event->created_date = $date;
                $response = $event->save();

                if($response){
                    if($request->has('event_group')){
                        $event_group = new EventGroup;
                        $event_group->group_id = $request->event_group;
                        $event_group->event_id = $event->id;
                        $event_group->save();
                    }

                    if($request->has('speaker')){
                        foreach ($request->speaker as $key => $value) {
                            $event_speaker = new EventSpeaker;
                            $event_speaker->speaker_id = $value;
                            $event_speaker->event_id = $event->id;
                            $event_speaker->save();
                        }
                    }
                }

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
                @unlink(env("WEB_UPLOAD")."event/".$upload['file']);
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

        $detail = Event::leftJoin('event_group AS eg', 'eg.event_id', 'events.id')
                ->where('events.id', $id)
                ->first();
        $speaker_data = EventSpeaker::selectRaw('GROUP_CONCAT(speaker_id) AS speaker_data')->where('event_id', $id)->first();

        $setting = Setting::where('setting_key', 'iwf_events')->first();
        $iwf_events = explode(';', $setting->setting_value);

        $category = explode("/", "CONFERENCE/WORKSHOP/EVENT/QA/SEMINAR/MASTERCLASS/OTHER");
        $group = Group::orderBy('position')->get();
        $sponsor = Sponsor::orderBy('show_order')->get();
        $speaker = Speaker::get();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control" value="'.$detail->name.'">';
        $html.=     '<input type="hidden" id="id" name="id" class="form-control" value="'.$id.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="category">Kategori *</label>';
        $html.=     '<select id="category" name="category" class="form-control"">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($category)){
            foreach ($category as $key => $value) {
        $html.=         '<option value="'.$value.'" '.($detail->category == $value ? "selected" : "").'>';
        $html.=             $value;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="period_code">Periode *</label>';
        $html.=     '<select id="period_code" name="period_code" class="form-control"">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($iwf_events)){
            foreach ($iwf_events as $key => $value) {
        $html.=         '<option value="'.$value.'" '.($detail->period_code == $value ? "selected" : "").'>';
        $html.=             $value;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="event_group">Kelompok *</label>';
        $html.=     '<select id="event_group" name="event_group" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($group)){
            foreach ($group as $key => $value) {
        $html.=         '<option value="'.$value->id.'" '.($detail->group_id == $value->id ? "selected" : "").'>';
        $html.=             $value->group_name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="mode">Mode *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode1" value="OFFLINE" '.($detail->mode == "OFFLINE" ? "checked" : "").'>
                        <label class="form-check-label" for="mode1">
                            Offline
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="mode" id="mode2" value="ONLINE" '.($detail->mode == "ONLINE" ? "checked" : "").'>
                        <label class="form-check-label" for="mode2">
                            Online
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="highlight">Highlight *</label>';
        $html.=     '<textarea id="highlight" name="highlight" class="form-control textarea">'.$detail->highlight.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="description">Deskipsi *</label>';
        $html.=     '<textarea id="description" name="description" class="form-control textarea">'.$detail->description.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="is_active">Status *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="is_active" id="is_active1" value="1" '.($detail->is_active == "1" ? "checked" : "").'>
                        <label class="form-check-label" for="is_active1">
                            Aktif
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="is_active" id="is_active2" value="0" '.($detail->is_active == "0" ? "checked" : "").'>
                        <label class="form-check-label" for="is_active2">
                            Tidak Aktif
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="startdate">Tanggal Mulai *</label>';
        $html.=     '<input type="text" id="startdate" name="startdate" class="form-control" value="'.(!empty($detail->startdate) ? substr($detail->startdate, 0, -3) : "").'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="enddate">Tanggal Selesai *</label>';
        $html.=     '<input type="text" id="enddate" name="enddate" class="form-control"  value="'.(!empty($detail->enddate) ? substr($detail->enddate, 0, -3) : "").'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="last_register">Tanggal Selesai *</label>';
        $html.=     '<input type="text" id="last_register" name="last_register" class="form-control"  value="'.(!empty($detail->last_register) ? substr($detail->last_register, 0, -3) : "").'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="location">Lokasi *</label>';
        $html.=     '<input type="text" id="location" name="location" class="form-control" value="'.$detail->location.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="quota">Kuota *</label>';
        $html.=     '<input type="text" id="quota" name="quota" class="form-control curr" value="'.$detail->quota.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="prices">Harga <i>(*kosongkan jika gratis)</i></label>';
        $html.=     '<input type="text" id="prices" name="prices" class="form-control curr" value="'.$detail->prices.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="sponsor">Sponsor</label>';
        $html.=     '<div><select id="sponsor" name="sponsor[]" class="form-control" multiple="multiple" style="width:100%">';
        if(!empty($sponsor)){
            foreach ($sponsor as $key => $value) {
        $html.=         '<option value="'.$value->id.'" '.(in_array($value->id ,explode(',',$detail->sponsors)) ? "selected" : "").'>';
        $html.=             $value->full_name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select></div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="speaker">Pembicara</label>';
        $html.=     '<div><select id="speaker" name="speaker[]" class="form-control" multiple="multiple" style="width:100%">';
        if(!empty($speaker)){
            foreach ($speaker as $key => $value) {
        $html.=         '<option value="'.$value->id.'" '.(in_array($value->id ,explode(',',$speaker_data->speaker_data)) ? "selected" : "").'>';
        $html.=             $value->full_name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select></div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="video_meeting_link">Link Pertemuan Online</label>';
        $html.=     '<textarea id="video_meeting_link" name="video_meeting_link" class="form-control">'.$detail->video_meeting_link.'</textarea>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="registered_information">Info Registrasi</label>';
        $html.=     '<textarea id="registered_information" name="registered_information" class="form-control textarea">'.$detail->registered_information.'</textarea>';
        $html.= '</div>';
        $path = env("APP_WEB_URL")."upload/event/";
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

        if(empty($request->name)){
            $validation = $validation && false;
            $validation_text.= '<li>Nama dibutuhkan</li>';
        }else{
            $check_data = Event::select('id')->where('name', $request->name)->whereNotIn('id', [$request->id])->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nama <b>'.$request->name.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->category)){
            $validation = $validation && false;
            $validation_text.= '<li>Kategori dibutuhkan</li>';
        }

        if(empty($request->period_code)){
            $validation = $validation && false;
            $validation_text.= '<li>Periode dibutuhkan</li>';
        }

        if(empty($request->event_group)){
            $validation = $validation && false;
            $validation_text.= '<li>Kelompok dibutuhkan</li>';
        }

        if(empty($request->mode)){
            $validation = $validation && false;
            $validation_text.= '<li>Mode dibutuhkan</li>';
        }

        if(empty($request->highlight)){
            $validation = $validation && false;
            $validation_text.= '<li>Highlight dibutuhkan</li>';
        }

        if(empty($request->description)){
            $validation = $validation && false;
            $validation_text.= '<li>Deskipsi dibutuhkan</li>';
        }

        if(!isset($request->is_active) && $request->is_active == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Status dibutuhkan</li>';
        }

        if(empty($request->startdate)){
            $validation = $validation && false;
            $validation_text.= '<li>Tanggal Mulai dibutuhkan</li>';
        }

        if(empty($request->enddate)){
            $validation = $validation && false;
            $validation_text.= '<li>Tanggal Selesai dibutuhkan</li>';
        }

        if(empty($request->enddate)){
            $validation = $validation && false;
            $validation_text.= '<li>Pendaftaran Terakhir dibutuhkan</li>';
        }

        if(empty($request->location)){
            $validation = $validation && false;
            $validation_text.= '<li>Lokasi dibutuhkan</li>';
        }

        if(empty($request->quota)){
            $validation = $validation && false;
            $validation_text.= '<li>Kuota dibutuhkan</li>';
        }

        // if(empty($request->file_image_value)){
        //     $validation = $validation && false;
        //     $validation_text.= '<li>Banner dibutuhkan</li>';
        // }


        if($validation){
            $results = true;
            $upload['status'] = true;
            $upload['file'] = "";

            if(!empty($request->file_image_value)){
                $path = env("WEB_UPLOAD")."event/";
                $max_size = '1024'; // in KB
                $type_allow = 'jpg|JPG|png|PNG|jpeg|JPEG|gif|GIF';
                $upload = $this->uploadBase64($request->file_image_value, $path, $max_size, $type_allow);
            }

            if($upload['status']){
                $date = date('Y-m-d H:i:s');
                $user_id = auth()->user()->id;

                $event = Event::where('id', $request->id)->first();

                $event->name = $request->name;
                $event->category = $request->category;
                $event->period_code = $request->period_code;
                $event->mode = $request->mode;
                $event->highlight = $request->highlight;
                $event->description = $request->description;
                $event->is_active = $request->is_active;
                $event->startdate = !empty($request->startdate) ? $request->startdate.":00" : NULL;
                $event->enddate = !empty($request->enddate) ? $request->enddate.":00" : NULL;
                $event->last_register = !empty($request->last_register) ? $request->last_register.":00" : NULL;
                $event->location = $request->location;
                $event->quota = !empty($request->quota) ? str_replace(".","",$request->quota) : NULL;
                $event->prices = !empty($request->prices) ? str_replace(".","",$request->prices) : NULL;
                $event->sponsors = !empty($request->sponsor) ? implode(",", $request->sponsor) : NULL;
                if($event->shortlink == ""){
                    $event->shortlink = preg_replace("/[^A-Za-z0-9 -]/", '', str_replace(" ", "-",strtolower($request->name)));
                }
                $event->video_meeting_link = $request->video_meeting_link;
                $event->registered_information = $request->registered_information;
                if(!empty($request->file_image_value)){
                    $event->banner_image = !empty($upload['file']) ? $upload['file'] : NULL;
                }
                $event->updated_by = $user_id;
                $event->updated_date = $date;
                $response = $event->save();

                if($response){
                    if($request->has('event_group')){
                        $event_group_delete = EventGroup::where('event_id', $request->id)->delete();

                        $event_group = new EventGroup;
                        $event_group->group_id = $request->event_group;
                        $event_group->event_id = $event->id;
                        $event_group->save();

                    }

                    if($request->has('speaker')){
                        $event_speaker_delete = EventSpeaker::where('event_id', $request->id)->delete();

                        foreach ($request->speaker as $key => $value) {
                            $event_speaker = new EventSpeaker;
                            $event_speaker->speaker_id = $value;
                            $event_speaker->event_id = $event->id;
                            $event_speaker->save();
                        }
                    }
                }

                if ($response) {
                    $result["status"] = TRUE;
                    $result["message"] = 'Sukses menambahkan data';
                    if(!empty($request->file_image_value) AND !empty($request->file_image_value_old)){
                        $path = env("WEB_UPLOAD")."event/";
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
                    $path = env("WEB_UPLOAD")."event/";
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
                @unlink(env("WEB_UPLOAD")."event/".$upload['file']);
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

        $detail = Event::leftJoin('event_group AS eg', 'eg.event_id', 'events.id')
                ->leftJoin('groups AS g', 'g.id', 'eg.group_id')
                ->where('events.id', $id)
                ->first();


        $data_sponsors_text = "";
        if(!empty($detail->sponsors)){
            $data_spomsors_exist = explode(',',$detail->sponsors);
            $data_sponsors = Sponsor::selectRaw('GROUP_CONCAT(full_name) AS name')->whereIn('id', $data_spomsors_exist)->first();
            $data_sponsors_text = !empty($data_sponsors->name) ? str_replace(",",", ",$data_sponsors->name) : "";
        }

        $data_speaker_text = "";
        $speaker_data = EventSpeaker::selectRaw('GROUP_CONCAT(s.full_name) AS name')
                        ->leftJoin('speakers AS s', 's.id', 'event_speaker.speaker_id')
                        ->where('event_id', $id)
                        ->first();
        if(!empty($speaker_data)){
            $data_speaker_text = !empty($speaker_data->name) ? str_replace(",",", ",$speaker_data->name) : "";
        }

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama</label>';
        $html.=     '<div id="name" class="detail-value">'.$detail->name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="category">Kategori</label>';
        $html.=     '<div id="category" class="detail-value">'.(ucwords(strtolower($detail->category))).'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="period_code">Periode</label>';
        $html.=     '<div id="period_code" class="detail-value">'.$detail->period_code.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="event_group">Kelompok</label>';
        $html.=     '<div id="event_group" class="detail-value">'.$detail->group_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="mode">Mode</label>';
        $html.=     '<div id="mode" class="detail-value">'.(ucwords(strtolower($detail->mode))).'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="highlight">Highlight</label>';
        $html.=     '<div id="highlight" class="detail-value">'.$detail->highlight.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="description">Deskipsi</label>';
        $html.=     '<div id="description" class="detail-value">'.$detail->description.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="is_active">Status</label>';
        $html.=     '<div id="is_active" class="detail-value">'.($detail->is_active == "1" ? "Aktif" : "Tidak Aktif").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="startdate">Tanggal Mulai</label>';
        $html.=     '<div id="startdate" class="detail-value">'.(!empty($detail->startdate) ? substr($detail->startdate, 0, -3) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="enddate">Tanggal Selesai</label>';
        $html.=     '<div id="enddate" class="detail-value">'.(!empty($detail->enddate) ? substr($detail->enddate, 0, -3) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="last_register">Pendaftaran Terakhir</label>';
        $html.=     '<div id="last_register" class="detail-value">'.(!empty($detail->last_register) ? substr($detail->last_register, 0, -3) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="location">Lokasi</label>';
        $html.=     '<div id="location" class="detail-value">'.$detail->location.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="quota">Kuota</label>';
        $html.=     '<div id="quota" class="detail-value">'.(!empty($detail->quota) ? number_format($detail->quota, 0, ",", ".") : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="prices">Harga</label>';
        $html.=     '<div id="quota" class="detail-value">'.(!empty($detail->prices) ? number_format($detail->prices, 0, ",", ".") : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="sponsor">Sponsor</label>';
        $html.=     '<div id="sponsor" class="detail-value">'.$data_sponsors_text.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="speaker">Pembicara</label>';
        $html.=     '<div id="speaker" class="detail-value">'.$data_speaker_text.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="video_meeting_link">Link Pertemuan Online</label>';
        $html.=     '<div id="video_meeting_link" class="detail-value">'.$detail->video_meeting_link.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="registered_information">Info Registrasi</label>';
        $html.=     '<div id="registered_information" class="detail-value">'.$detail->registered_information.'</div>';
        $html.= '</div>';
        $path = env("APP_WEB_URL")."upload/event/";
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

        $event = Event::where('id', $id)->first();

        $delete = EventGroup::where('event_id', $id)->delete();
        $delete = $delete && EventSpeaker::where('event_id', $id)->delete();
        $delete = $delete && Event::where('id', $id)->delete();
        if($delete){
            $path = env("WEB_UPLOAD")."event/";
            @unlink($path.$event->banner_image);

            $result["status"] = TRUE;
            $result["message"] = 'Successfully deleted data';
        }else{
            $result["status"] = FALSE;
            $result["message"] = 'Failed deleted data';
        }

        return response()->json($result, 200);

    }

    public function eventExport(Request $request){

        $user_id = auth()->user()->id;

        @unlink(redirect('Event_'.$user_id.'.xlsx'));

        $styleBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $styleHeader = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'FFA0A0A0',
                ],
                'endColor' => [
                    'argb' => 'FFFFFFFF',
                ],
            ],'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();

        // sheet 1
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Data Acara');

        // style auto width column
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('O')->setAutoSize(true);

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama');
        $sheet->setCellValue('C1', 'Kategori');
        $sheet->setCellValue('D1', 'Periode');
        $sheet->setCellValue('E1', 'Mode');
        $sheet->setCellValue('F1', 'Kelompok');
        $sheet->setCellValue('G1', 'Status');
        $sheet->setCellValue('H1', 'Tanggal Mulai');
        $sheet->setCellValue('I1', 'Tanggal Selesai');
        $sheet->setCellValue('J1', 'Lokasi');
        $sheet->setCellValue('K1', 'Kuota');
        $sheet->setCellValue('L1', 'Harga');
        $sheet->setCellValue('M1', 'Sponsor');
        $sheet->setCellValue('N1', 'Pembicara');
        $sheet->setCellValue('O1', 'Link Pertemuan Online');
        $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:O1')->getFont()->setBold(true);
        $sheet->getStyle('A1:O1')->applyFromArray($styleHeader);

        $data = Event::select('events.*', 'eg.*', 'g.id AS group_id', 'g.group_name')
                ->leftJoin('event_group AS eg', 'eg.event_id', 'events.id')
                ->leftJoin('groups AS g', 'g.id', 'eg.group_id')
                ->get();

        $row = $row_data = 2;
        $no = 1;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $data_sponsors_text = "";
                if(!empty($value->sponsors)){
                    $data_spomsors_exist = explode(',',$value->sponsors);
                    $data_sponsors = Sponsor::selectRaw('GROUP_CONCAT(full_name) AS name')->whereIn('id', $data_spomsors_exist)->first();
                    $data_sponsors_text = !empty($data_sponsors->name) ? str_replace(",",", ",$data_sponsors->name) : "";
                }

                $data_speaker_text = "";
                $speaker_data = EventSpeaker::selectRaw('GROUP_CONCAT(s.full_name) AS name')
                                ->leftJoin('speakers AS s', 's.id', 'event_speaker.speaker_id')
                                ->where('event_id', $value->id)
                                ->first();
                if(!empty($speaker_data)){
                    $data_speaker_text = !empty($speaker_data->name) ? str_replace(",",", ",$speaker_data->name) : "";
                }

                $sheet->setCellValue('A'.$row, $no)->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('B'.$row, $value->name);
                $sheet->setCellValue('C'.$row, (ucwords(strtolower($value->category))));
                $sheet->setCellValue('D'.$row, $value->period_code);
                $sheet->setCellValue('E'.$row, (ucwords(strtolower($value->mode))));
                $sheet->setCellValue('F'.$row, $value->group_name);
                $sheet->setCellValue('G'.$row, ($value->is_active == "1" ? "Aktif" : "Tidak Aktif"));
                $sheet->setCellValue('H'.$row, (!empty($value->startdate) ? substr($value->startdate, 0, -3) : ""));
                $sheet->setCellValue('I'.$row, (!empty($value->enddate) ? substr($value->enddate, 0, -3) : ""));
                $sheet->setCellValue('J'.$row, $value->location);
                $sheet->setCellValue('K'.$row, (!empty($value->quota) ? number_format($value->quota, 0, ",", ".") : ""));
                $sheet->setCellValue('L'.$row, (!empty($value->prices) ? number_format($value->prices, 0, ",", ".") : ""));
                $sheet->setCellValue('M'.$row, $data_sponsors_text);
                $sheet->setCellValue('N'.$row, $data_speaker_text);
                $sheet->setCellValue('O'.$row, $value->video_meeting_link);
                $no++;
                $row++;
            }
        }

        $sheet->getStyle('A'.$row_data.':O'.($row-1))->applyFromArray($styleBorder);

        $writer = new Xlsx($spreadsheet);
        $date_now = date('YmdHis');
        $writer->save('Event_'.$user_id.'.xlsx');

        return redirect('Event_'.$user_id.'.xlsx');

    }

    public function eventUserExport(Request $request, $id){

        $user_id = auth()->user()->id;

        @unlink(redirect('Event_User_'.$user_id.'.xlsx'));

        $styleBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $styleHeader = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'FFA0A0A0',
                ],
                'endColor' => [
                    'argb' => 'FFFFFFFF',
                ],
            ],'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();

        // sheet 1
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Data Acara');

        // style auto width column
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('O')->setAutoSize(true);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('R')->setAutoSize(true);
        $sheet->getColumnDimension('S')->setAutoSize(true);
        $sheet->getColumnDimension('T')->setAutoSize(true);

        $detail = Event::leftJoin('event_group AS eg', 'eg.event_id', 'events.id')
                ->leftJoin('groups AS g', 'g.id', 'eg.group_id')
                ->where('events.id', $id)
                ->first();


        $data_sponsors_text = "";
        if(!empty($detail->sponsors)){
            $data_spomsors_exist = explode(',',$detail->sponsors);
            $data_sponsors = Sponsor::selectRaw('GROUP_CONCAT(full_name) AS name')->whereIn('id', $data_spomsors_exist)->first();
            $data_sponsors_text = !empty($data_sponsors->name) ? str_replace(",",", ",$data_sponsors->name) : "";
        }

        $data_speaker_text = "";
        $speaker_data = EventSpeaker::selectRaw('GROUP_CONCAT(s.full_name) AS name')
                        ->leftJoin('speakers AS s', 's.id', 'event_speaker.speaker_id')
                        ->where('event_id', $id)
                        ->first();
        if(!empty($speaker_data)){
            $data_speaker_text = !empty($speaker_data->name) ? str_replace(",",", ",$speaker_data->name) : "";
        }

        $sheet->setCellValue('A1', 'Nama');
        $sheet->setCellValue('A2', 'Kategori');
        $sheet->setCellValue('A3', 'Periode');
        $sheet->setCellValue('A4', 'Mode');
        $sheet->setCellValue('A5', 'Kelompok');
        $sheet->setCellValue('A6', 'Status');
        $sheet->setCellValue('A7', 'Tanggal Mulai');
        $sheet->setCellValue('A8', 'Tanggal Selesai');
        $sheet->setCellValue('A9', 'Lokasi');
        $sheet->setCellValue('A10', 'Kuota');
        $sheet->setCellValue('A11', 'Harga');
        $sheet->setCellValue('A12', 'Sponsor');
        $sheet->setCellValue('A13', 'Pembicara');
        $sheet->setCellValue('A14', 'Link Pertemuan Online');
        $sheet->getStyle('A1:A14')->getFont()->setBold(true);

        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        $sheet->mergeCells('A3:B3');
        $sheet->mergeCells('A4:B4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('A7:B7');
        $sheet->mergeCells('A8:B8');
        $sheet->mergeCells('A9:B9');
        $sheet->mergeCells('A10:B10');
        $sheet->mergeCells('A11:B11');
        $sheet->mergeCells('A12:B12');
        $sheet->mergeCells('A13:B13');
        $sheet->mergeCells('A14:B14');

        $sheet->setCellValue('C1',' '.$detail->name);
        $sheet->setCellValue('C2',' '.(ucwords(strtolower($detail->category))));
        $sheet->setCellValue('C3',' '.$detail->period_code);
        $sheet->setCellValue('C4',' '.(ucwords(strtolower($detail->mode))));
        $sheet->setCellValue('C5',' '.$detail->group_name);
        $sheet->setCellValue('C6',' '.($detail->is_active == "1" ? "Aktif" : "Tidak Aktif"));
        $sheet->setCellValue('C7',' '.(!empty($detail->startdate) ? substr($detail->startdate, 0, -3) : ""));
        $sheet->setCellValue('C8',' '.(!empty($detail->enddate) ? substr($detail->enddate, 0, -3) : ""));
        $sheet->setCellValue('C9',' '.$detail->location);
        $sheet->setCellValue('C10',' '.(!empty($detail->quota) ? number_format($detail->quota, 0, ",", ".") : ""));
        $sheet->setCellValue('C11',' '.(!empty($detail->prices) ? number_format($detail->prices, 0, ",", ".") : ""));
        $sheet->setCellValue('C12',' '.$data_sponsors_text);
        $sheet->setCellValue('C13',' '.$data_speaker_text);
        $sheet->setCellValue('C14',' '.$detail->video_meeting_link);

        $sheet->mergeCells('C1:T1');
        $sheet->mergeCells('C2:T2');
        $sheet->mergeCells('C3:T3');
        $sheet->mergeCells('C4:T4');
        $sheet->mergeCells('C5:T5');
        $sheet->mergeCells('C6:T6');
        $sheet->mergeCells('C7:T7');
        $sheet->mergeCells('C8:T8');
        $sheet->mergeCells('C9:T9');
        $sheet->mergeCells('C10:T10');
        $sheet->mergeCells('C11:T11');
        $sheet->mergeCells('C12:T12');
        $sheet->mergeCells('C13:T13');
        $sheet->mergeCells('C14:T14');

        $row = $row_data = 16;

        $sheet->setCellValue('A'.$row, 'No');
        $sheet->setCellValue('B'.$row, 'Id Member');
        $sheet->setCellValue('C'.$row, 'Nama');
        $sheet->setCellValue('D'.$row, 'Email');
        $sheet->setCellValue('E'.$row, 'Nomer Telepon');
        $sheet->setCellValue('F'.$row, 'Tanggal Lahir');
        $sheet->setCellValue('G'.$row, 'Pekerjaan Utama');
        $sheet->setCellValue('H'.$row, 'Alamat');
        $sheet->setCellValue('I'.$row, 'Provinsi');
        $sheet->setCellValue('J'.$row, 'Kota / Kabupaten');
        $sheet->setCellValue('K'.$row, 'Kode Pos');
        $sheet->setCellValue('L'.$row, 'Kegiatan IWF');
        $sheet->setCellValue('M'.$row, 'Facebook');
        $sheet->setCellValue('N'.$row, 'Instagram');
        $sheet->setCellValue('O'.$row, 'Linkedin');
        $sheet->setCellValue('P'.$row, 'Peran');
        $sheet->setCellValue('Q'.$row, 'Diverifikasi?');
        $sheet->setCellValue('R'.$row, 'Diblok?');
        $sheet->setCellValue('S'.$row, 'Kode Tiket');
        $sheet->setCellValue('T'.$row, 'Tanggal Pemesanan');
        $sheet->getStyle('A'.$row.':T'.$row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A'.$row.':T'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row.':T'.$row)->applyFromArray($styleHeader);

        $data = User::select('users.*', 'provinces.name AS provinces_name', 'regions.name AS regions_name', 'em.*')
        ->leftJoin('provinces', 'provinces.id', '=', 'users.province_id')
        ->leftJoin('regions', 'regions.id', '=', 'users.region_id')
        ->join('event_member AS em', 'em.user_id', 'users.id')
        ->where('em.event_id', $id)
        ->where('em.is_cancel', 0)
        ->get();

        $row++;
        $no = 1;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $sheet->setCellValue('A'.$row, $no)->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('B'.$row, $value->number);
                $sheet->setCellValue('C'.$row, $value->full_name);
                $sheet->setCellValue('D'.$row, $value->email);
                $sheet->setCellValue('E'.$row, $value->phone_number);
                $sheet->setCellValue('F'.$row, $value->birthday);
                if(!empty($value->occupation_company_name) && !empty($value->occupation_company_detail)){
                    $occupation_str = " (".$value->occupation_company_name.", ".$value->occupation_company_detail.")";
                }else{
                    $occupation_str = "";
                }
                $sheet->setCellValue('G'.$row, $value->occupation.$occupation_str);
                $sheet->setCellValue('H'.$row, $value->address);
                $value->provinces_name = str_replace("\n", "", $value->provinces_name);
                $value->provinces_name = str_replace("\r", "", $value->provinces_name);
                $sheet->setCellValue('I'.$row, !empty($value->provinces_name) ? ucwords(strtolower($value->provinces_name)) : "");
                $value->regions_name = str_replace("\n", "", $value->regions_name);
                $value->regions_name = str_replace("\r", "", $value->regions_name);
                $sheet->setCellValue('J'.$row, !empty($value->regions_name) ? ucwords(strtolower($value->regions_name)) : "");
                $sheet->setCellValue('K'.$row, $value->postal_code);
                $sheet->setCellValue('L'.$row, " ".str_replace(';',', ',$value->previous_participations));
                $sheet->setCellValue('M'.$row, $value->facebook);
                $sheet->setCellValue('N'.$row, $value->instagram);
                $sheet->setCellValue('O'.$row, $value->linkedin);
                $sheet->setCellValue('P'.$row, !empty($value->roles) ? ucwords(strtolower($value->roles)) : "");
                $sheet->setCellValue('Q'.$row, !empty($value->account_verified_date) ? "Yes" : "No");
                $sheet->setCellValue('R'.$row, $value->is_blocked == 1 ? "Yes" : "No");
                $sheet->setCellValue('S'.$row, $value->iwf_code);
                $sheet->setCellValue('T'.$row, (!empty($value->registered_date) ? substr($value->registered_date, 0, -3) : ""));
                $no++;
                $row++;
            }
        }

        $sheet->getStyle('A'.$row_data.':T'.($row-1))->applyFromArray($styleBorder);

        $writer = new Xlsx($spreadsheet);
        $date_now = date('YmdHis');
        $writer->save('Event_User_'.$user_id.'.xlsx');

        return redirect('Event_User_'.$user_id.'.xlsx');

    }

    public function allEventUserExport(Request $request){

        $user_id = auth()->user()->id;

        @unlink(redirect('All_Event_User_'.$user_id.'.xlsx'));

        $styleBorder = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $styleHeader = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'FFA0A0A0',
                ],
                'endColor' => [
                    'argb' => 'FFFFFFFF',
                ],
            ],'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->createSheet();

        // sheet 1
        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Data Acara');

        // style auto width column
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('O')->setAutoSize(true);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('R')->setAutoSize(true);
        $sheet->getColumnDimension('S')->setAutoSize(true);
        $sheet->getColumnDimension('T')->setAutoSize(true);
        $sheet->getColumnDimension('U')->setAutoSize(true);

        $row = 1;
        $sheet->setCellValue('A'.$row, 'No');
        $sheet->setCellValue('B'.$row, 'Id Member');
        $sheet->setCellValue('C'.$row, 'Nama');
        $sheet->setCellValue('D'.$row, 'Email');
        $sheet->setCellValue('E'.$row, 'Nomer Telepon');
        $sheet->setCellValue('F'.$row, 'Tanggal Lahir');
        $sheet->setCellValue('G'.$row, 'Pekerjaan Utama');
        $sheet->setCellValue('H'.$row, 'Alamat');
        $sheet->setCellValue('I'.$row, 'Provinsi');
        $sheet->setCellValue('J'.$row, 'Kota / Kabupaten');
        $sheet->setCellValue('K'.$row, 'Kode Pos');
        $sheet->setCellValue('L'.$row, 'Kegiatan IWF');
        $sheet->setCellValue('M'.$row, 'Facebook');
        $sheet->setCellValue('N'.$row, 'Instagram');
        $sheet->setCellValue('O'.$row, 'Linkedin');
        $sheet->setCellValue('P'.$row, 'Peran');
        $sheet->setCellValue('Q'.$row, 'Diverifikasi?');
        $sheet->setCellValue('R'.$row, 'Diblok?');
        $sheet->setCellValue('S'.$row, 'Kode Tiket');
        $sheet->setCellValue('T'.$row, 'Tanggal Pemesanan');
        $sheet->setCellValue('U'.$row, 'Acara');
        $sheet->getStyle('A'.$row.':U'.$row)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A'.$row.':U'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row.':U'.$row)->applyFromArray($styleHeader);

        $data = User::select('users.*', 'provinces.name AS provinces_name', 'regions.name AS regions_name', 'em.*', 'e.name AS event_name')
        ->leftJoin('provinces', 'provinces.id', '=', 'users.province_id')
        ->leftJoin('regions', 'regions.id', '=', 'users.region_id')
        ->join('event_member AS em', 'em.user_id', 'users.id')
        ->leftJoin('events AS e', 'e.id', 'em.event_id')
        ->where('em.is_cancel', 0)
        ->get();

        $row++;
        $row_data = $row;
        $no = 1;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $sheet->setCellValue('A'.$row, $no)->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('B'.$row, $value->number);
                $sheet->setCellValue('C'.$row, $value->full_name);
                $sheet->setCellValue('D'.$row, $value->email);
                $sheet->setCellValue('E'.$row, $value->phone_number);
                $sheet->setCellValue('F'.$row, $value->birthday);
                if(!empty($value->occupation_company_name) && !empty($value->occupation_company_detail)){
                    $occupation_str = " (".$value->occupation_company_name.", ".$value->occupation_company_detail.")";
                }else{
                    $occupation_str = "";
                }
                $sheet->setCellValue('G'.$row, $value->occupation.$occupation_str);
                $sheet->setCellValue('H'.$row, $value->address);
                $value->provinces_name = str_replace("\n", "", $value->provinces_name);
                $value->provinces_name = str_replace("\r", "", $value->provinces_name);
                $sheet->setCellValue('I'.$row, !empty($value->provinces_name) ? ucwords(strtolower($value->provinces_name)) : "");
                $value->regions_name = str_replace("\n", "", $value->regions_name);
                $value->regions_name = str_replace("\r", "", $value->regions_name);
                $sheet->setCellValue('J'.$row, !empty($value->regions_name) ? ucwords(strtolower($value->regions_name)) : "");
                $sheet->setCellValue('K'.$row, $value->postal_code);
                $sheet->setCellValue('L'.$row, " ".str_replace(';',', ',$value->previous_participations));
                $sheet->setCellValue('M'.$row, $value->facebook);
                $sheet->setCellValue('N'.$row, $value->instagram);
                $sheet->setCellValue('O'.$row, $value->linkedin);
                $sheet->setCellValue('P'.$row, !empty($value->roles) ? ucwords(strtolower($value->roles)) : "");
                $sheet->setCellValue('Q'.$row, !empty($value->account_verified_date) ? "Yes" : "No");
                $sheet->setCellValue('R'.$row, $value->is_blocked == 1 ? "Yes" : "No");
                $sheet->setCellValue('S'.$row, $value->iwf_code);
                $sheet->setCellValue('T'.$row, (!empty($value->registered_date) ? substr($value->registered_date, 0, -3) : ""));
                $sheet->setCellValue('U'.$row, $value->event_name);
                $no++;
                $row++;
            }
        }

        $sheet->getStyle('A'.$row_data.':U'.($row-1))->applyFromArray($styleBorder);

        $writer = new Xlsx($spreadsheet);
        $date_now = date('YmdHis');
        $writer->save('All_Event_User_'.$user_id.'.xlsx');

        return redirect('All_Event_User_'.$user_id.'.xlsx');

    }
}
