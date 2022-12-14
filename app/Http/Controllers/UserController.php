<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use App\Models\Provinces;
use App\Models\Regions;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    public function index()
    {
        $data['title'] = "User";
        return view('user.index', $data);
    }

    public function data(Request $request)
	{
        $keyword = $request->search['value'];
        $start = $request->post('start');
        $length = $request->post('length');

        $columns = array(
            1 => 'full_name',
            2 => 'email',
            3 => 'phone_number',
            4 => 'roles',
            5 => 'is_blocked'
        );

        $order = $columns[$request->order[0]['column']];
        $dir = $request->order[0]['dir'];

		$list = User::select(
            'id',
            'full_name',
            'email',
            'phone_number',
            'roles',
            'is_blocked'
        );
        if(!empty($keyword)){
            $keyword = '%'.$keyword .'%';
            $query = $list->where(function($q) use($keyword) {
                $q->where('full_name', 'LIKE', $keyword)
                ->orWhere('email', 'LIKE', $keyword)
                ->orWhere('phone_number', 'LIKE', $keyword)
                ->orWhere('roles', 'LIKE', $keyword)
                ->orWhere('is_blocked', 'LIKE', ($keyword == "%Yes%" ? "%1%" : ($keyword == "%No%" ? "%0%" : "")))
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
                $row[] = '<a class="btn btn-sm btn-info" href="javascript:void(0)" title="Detail" onclick="detail(\''.$value->id.'\')"><i class="fas fa-search"></i></a>
                        <a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit('."'".$value->id."'".')"><i class="fas fa-edit"></i></a>
                        <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Delete" onclick="deletes('."'".$value->id."','".$value->full_name."'".')"><i class="fas fa-trash-alt"></i></a>';
                $row[] = $value->full_name;
                $row[] = $value->email;
                $row[] = $value->phone_number;
                $row[] = $value->roles;
                $row[] = $value->is_blocked == 1 ? "Yes" : "No";

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

    public function getOccupationType(Request $request, $key){
        $data = array();
        $data["occupation_type_lable"] = array();
        $setting = Setting::where('setting_key', $key)->first();
        $occupation_type = !empty($setting) ? $setting->setting_value : "";

        $setting = Setting::where('setting_key', 'occupation_data_lable')->first();
        $occupation_data_lable = $setting->setting_value;

        if($occupation_type){
            $occupation_type = explode(";",$occupation_type);

            $occupation_data_lable = explode(";",$occupation_data_lable);

            if($occupation_data_lable){

                foreach ($occupation_data_lable as $k => $value) {

                    $lable = explode("|", $value);
                    $key_check = ucwords(str_replace("_"," ", $key));
                    if($lable[0] == $key_check){
                        $data["occupation_type_lable"] = array(
                            $lable[1],
                            $lable[2]
                        );
                    }
                }
            }
            $data["result"] = $occupation_type;
        }else{
            $data["occupation_type_lable"] = array(
                "",
                ""
            );
            $data["result"] = "";
        }

        return response()->json($data, 200);
    }

    public function addView(Request $request){

        $setting = Setting::where('setting_key', 'occupation_data')->first();
        $occupation = explode(';', $setting->setting_value);

        $setting = Setting::where('setting_key', 'iwf_events')->first();
        $iwf_events = explode(';', $setting->setting_value);
        array_pop($iwf_events);
        array_push($iwf_events, "Belum Pernah");

        $provinces = Provinces::get();
        $regions = Regions::get();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="email">Email *</label>';
        $html.=     '<input type="email" id="email" name="email" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="phone">Nomer Telepon *</label>';
        $html.=     '<input type="number" id="phone" name="phone" class="form-control" onkeypress="return isNumber(event)">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="birthday">Tanggal Lahir *</label>';
        $html.=     '<input type="text" id="birthday" name="birthday" class="form-control" placeholder="yyyy-mm-dd">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="occupation">Pekerjaan Utama *</label>';
        $html.=     '<select id="occupation" name="occupation" class="form-control" onchange="show_occupation()">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($occupation)){
            foreach ($occupation as $key => $value) {
        $html.=         '<option value="'.$value.'">';
        $html.=             $value;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group" id="occupation_type_title_cover">';
        $html.= '</div>';
        $html.= '<div class="form-group" id="occupation_type_cover">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="address">Alamat *</label>';
        $html.=     '<textarea id="address" name="address" class="form-control"></textarea>';
        $html.= '</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="provinces">Provinsi *</label>';
        $html.=     '<select id="provinces" name="provinces" class="form-control" onchange="relod_regions(\'provinces\')">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($provinces)){
            foreach ($provinces as $key => $value) {
        $html.=         '<option value="'.$value->id.'">';
        $html.=             $value->name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="regions">Kota / Kabupaten *</label>';
        $html.=     '<select id="regions" name="regions" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="postal_code">Kode Pos</label>';
        $html.=     '<input type="number" id="postal_code" name="postal_code" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="participated">Have you ever participated in IWF?</label>';
        if(!empty($iwf_events)){
            foreach ($iwf_events as $key => $value) {
        $html.=     '<div class="form-check">
                        <input class="form-check-input checkboxes" type="checkbox" name="participated[]" id="participated'.$key.'" value="'.$value.'" '.($value == "Belum Pernah" ? 'onclick="hide_other('.$key.')"' : '').'>
                        <label class="form-check-label checkboxes" for="participated'.$key.'">';
        $html.=             $value;
        $html.=         '</label>
                    </div>';
            }
        }
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="facebook">Facebook</label>';
        $html.=     '<input type="facebook" id="facebook" name="facebook" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="instagram">Instagram</label>';
        $html.=     '<input type="instagram" id="instagram" name="instagram" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="linkedin">Linkedin</label>';
        $html.=     '<input type="linkedin" id="linkedin" name="linkedin" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="password">Password *</label>';
        $html.=     '<input type="password" id="password" name="password" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="retype_password">Ualngi Password *</label>';
        $html.=     '<input type="password" id="retype_password" name="retype_password" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="roles">Peran *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="roles" id="roles1" value="ADMIN" checked>
                        <label class="form-check-label" for="roles1">
                            Admin
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="roles" id="roles2" value="MODERATOR">
                        <label class="form-check-label" for="roles2">
                            Moderator
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="roles" id="roles3" value="GUEST">
                        <label class="form-check-label" for="roles3">
                            Guest
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="verified">Diverifikasi? *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="verified" id="verified1" value="1" checked>
                        <label class="form-check-label" for="verified1">
                            Yes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="verified" id="verified2" value="0">
                        <label class="form-check-label" for="verified2">
                            No
                        </label>
                    </div>';
        $html.= '</div>';
        $html.=     '<label for="blocked">Diblok? *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="blocked" id="blocked1" value="1" checked>
                        <label class="form-check-label" for="blocked1">
                            Yes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="blocked" id="blocked2" value="0">
                        <label class="form-check-label" for="blocked2">
                            No
                        </label>
                    </div>';
        $html.= '</div>';

        $data['html'] = $html;
        $data['iwf_events'] = count($iwf_events) - 1;

        return response()->json($data, 200);
    }

    public function add(Request $request){

        $last_data = User::select('id', 'number')->orderBy('id', 'desc')->first();
        if(!empty($last_data)){
            $year = substr($last_data->number,3,4);
            $seq = substr($last_data->number,7,6);
            $seq = (int)$seq + 1;
            if((int)date("Y") > (int)$year){
                $year = date("Y");
                $seq = 1;
            }
            $number = "IWF".$year.sprintf("%'.06d", $seq);
        }else{
            $number = "IWF".date('Y')."000001";
        }
        $name = $request->name;
        $email = $request->email;
        $phone = $request->phone;
        $birthday = $request->birthday;
        $occupation = $request->occupation;
        $occupation_type_title = $request->has('occupation_type_title') ? $request->occupation_type_title : NULL;
        $occupation_type = $request->has('occupation_type') ? $request->occupation_type : NULL;
        $address = $request->address;
        $provinces = $request->provinces;
        $regions = $request->regions;
        $postal_code = $request->postal_code;
        $participated = $request->participated;
        $facebook = $request->facebook;
        $instagram = $request->instagram;
        $linkedin = $request->linkedin;
        $password = $request->password;
        $retype_password = $request->retype_password;
        $roles = $request->roles;
        $verified = $request->verified;
        $blocked = $request->blocked;

        $date = date('Y-m-d H:i:s');
        $user_id = auth()->user()->id;

        $validation = true;
        $validation_text = '';

        if(empty($name)){
            $validation = $validation && false;
            $validation_text.= '<li>Nama dibutuhkan</li>';
        }else{
            $check_user = User::select('id')->where('full_name', $name)->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nama <b>'.$name.'</b> telah digunakan</li>';
            }
        }

        if(empty($email)){
            $validation = $validation && false;
            $validation_text.= '<li>Email dibutuhkan</li>';
        }else{
            $check_user = User::select('id')->where('email', $email)->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Email <b>'.$email.'</b> telah digunakan</li>';
            }
        }

        if(empty($phone)){
            $validation = $validation && false;
            $validation_text.= '<li>Nomer Telpon dibutuhkan</li>';
        }else{
            $check_user = User::select('id')->where('phone_number', $phone)->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nomer Telepon <b>'.$phone.'</b> telah digunakan</li>';
            }
        }

        if(empty($birthday)){
            $validation = $validation && false;
            $validation_text.= '<li>Tanggal Lahir dibutuhkan</li>';
        }else{
            $check_user = User::select('id')
            ->where('full_name', $name)
            ->where('email', $email)
            ->where('phone_number', $phone)
            ->where('birthday', $birthday)->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Tanggal Lahir <b>'.$birthday.'</b> telah digunakan</li>';
            }
        }

        if(!isset($occupation) AND $occupation == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Pekerjaan Utama dibutuhkan</li>';
        }

        if(!isset($address) AND $address == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Alamat dibutuhkan</li>';
        }

        if(!isset($provinces) AND $provinces == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Provinsi dibutuhkan</li>';
        }

        if(!isset($regions) AND $regions == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Kota / Kabupaten dibutuhkan</li>';
        }

        if(empty($password)){
            $validation = $validation && false;
            $validation_text.= '<li>Password dibutuhkan</li>';
        }

        if(empty($retype_password)){
            $validation = $validation && false;
            $validation_text.= '<li>Ulangi Password dibutuhkan</li>';
        }

        if(!empty($password) AND !empty($retype_password) AND $password !== $retype_password){
			$validation = $validation && false;
			$validation_text.= '<li>Password and Ualngi Password tidak sama</li>';
		}

        if(empty($roles) AND $roles == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Peran dibutuhkan</li>';
        }

        if(!isset($verified) AND $verified == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Diverifikasi dibutuhkan</li>';
        }

        if(!isset($blocked) AND $blocked == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Diblok dibutuhkan</li>';
        }

        if($validation){
            $results = true;

            $user = new User;

            if(!empty($participated)){
                $participated_str = implode(";", $participated);
            }else{
                $participated_str = "";
            }

            $user->number = $number;
            $user->full_name = $name;
            $user->username = $email;
            $user->email = $email;
            $user->birthday = $birthday;
            $user->phone_number = $phone;
            $user->occupation = $occupation;
            $user->occupation_company_name = $occupation_type_title;
            $user->occupation_company_detail = $occupation_type;
            $user->address = $address;
            $user->province_id = $provinces;
            $user->region_id = $regions;
            $user->postal_code = $postal_code;
            $user->previous_participations = $participated_str;
            $user->facebook = $request->facebook;
            $user->instagram = $request->instagram;
            $user->linkedin = $request->linkedin;
            $user->password = Hash::make($password);
            $user->account_verified_date = $verified == 1 ? $date : NULL;
            $user->is_blocked = $blocked;
            $user->roles = $roles;
            $user->last_login = NULL;
            $user->created_by = $user_id;
            $user->created_at = $date;
            $response = $user->save();

            if ($response) {
                $result["status"] = TRUE;
                $result["message"] = 'Sukses menambahkan data';
            } else {
                $result["status"] = FALSE;
                $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                $result["message"].= '<li>Failed added data</li>';
                $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>';
                $result["message"].= '</div>';
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

        $detail = User::where('id', $id)->first();

        $setting = Setting::where('setting_key', 'occupation_data')->first();
        $occupation = explode(';', $setting->setting_value);

        $setting = Setting::where('setting_key', 'iwf_events')->first();
        $iwf_events = explode(';', $setting->setting_value);
        array_pop($iwf_events);
        array_push($iwf_events, "Belum Pernah");

        $provinces = Provinces::get();
        $regions = Regions::get();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control" value="'.$detail->full_name.'">';
        $html.=     '<input type="hidden" id="id" name="id" value="'.$detail->id.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="email">Email *</label>';
        $html.=     '<input type="email" id="email" name="email" class="form-control" value="'.$detail->email.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="phone">Nomer Telepon *</label>';
        $html.=     '<input type="number" id="phone" name="phone" class="form-control" onkeypress="return isNumber(event)" value="'.$detail->phone_number.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="birthday">Tanggal Lahir *</label>';
        $html.=     '<input type="text" id="birthday" name="birthday" class="form-control" placeholder="yyyy-mm-dd"  value="'.$detail->birthday.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="occupation">Pekerjaan Utama *</label>';
        $html.=     '<select id="occupation" name="occupation" class="form-control" onchange="show_occupation_edit_val(\''.$detail->occupation_company_name.'\', \''.$detail->occupation_company_detail.'\')">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($occupation)){
            foreach ($occupation as $key => $value) {
        $html.=         '<option value="'.$value.'" '.($detail->occupation == $value ? 'selected' : '').'>';
        $html.=             $value;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group" id="occupation_type_title_cover">';
        $html.= '</div>';
        $html.= '<div class="form-group" id="occupation_type_cover">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="address">Alamat *</label>';
        $html.=     '<textarea id="address" name="address" class="form-control">'.$detail->address.'</textarea>';
        $html.= '</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="provinces">Provinsi *</label>';
        $html.=     '<select id="provinces" name="provinces" class="form-control" onchange="relod_regions(\'provinces\')">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        if(!empty($provinces)){
            foreach ($provinces as $key => $value) {
        $html.=         '<option value="'.$value->id.'" '.($detail->province_id == $value->id ? 'selected' : '').'>';
        $html.=             $value->name;
        $html.=         '</option>';
            }
        }
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="regions">Kota / Kabupaten *</label>';
        $html.=     '<select id="regions" name="regions" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Pilih --';
        $html.=         '</option>';
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="postal_code">Kode Pos</label>';
        $html.=     '<input type="number" id="postal_code" name="postal_code" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="participated">Apakah Anda pernah mengikuti kegiatan IWF sebelumnya?</label>';
        if(!empty($iwf_events)){
            foreach ($iwf_events as $key => $value) {
        $html.=     '<div class="form-check">
                        <input class="form-check-input checkboxes" type="checkbox" name="participated[]" id="participated'.$key.'" value="'.$value.'" '.(in_array($value, explode(';', $detail->previous_participations)) ? 'checked' : '').' '.($value == "Belum Pernah" ? 'onclick="hide_other('.$key.')"' : '').'>
                        <label class="form-check-label checkboxes" for="participated'.$key.'">';
        $html.=             $value;
        $html.=         '</label>
                    </div>';
            }
        }
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="facebook">Facebook</label>';
        $html.=     '<input type="facebook" id="facebook" name="facebook" class="form-control" value="'.$detail->facebook.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="instagram">Instagram</label>';
        $html.=     '<input type="instagram" id="instagram" name="instagram" class="form-control" value="'.$detail->instagram.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="linkedin">Linkedin</label>';
        $html.=     '<input type="linkedin" id="linkedin" name="linkedin" class="form-control" value="'.$detail->linkedin.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="password">Password *</label>';
        $html.=     '<input type="password" id="password" name="password" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="retype_password">Ualngi Password *</label>';
        $html.=     '<input type="password" id="retype_password" name="retype_password" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="roles">Peran *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="roles" id="roles1" value="ADMIN" '.($detail->roles == "ADMIN" ? "checked" : "").'>
                        <label class="form-check-label" for="roles1">
                            Admin
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="roles" id="roles2" value="MODERATOR" '.($detail->roles == "MODERATOR" ? "checked" : "").'>
                        <label class="form-check-label" for="roles2">
                            Moderator
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="roles" id="roles3" value="GUEST" '.($detail->roles == "GUEST" ? "checked" : "").'>
                        <label class="form-check-label" for="roles3">
                            Guest
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="verified">Diverifikasi? *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="verified" id="verified1" value="1" '.(!empty($detail->account_verified_date) ? "checked" : "").'>
                        <label class="form-check-label" for="verified1">
                            Yes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="verified" id="verified2" value="0" '.(empty($detail->account_verified_date) ? "checked" : "").'>
                        <label class="form-check-label" for="verified2">
                            No
                        </label>
                    </div>';
        $html.= '</div>';
        $html.=     '<label for="blocked">Diblok? *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="blocked" id="blocked1" value="1" '.($detail->is_blocked == 1 ? "checked" : "").'>
                        <label class="form-check-label" for="blocked1">
                            Yes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="blocked" id="blocked2" value="0" '.($detail->is_blocked == 0 ? "checked" : "").'>
                        <label class="form-check-label" for="blocked2">
                            No
                        </label>
                    </div>';
        $html.= '</div>';

        $data['html'] = $html;
        $data['iwf_events'] = count($iwf_events) - 1;
        $data['detail'] = $detail;

        return response()->json($data, 200);
    }

    public function edit(Request $request){

        $id = $request->id;
        $name = $request->name;
        $email = $request->email;
        $phone = $request->phone;
        $birthday = $request->birthday;
        $occupation = $request->occupation;
        $occupation_type_title = $request->has('occupation_type_title') ? $request->occupation_type_title : NULL;
        $occupation_type = $request->has('occupation_type') ? $request->occupation_type : NULL;
        $address = $request->address;
        $provinces = $request->provinces;
        $regions = $request->regions;
        $postal_code = $request->postal_code;
        $participated = $request->participated;
        $facebook = $request->facebook;
        $instagram = $request->instagram;
        $linkedin = $request->linkedin;
        $password = $request->password;
        $retype_password = $request->retype_password;
        $roles = $request->roles;
        $verified = $request->verified;
        $blocked = $request->blocked;

        $date = date('Y-m-d H:i:s');
        $user_id = auth()->user()->id;

        $validation = true;
        $validation_text = '';

        if(empty($name)){
            $validation = $validation && false;
            $validation_text.= '<li>Nama dibutuhkan</li>';
        }else{
            $check_user = User::select('id')->where('full_name', $name)->whereNotIn('id', [$id])->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nama <b>'.$name.'</b> telah digunakan</li>';
            }
        }

        if(empty($email)){
            $validation = $validation && false;
            $validation_text.= '<li>Email dibutuhkan</li>';
        }else{
            $check_user = User::select('id')->where('email', $email)->whereNotIn('id', [$id])->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Email <b>'.$email.'</b> telah digunakan</li>';
            }
        }

        if(empty($phone)){
            $validation = $validation && false;
            $validation_text.= '<li>Nomer Telpon dibutuhkan</li>';
        }else{
            $check_user = User::select('id')->where('phone_number', $phone)->whereNotIn('id', [$id])->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nomer Telepon <b>'.$phone.'</b> telah digunakan</li>';
            }
        }

        if(empty($birthday)){
            $validation = $validation && false;
            $validation_text.= '<li>Tanggal Lahir dibutuhkan</li>';
        }else{
            $check_user = User::select('id')
            ->where('full_name', $name)
            ->where('email', $email)
            ->where('phone_number', $phone)
            ->where('birthday', $birthday)
            ->whereNotIn('id', [$id])->first();
            if(!empty($check_user->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Tanggal Lahir <b>'.$birthday.'</b> telah digunakan</li>';
            }
        }

        if(!isset($occupation) AND $occupation == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Pekerjaan Utama dibutuhkan</li>';
        }

        if(!isset($address) AND $address == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Alamat dibutuhkan</li>';
        }

        if(!isset($provinces) AND $provinces == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Provinsi dibutuhkan</li>';
        }

        if(!isset($regions) AND $regions == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Kota / Kabupaten dibutuhkan</li>';
        }

        if(!empty($password) AND !empty($retype_password) AND $password !== $retype_password){
			$validation = $validation && false;
			$validation_text.= '<li>Password and Ualngi Password tidak sama</li>';
		}

        if(empty($roles) AND $roles == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Peran dibutuhkan</li>';
        }

        if(!isset($verified) AND $verified == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Diverifikasi dibutuhkan</li>';
        }

        if(!isset($blocked) AND $blocked == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Diblok dibutuhkan</li>';
        }

        if($validation){
            $results = true;

            $user = User::where('id', $id)->first();

            if(!empty($participated)){
                $participated_str = implode(";", $participated);
            }else{
                $participated_str = "";
            }

            $user->full_name = $name;
            $user->username = $email;
            $user->email = $email;
            $user->birthday = $birthday;
            $user->phone_number = $phone;
            $user->occupation = $occupation;
            $user->occupation_company_name = $occupation_type_title;
            $user->occupation_company_detail = $occupation_type;
            $user->address = $address;
            $user->province_id = $provinces;
            $user->region_id = $regions;
            $user->postal_code = $postal_code;
            $user->previous_participations = $participated_str;
            $user->facebook = $request->facebook;
            $user->instagram = $request->instagram;
            $user->linkedin = $request->linkedin;
            if(!empty($password) AND !empty($retype_password) AND $password !== $retype_password){
                $user->password = Hash::make($password);
            }
            $user->account_verified_date = $verified == 1 ? $date : NULL;
            $user->is_blocked = $blocked;
            $user->roles = $roles;
            $user->updated_by = $user_id;
            $user->updated_at = $date;

            $response = $user->update();

            if ($response) {
                $result["status"] = TRUE;
                $result["message"] = 'Successfully edited data';
            } else {
                $result["status"] = FALSE;
                $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                $result["message"].= '<li>Failed edited data</li>';
                $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>';
                $result["message"].= '</div>';
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

        $detail = User::select('users.*', 'provinces.name AS provinces_name', 'regions.name AS regions_name')
        ->leftJoin('provinces', 'provinces.id', '=', 'users.province_id')
        ->leftJoin('regions', 'regions.id', '=', 'users.region_id')
        ->where('users.id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama</label>';
        $html.=     '<div class="detail-value" id="name">'.$detail->full_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="email">Email</label>';
        $html.=     '<div class="detail-value" id="email">'.$detail->email.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="phone">Nomer Telepon</label>';
        $html.=     '<div class="detail-value" id="phone">'.$detail->phone_number.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="birthday">Tanggal Lahir</label>';
        $html.=     '<div class="detail-value" id="birthday">'.(!empty($detail->birthday) ? date("Y-m-d", strtotime($detail->birthday)) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="occupation">Pekerjaan Utama</label>';
        $html.=     '<div class="detail-value" id="occupation">'.$detail->occupation.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group" id="occupation_type_title_cover">';
        $html.= '</div>';
        $html.= '<div class="form-group" id="occupation_type_cover">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="address">Alamat</label>';
        $html.=     '<div class="detail-value" id="address">'.$detail->address.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="provinces">Provinsi</label>';
        $html.=     '<div class="detail-value" id="provinces">'.$detail->provinces_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="regions_name">Kota / Kabupaten</label>';
        $html.=     '<div class="detail-value" id="regions_name">'.$detail->regions_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="postal_code">Kode Pos</label>';
        $html.=     '<div class="detail-value" id="postal_code">'.$detail->postal_code.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="participated">Apakah Anda pernah mengikuti kegiatan IWF sebelumnya?</label>';
        $detail->previous_participations = str_replace(';','<br>',$detail->previous_participations);
        $html.=     '<div class="detail-value" id="participated">'.$detail->previous_participations.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="facebook">Facebook</label>';
        $html.=     '<div class="detail-value" id="facebook">'.$detail->facebook.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="instagram">Instagram</label>';
        $html.=     '<div class="detail-value" id="instagram">'.$detail->instagram.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="linkedin">Linkedin</label>';
        $html.=     '<div class="detail-value" id="linkedin">'.$detail->linkedin.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="roles">Peran</label>';
        if($detail->roles == "ADMIN"){
            $html.=     '<div class="detail-value" id="roles">Admin</div>';
        }elseif($detail->roles == "MODERATOR"){
            $html.=     '<div class="detail-value" id="roles">Moderator</div>';
        }elseif($detail->roles == "GUEST"){
            $html.=     '<div class="detail-value" id="roles">Guest</div>';
        }
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="verified">Diverifikasi?</label>';
        $html.=     '<div class="detail-value" id="verified">'.(!empty($detail->account_verified_date) ? "Yes" : "No").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="blocked">Diblok?</label>';
        $html.=     '<div class="detail-value" id="blocked">'.($detail->is_blocked == 1 ? "Yes" : "No").'</div>';
        $html.= '</div>';

        $data['html'] = $html;
        $data['detail'] = $detail;

        return response()->json($data, 200);
    }

    public function delete(Request $request, $id){

        $delete = User::where('id', $id)->delete();
        if($delete){
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
