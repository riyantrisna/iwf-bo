<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $data['title'] = "Setting";
        return view('setting.index', $data);
    }

    public function data(Request $request)
	{
        $keyword = $request->search['value'];
        $start = $request->post('start');
        $length = $request->post('length');

        $columns = array(
            2 => 'setting.setting_key'
        );

        $order = $columns[$request->order[0]['column']];
        $dir = $request->order[0]['dir'];

		$list = Setting::selectRaw('
            setting.setting_id,
            setting.setting_key,
            setting.setting_value
        ');
        if(!empty($keyword)){
            $keyword = '%'.$keyword .'%';
            $query = $list->where(function($q) use($keyword) {
                $q->where('setting.setting_key', 'LIKE', $keyword)
                ->orWhere('setting.setting_value', 'LIKE', $keyword)
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
                 <a class="btn btn-sm btn-info" href="javascript:void(0)" title="Detail" onclick="detail(\''.$value->setting_id.'\')"><i class="fas fa-search"></i></a>
                 <a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit('."'".$value->setting_id."'".')"><i class="fas fa-edit"></i></a>';
                $row[] = $value->setting_key;
                $row[] = $value->setting_value;
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

        $html = '<div class="form-group">';
        $html.=     '<label for="setting_key">Penanda *</label>';
        $html.=     '<input type="text" id="setting_key" name="setting_key" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="setting_value">Nilai *</label>';
        $html.=     '<textarea id="setting_value" name="setting_value" class="form-control"></textarea>';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function add(Request $request){

        $validation = true;
        $validation_text = '';

        if(empty($request->setting_key)){
            $validation = $validation && false;
            $validation_text.= '<li>Penanda dibutuhkan</li>';
        }else{
            $check_data = Setting::select('setting_id')->where('setting_key', $request->setting_key)->first();
            if(!empty($check_data->setting_id)){
                $validation = $validation && false;
                $validation_text.= '<li>Penanda <b>'.$request->setting_key.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->setting_value)){
            $validation = $validation && false;
            $validation_text.= '<li>Nilai dibutuhkan</li>';
        }

        if($validation){
            $results = true;

            $date = date('Y-m-d H:i:s');
            $user_id = auth()->user()->id;

            $setting = new Setting;

            $setting->setting_key = $request->setting_key;
            $setting->setting_value = $request->setting_value;
            $response = $setting->save();

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
            $result["message"].= $validation_text;
            $result["message"].= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>';
            $result["message"].= '</div>';
        }

        return response()->json($result, 200);
    }

    public function editView(Request $request, $id){

        $detail = Setting::where('setting_id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="setting_key">Nama *</label>';
        $html.=     '<input type="text" id="setting_key" name="setting_key" class="form-control" value="'.$detail->setting_key.'">';
        $html.=     '<input type="hidden" id="setting_id" name="setting_id" class="form-control" value="'.$id.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="setting_value">Urutan *</label>';
        $html.=     '<textarea id="setting_value" name="setting_value" class="form-control">'.$detail->setting_value.'</textarea>';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function edit(Request $request){

        $validation = true;
        $validation_text = '';

        if(empty($request->setting_key)){
            $validation = $validation && false;
            $validation_text.= '<li>Penanda dibutuhkan</li>';
        }else{
            $check_data = Setting::select('setting_id')->where('setting_key', $request->setting_key)->whereNotIn('setting_id', [$request->setting_id])->first();
            if(!empty($check_data->setting_id)){
                $validation = $validation && false;
                $validation_text.= '<li>Penanda <b>'.$request->setting_key.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->setting_value)){
            $validation = $validation && false;
            $validation_text.= '<li>Nilai dibutuhkan</li>';
        }

        if($validation){
            $results = true;

            $date = date('Y-m-d H:i:s');
            $user_id = auth()->user()->id;

            $setting = Setting::where('setting_id', $request->setting_id)->first();

            $setting->setting_key = $request->setting_key;
            $setting->setting_value = $request->setting_value;
            $response = $setting->save();

            if ($response) {
                $result["status"] = TRUE;
                $result["message"] = 'Sukses ubah data';
            } else {
                $result["status"] = FALSE;
                $result["message"] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                $result["message"].= '<li>Gagal ubah data</li>';
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
        $detail = Setting::where('setting_id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Penanda</label>';
        $html.=     '<div id="name" class="detail-value">'.$detail->setting_key.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="position">Nilai</label>';
        $html.=     '<div id="position" class="detail-value">'.$detail->setting_value.'</div>';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function delete(Request $request, $id){

        $delete = Setting::where('setting_id', $id)->delete();
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
