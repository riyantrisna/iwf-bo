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
            1 => 'name',
            2 => 'category',
            3 => 'startdate',
            4 => 'enddate',
            5 => 'quota',
            6 => 'is_active'
        );

        $order = $columns[$request->order[0]['column']];
        $dir = $request->order[0]['dir'];

		$list = Event::select(
            'id',
            'name',
            'category',
            'startdate',
            'enddate',
            'quota',
            'is_active'
        );
        if(!empty($keyword)){
            $keyword = '%'.$keyword .'%';
            $query = $list->where(function($q) use($keyword) {
                $q->where('name', 'LIKE', $keyword)
                ->orWhere('category', 'LIKE', $keyword)
                ->orWhere('startdate', 'LIKE', $keyword)
                ->orWhere('enddate', 'LIKE', $keyword)
                ->orWhere('quota', 'LIKE', $keyword)
                ->orWhere('is_active', 'LIKE', ($keyword == "%Yes%" ? "%1%" : ($keyword == "%No%" ? "%0%" : "")))
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
                $row[] = $value->name;
                $row[] = !empty($value->category) ? ucwords(strtolower($value->category)) : "";
                $row[] = !empty($value->startdate) ? date("d-m-Y H:i:s", strtotime($value->startdate)) : "";
                $row[] = !empty($value->enddate) ? date("d-m-Y H:i:s", strtotime($value->enddate)) : "";
                $row[] = $value->quota;
                $row[] = $value->is_active == 1 ? "Yes" : "No";

                //add html for action
                $row[] = '<a class="btn btn-sm btn-info" href="javascript:void(0)" title="Detail" onclick="detail(\''.$value->id.'\')"><i class="fas fa-search"></i></a>
                        <a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit('."'".$value->id."'".')"><i class="fas fa-edit"></i></a>
                        <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Delete" onclick="deletes('."'".$value->id."','".$value->name."'".')"><i class="fas fa-trash-alt"></i></a>';

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

        $category = explode("/", "CONFERENCE/WORKSHOP/EVENT/QA/SEMINAR/OTHER");
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
                    <label for="file_image">Banner *</label>
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

        if(empty($request->location)){
            $validation = $validation && false;
            $validation_text.= '<li>Lokasi dibutuhkan</li>';
        }

        if(empty($request->quota)){
            $validation = $validation && false;
            $validation_text.= '<li>Kuota dibutuhkan</li>';
        }

        if(empty($request->file_image_value)){
            $validation = $validation && false;
            $validation_text.= '<li>Banner dibutuhkan</li>';
        }


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
                $event->location = $request->location;
                $event->quota = $request->quota;
                $event->prices = !empty($request->prices) ? str_replace(".","",$request->prices) : NULL;
                $event->sponsors = !empty($request->sponsor) ? implode(",", $request->sponsor) : NULL;
                $event->shortlink = str_replace(" ", "_",strtolower($request->name));
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

        $category = explode("/", "CONFERENCE/WORKSHOP/EVENT/QA/SEMINAR/OTHER");
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
                    <label for="file_image">Banner *</label>
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

        if(empty($request->location)){
            $validation = $validation && false;
            $validation_text.= '<li>Lokasi dibutuhkan</li>';
        }

        if(empty($request->quota)){
            $validation = $validation && false;
            $validation_text.= '<li>Kuota dibutuhkan</li>';
        }

        if(empty($request->file_image_value)){
            $validation = $validation && false;
            $validation_text.= '<li>Banner dibutuhkan</li>';
        }


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
                $event->location = $request->location;
                $event->quota = $request->quota;
                $event->prices = !empty($request->prices) ? str_replace(".","",$request->prices) : NULL;
                $event->sponsors = !empty($request->sponsor) ? implode(",", $request->sponsor) : NULL;
                $event->shortlink = str_replace(" ", "_",strtolower($request->name));
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
        if(!empty($detail->sponsors)){
            $speaker_data = EventSpeaker::selectRaw('GROUP_CONCAT(s.full_name) AS name')
                            ->leftJoin('speakers AS s', 's.id', 'event_speaker.speaker_id')
                            ->where('event_id', $id)
                            ->first();
            $data_speaker_text = !empty($speaker_data->name) ? str_replace(",",", ",$speaker_data->name) : "";
        }

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<div id="name" class="detail-value">'.$detail->name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="category">Kategori *</label>';
        $html.=     '<div id="category" class="detail-value">'.(ucwords(strtolower($detail->category))).'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="period_code">Periode *</label>';
        $html.=     '<div id="period_code" class="detail-value">'.$detail->period_code.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="event_group">Kelompok *</label>';
        $html.=     '<div id="event_group" class="detail-value">'.$detail->group_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="mode">Mode *</label>';
        $html.=     '<div id="mode" class="detail-value">'.(ucwords(strtolower($detail->mode))).'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="highlight">Highlight *</label>';
        $html.=     '<div id="highlight" class="detail-value">'.$detail->highlight.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="description">Deskipsi *</label>';
        $html.=     '<div id="description" class="detail-value">'.$detail->description.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="is_active">Status *</label>';
        $html.=     '<div id="is_active" class="detail-value">'.($detail->is_active == "1" ? "Aktif" : "Tidak Aktif").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="startdate">Tanggal Mulai *</label>';
        $html.=     '<div id="startdate" class="detail-value">'.(!empty($detail->startdate) ? substr($detail->startdate, 0, -3) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="enddate">Tanggal Selesai *</label>';
        $html.=     '<div id="enddate" class="detail-value">'.(!empty($detail->enddate) ? substr($detail->enddate, 0, -3) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="location">Lokasi *</label>';
        $html.=     '<div id="location" class="detail-value">'.$detail->location.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="quota">Kuota *</label>';
        $html.=     '<div id="quota" class="detail-value">'.(!empty($detail->quota) ? number_format($detail->quota, 0, ",", ".") : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="prices">Harga <i>(*kosongkan jika gratis)</i></label>';
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

    public function userExport(Request $request){

        $user_id = auth()->user()->id;

        @unlink(redirect('User_'.$user_id.'.xlsx'));

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
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Data User');

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

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Nama');
        $sheet->setCellValue('C1', 'Email');
        $sheet->setCellValue('D1', 'Nomer Telepon');
        $sheet->setCellValue('E1', 'Tanggal Lahir');
        $sheet->setCellValue('F1', 'Pekerjaan Utama');
        $sheet->setCellValue('G1', 'Alamat');
        $sheet->setCellValue('H1', 'Provinsi');
        $sheet->setCellValue('I1', 'Kota / Kabupaten');
        $sheet->setCellValue('J1', 'Kode Pos');
        $sheet->setCellValue('K1', 'Kegiatan IWF');
        $sheet->setCellValue('L1', 'Facebook');
        $sheet->setCellValue('M1', 'Instagram');
        $sheet->setCellValue('N1', 'Linkedin');
        $sheet->setCellValue('O1', 'Peran');
        $sheet->setCellValue('P1', 'Diverifikasi?');
        $sheet->setCellValue('Q1', 'Diblok?');
        $sheet->getStyle('A1:Q1')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
        $sheet->getStyle('A1:Q1')->applyFromArray($styleHeader);

        $data = User::select('users.*', 'provinces.name AS provinces_name', 'regions.name AS regions_name')
        ->leftJoin('provinces', 'provinces.id', '=', 'users.province_id')
        ->leftJoin('regions', 'regions.id', '=', 'users.region_id')
        ->get();

        $row = $row_data = 2;
        $no = 1;
        if(!empty($data)){
            foreach ($data as $key => $value) {
                $sheet->setCellValue('A'.$row, $no)->getStyle('A'.$row)->getAlignment()->setHorizontal('center');
                $sheet->setCellValue('B'.$row, $value->full_name);
                $sheet->setCellValue('C'.$row, $value->email);
                $sheet->setCellValue('D'.$row, $value->phone_number);
                $sheet->setCellValue('E'.$row, $value->birthday);
                if(!empty($value->occupation_company_name) && !empty($value->occupation_company_detail)){
                    $occupation_str = " (".$value->occupation_company_name.", ".$value->occupation_company_detail.")";
                }else{
                    $occupation_str = "";
                }
                $sheet->setCellValue('F'.$row, $value->occupation.$occupation_str);
                $sheet->setCellValue('G'.$row, $value->address);
                $value->provinces_name = str_replace("\n", "", $value->provinces_name);
                $value->provinces_name = str_replace("\r", "", $value->provinces_name);
                $sheet->setCellValue('H'.$row, !empty($value->provinces_name) ? ucwords(strtolower($value->provinces_name)) : "");
                $value->regions_name = str_replace("\n", "", $value->regions_name);
                $value->regions_name = str_replace("\r", "", $value->regions_name);
                $sheet->setCellValue('I'.$row, !empty($value->regions_name) ? ucwords(strtolower($value->regions_name)) : "");
                $sheet->setCellValue('J'.$row, $value->postal_code);
                $sheet->setCellValue('K'.$row, " ".str_replace(';',', ',$value->previous_participations));
                $sheet->setCellValue('L'.$row, $value->facebook);
                $sheet->setCellValue('M'.$row, $value->instagram);
                $sheet->setCellValue('N'.$row, $value->linkedin);
                $sheet->setCellValue('O'.$row, !empty($value->roles) ? ucwords(strtolower($value->roles)) : "");
                $sheet->setCellValue('P'.$row, !empty($value->account_verified_date) ? "Yes" : "No");
                $sheet->setCellValue('Q'.$row, $value->is_blocked == 1 ? "Yes" : "No");
                $no++;
                $row++;
            }
        }

        $sheet->getStyle('A'.$row_data.':Q'.($row-1))->applyFromArray($styleBorder);

        $writer = new Xlsx($spreadsheet);
        $date_now = date('YmdHis');
        $writer->save('User_'.$user_id.'.xlsx');

        return redirect('User_'.$user_id.'.xlsx');

    }
}
