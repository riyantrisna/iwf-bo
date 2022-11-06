<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Setting;
use App\Models\Provinces;
use App\Models\Regions;
use Illuminate\Support\Facades\Hash;

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
                $row[] = $value->full_name;
                $row[] = $value->email;
                $row[] = $value->phone_number;
                $row[] = $value->roles;
                $row[] = $value->is_blocked == 1 ? "Yes" : "No";

                //add html for action
                $row[] = '<a class="btn btn-sm btn-info" href="javascript:void(0)" title="Detail" onclick="detail(\''.$value->id.'\')"><i class="fas fa-search"></i></a>
                        <a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit('."'".$value->id."'".')"><i class="fas fa-edit"></i></a>
                        <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Delete" onclick="deletes('."'".$value->id."','".$value->full_name."'".')"><i class="fas fa-trash-alt"></i></a>';

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

    public function addView(Request $request){

        $setting = Setting::where('setting_key', 'occupation_data')->first();
        $occupation = explode(';', $setting->setting_value);

        $setting = Setting::where('setting_key', 'iwf_events')->first();
        $iwf_events = explode(';', $setting->setting_value);

        $provinces = Provinces::get();
        $regions = Regions::get();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Name *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="title">Title *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="title" id="title1" value="Bapak" checked>
                        <label class="form-check-label" for="title1">
                            Bapak
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="title" id="title2" value="Ibu">
                        <label class="form-check-label" for="title2">
                            Ibu
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="email">Email *</label>';
        $html.=     '<input type="email" id="email" name="email" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="phone">Phone *</label>';
        $html.=     '<input type="number" id="phone" name="phone" class="form-control" onkeypress="return isNumber(event)">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="wa">Whatsapp</label>';
        $html.=     '<input type="number" id="wa" name="wa" class="form-control" onkeypress="return isNumber(event)">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="gender">Gender *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="gender1" value="M" checked>
                        <label class="form-check-label" for="gender1">
                            Male
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="gender2" value="F">
                        <label class="form-check-label" for="gender2">
                            Female
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="birthday">Birthday *</label>';
        $html.=     '<input type="text" id="birthday" name="birthday" class="form-control" placeholder="yyyy-mm-dd">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="occupation">Occupation *</label>';
        $html.=     '<select id="occupation" name="occupation" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Select --';
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
        $html.= '<div class="form-group">';
        $html.=     '<label for="participated">Have you ever participated in IWF?</label>';
        if(!empty($iwf_events)){
            foreach ($iwf_events as $key => $value) {
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="checkbox" name="participated[]" id="participated'.$key.'" value="'.$value.'">
                        <label class="form-check-label" for="participated'.$key.'">';
        $html.=             $value;
        $html.=         '</label>
                    </div>';
            }
        }
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="provinces">Province</label>';
        $html.=     '<select id="provinces" name="provinces" class="form-control" onchange="relod_regions(\'provinces\')">';
        $html.=         '<option value="">';
        $html.=             '-- Select --';
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
        $html.=     '<label for="regions">Region</label>';
        $html.=     '<select id="regions" name="regions" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Select --';
        $html.=         '</option>';
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="roles">Roles *</label>';
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
        $html.=     '<label for="verified">Is Verified? *</label>';
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
        $html.=     '<label for="blocked">Is Blocked? *</label>';
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
        $html.= '<div class="form-group">';
        $html.=     '<label for="password">Password *</label>';
        $html.=     '<input type="password" id="password" name="password" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="retype_password">Retype Password *</label>';
        $html.=     '<input type="password" id="retype_password" name="retype_password" class="form-control">';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function add(Request $request){

        $name = $request->name;
        $title = $request->title;
        $email = $request->email;
        $phone = $request->phone;
        $wa = $request->wa;
        $gender = $request->gender;
        $birthday = $request->birthday;
        $occupation = $request->occupation;
        $participated = $request->participated;
        $provinces = $request->provinces;
        $regions = $request->regions;
        $roles = $request->roles;
        $verified = $request->verified;
        $blocked = $request->blocked;
        $password = $request->password;
        $retype_password = $request->retype_password;

        $date = date('Y-m-d H:i:s');
        $user_id = auth()->user()->id;

        $validation = true;
        $validation_text = '';

        if(empty($name)){
            $validation = $validation && false;
            $validation_text.= '<li>Name required</li>';
        }

        if(empty($title)){
            $validation = $validation && false;
            $validation_text.= '<li>Title required</li>';
        }

        if(empty($email)){
            $validation = $validation && false;
            $validation_text.= '<li>Email required</li>';
        }else{
            $check_email = User::select('id')->where('email', $email)->first();
            if(!empty($check_email->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Email <b>'.$email.'</b> existed</li>';
            }
        }

        if(empty($phone)){
            $validation = $validation && false;
            $validation_text.= '<li>Phone required</li>';
        }

        if(empty($gender)){
            $validation = $validation && false;
            $validation_text.= '<li>Gender required</li>';
        }

        if(empty($birthday)){
            $validation = $validation && false;
            $validation_text.= '<li>Birthday required</li>';
        }

        if(!isset($occupation) AND $occupation == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Occupation required</li>';
        }

        if(empty($roles) AND $roles == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Roles required</li>';
        }

        if(!isset($verified) AND $verified == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Is Verified required</li>';
        }

        if(!isset($blocked) AND $blocked == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Is Blocked required</li>';
        }

        if(empty($password)){
            $validation = $validation && false;
            $validation_text.= '<li>Password required</li>';
        }

        if(empty($retype_password)){
            $validation = $validation && false;
            $validation_text.= '<li>Retype Password required</li>';
        }

        if(!empty($password) AND !empty($retype_password) AND $password !== $retype_password){
			$validation = $validation && false;
			$validation_text.= '<li>Password and Retype Password not match</li>';
		}

        if($validation){
            $results = true;

            $user = new User;

            if(!empty($participated)){
                $participated_str = implode(";", $participated);
            }else{
                $participated_str = "";
            }

            $user->full_name = $name;
            $user->username = $email;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->gender = $gender;
            $user->title = $title;
            $user->birthday = $birthday;
            $user->account_verified_date = $verified == 1 ? $date : NULL;
            $user->last_login = NULL;
            $user->phone_number = $phone;
            $user->wa_number = $wa;
            $user->is_blocked = $blocked;
            $user->roles = $roles;
            $user->occupation = $occupation;
            $user->previous_participations = $participated_str;
            $user->country = NULL;
            $user->province_id = $provinces;
            $user->region_id = $regions;
            $user->created_by = $user_id;
            $user->created_at = $date;

            $response = $user->save();

            if ($response) {
                $result["status"] = TRUE;
                $result["message"] = 'Successfully added data';
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

        $provinces = Provinces::get();
        if(!empty($detail->province_id)){
            $regions = Regions::where('province_id', $detail->province_id)->get();
        }else{
            $regions = array();
        }

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Name *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control" value="'.$detail->full_name.'">';
        $html.=     '<input type="hidden" id="id" name="id" value="'.$detail->id.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="title">Title *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="title" id="title1" value="Bapak" '.($detail->title == "Bapak" ? "checked" : "").'>
                        <label class="form-check-label" for="title1">
                            Bapak
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="title" id="title2" value="Ibu" '.($detail->title == "Ibu" ? "checked" : "").'>
                        <label class="form-check-label" for="title2">
                            Ibu
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="email">Email *</label>';
        $html.=     '<input type="email" id="email" name="email" class="form-control" value="'.$detail->email.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="phone">Phone *</label>';
        $html.=     '<input type="number" id="phone" name="phone" class="form-control" onkeypress="return isNumber(event)" value="'.$detail->phone_number.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="wa">Whatsapp</label>';
        $html.=     '<input type="number" id="wa" name="wa" class="form-control" onkeypress="return isNumber(event)" value="'.$detail->wa_number.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="gender">Gender *</label>';
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="gender1" value="M" '.($detail->gender == "M" ? "checked" : "").'>
                        <label class="form-check-label" for="gender1">
                            Male
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="gender" id="gender2" value="F" '.($detail->gender == "F" ? "checked" : "").'>
                        <label class="form-check-label" for="gender2">
                            Female
                        </label>
                    </div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="birthday">Birthday *</label>';
        $html.=     '<input type="text" id="birthday" name="birthday" class="form-control" placeholder="yyyy-mm-dd" value="'.$detail->birthday.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="occupation">Occupation *</label>';
        $html.=     '<select id="occupation" name="occupation" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Select --';
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
        $html.= '<div class="form-group">';
        $html.=     '<label for="participated">Have you ever participated in IWF?</label>';
        if(!empty($iwf_events)){
            foreach ($iwf_events as $key => $value) {
        $html.=     '<div class="form-check">
                        <input class="form-check-input" type="checkbox" name="participated[]" id="participated'.$key.'" value="'.$value.'" '.(in_array($value, explode(';', $detail->previous_participations)) ? 'checked' : '').'>
                        <label class="form-check-label" for="participated'.$key.'">';
        $html.=             $value;
        $html.=         '</label>
                    </div>';
            }
        }
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="provinces">Province</label>';
        $html.=     '<select id="provinces" name="provinces" class="form-control" onchange="relod_regions(\'provinces\')">';
        $html.=         '<option value="">';
        $html.=             '-- Select --';
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
        $html.=     '<label for="regions">Region</label>';
        $html.=     '<select id="regions" name="regions" class="form-control">';
        $html.=         '<option value="">';
        $html.=             '-- Select --';
        if(!empty($regions)){
            foreach ($regions as $key => $value) {
        $html.=         '<option value="'.$value->id.'" '.($detail->region_id == $value->id ? 'selected' : '').'>';
        $html.=             $value->name;
        $html.=         '</option>';
            }
        }
        $html.=         '</option>';
        $html.=     '</select>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="roles">Roles *</label>';
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
        $html.=     '<label for="verified">Is Verified? *</label>';
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
        $html.= '<div class="form-group">';
        $html.=     '<label for="blocked">Is Blocked? *</label>';
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
        $html.= '<div class="form-group">';
        $html.=     '<label for="password">Password *</label>';
        $html.=     '<input type="password" id="password" name="password" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="retype_password">Retype Password *</label>';
        $html.=     '<input type="password" id="retype_password" name="retype_password" class="form-control">';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function edit(Request $request){

        $id = $request->id;
        $name = $request->name;
        $title = $request->title;
        $email = $request->email;
        $phone = $request->phone;
        $wa = $request->wa;
        $gender = $request->gender;
        $birthday = $request->birthday;
        $occupation = $request->occupation;
        $participated = $request->participated;
        $provinces = $request->provinces;
        $regions = $request->regions;
        $roles = $request->roles;
        $verified = $request->verified;
        $blocked = $request->blocked;
        $password = $request->password;
        $retype_password = $request->retype_password;

        $date = date('Y-m-d H:i:s');
        $user_id = auth()->user()->id;

        $validation = true;
        $validation_text = '';

        if(empty($name)){
            $validation = $validation && false;
            $validation_text.= '<li>Name required</li>';
        }

        if(empty($title)){
            $validation = $validation && false;
            $validation_text.= '<li>Title required</li>';
        }

        if(empty($email)){
            $validation = $validation && false;
            $validation_text.= '<li>Email required</li>';
        }else{
            $check_email = User::select('id')->where('email', $email)->whereNotIn('id', [$id])->first();
            if(!empty($check_email->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Email <b>'.$email.'</b> existed</li>';
            }
        }

        if(empty($phone)){
            $validation = $validation && false;
            $validation_text.= '<li>Phone required</li>';
        }

        if(empty($gender)){
            $validation = $validation && false;
            $validation_text.= '<li>Gender required</li>';
        }

        if(empty($birthday)){
            $validation = $validation && false;
            $validation_text.= '<li>Birthday required</li>';
        }

        if(!isset($occupation) AND $occupation == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Occupation required</li>';
        }

        if(empty($roles) AND $roles == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Roles required</li>';
        }

        if(!isset($verified) AND $verified == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Is Verified required</li>';
        }

        if(!isset($blocked) AND $blocked == ''){
            $validation = $validation && false;
            $validation_text.= '<li>Is Blocked required</li>';
        }

        if(!empty($password) AND !empty($retype_password) AND $password !== $retype_password){
			$validation = $validation && false;
			$validation_text.= '<li>Password and Retype Password not match</li>';
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
            if(!empty($password) AND !empty($retype_password) AND $password !== $retype_password){
                $user->password = Hash::make($password);
            }
            $user->gender = $gender;
            $user->title = $title;
            $user->birthday = $birthday;
            $user->account_verified_date = $verified == 1 ? $date : NULL;
            $user->phone_number = $phone;
            $user->wa_number = $wa;
            $user->is_blocked = $blocked;
            $user->roles = $roles;
            $user->occupation = $occupation;
            $user->previous_participations = $participated_str;
            $user->country = NULL;
            $user->province_id = $provinces;
            $user->region_id = $regions;
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
        $html.=     '<label for="name">Name</label>';
        $html.=     '<div class="detail-value" id="name">'.$detail->full_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="title">Title</label>';
        $html.=     '<div class="detail-value" id="title">'.$detail->title.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="email">Email</label>';
        $html.=     '<div class="detail-value" id="email">'.$detail->email.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="phone">Phone</label>';
        $html.=     '<div class="detail-value" id="phone">'.$detail->phone_number.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="wa">Whatsapp</label>';
        $html.=     '<div class="detail-value" id="wa">'.$detail->wa_number.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="gender">Gender</label>';
        $html.=     '<div class="detail-value" id="gender">'.($detail->gender == "F" ? "Female" : "Meal").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="birthday">Birthday</label>';
        $html.=     '<div class="detail-value" id="birthday">'.(!empty($detail->birthday) ? date("Y-m-d", strtotime($detail->birthday)) : "").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="occupation">Occupation *</label>';
        $html.=     '<div class="detail-value" id="occupation">'.$detail->occupation.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="participated">Have you ever participated in IWF?</label>';
        $detail->previous_participations = str_replace(';','<br>',$detail->previous_participations);
        $html.=     '<div class="detail-value" id="participated">'.$detail->previous_participations.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="provinces">Province</label>';
        $html.=     '<div class="detail-value" id="provinces">'.$detail->provinces_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="regions">Region</label>';
        $html.=     '<div class="detail-value" id="regions">'.$detail->regions_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="roles">Roles</label>';
        if($detail->roles == "ADMIN"){
            $html.=     '<div class="detail-value" id="roles">Admin</div>';
        }elseif($detail->roles == "MODERATOR"){
            $html.=     '<div class="detail-value" id="roles">Moderator</div>';
        }elseif($detail->roles == "GUEST"){
            $html.=     '<div class="detail-value" id="roles">Guest</div>';
        }
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="verified">Is Verified?</label>';
        $html.=     '<div class="detail-value" id="verified">'.(!empty($detail->account_verified_date) ? "Yes" : "No").'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="blocked">Is Blocked?</label>';
        $html.=     '<div class="detail-value" id="blocked">'.($detail->is_blocked == 1 ? "Yes" : "No").'</div>';
        $html.= '</div>';

        $data['html'] = $html;

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
}
