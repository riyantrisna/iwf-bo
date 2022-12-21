<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GroupEvent;

class GroupEventController extends Controller
{
    public function index()
    {
        $data['title'] = "Kelompok Acara";
        return view('group-event.index', $data);
    }

    public function data(Request $request)
	{
        $keyword = $request->search['value'];
        $start = $request->post('start');
        $length = $request->post('length');

        $columns = array(
            2 => 'groups.group_name',
            3 => 'groups.position'
        );

        $order = $columns[$request->order[0]['column']];
        $dir = $request->order[0]['dir'];

		$list = GroupEvent::selectRaw('
            groups.id,
            groups.group_name,
            groups.position
        ');
        if(!empty($keyword)){
            $keyword = '%'.$keyword .'%';
            $query = $list->where(function($q) use($keyword) {
                $q->where('groups.group_name', 'LIKE', $keyword)
                ->orWhere('groups.position', 'LIKE', $keyword)
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
                 <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Delete" onclick="deletes('."'".$value->id."','".$value->group_name."'".')"><i class="fas fa-trash-alt"></i></a>';
                $row[] = $value->group_name;
                $row[] = $value->position;
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
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="position">Urutan *</label>';
        $html.=     '<input type="number" id="position" name="position" class="form-control">';
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
            $check_data = GroupEvent::select('id')->where('group_name', $request->name)->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nama <b>'.$request->name.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->position)){
            $validation = $validation && false;
            $validation_text.= '<li>Urutan dibutuhkan</li>';
        }else{
            $check_data = GroupEvent::select('id')->where('position', $request->position)->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Urutan <b>'.$request->position.'</b> telah digunakan</li>';
            }
        }

        if($validation){
            $results = true;

            $date = date('Y-m-d H:i:s');
            $user_id = auth()->user()->id;

            $ge = new GroupEvent;

            $ge->group_name = $request->name;
            $ge->position = $request->position;
            $response = $ge->save();

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

        $detail = GroupEvent::where('id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama *</label>';
        $html.=     '<input type="text" id="name" name="name" class="form-control" value="'.$detail->group_name.'">';
        $html.=     '<input type="hidden" id="id" name="id" class="form-control" value="'.$id.'">';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="position">Urutan *</label>';
        $html.=     '<input type="number" id="position" name="position" class="form-control" value="'.$detail->position.'">';
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
            $check_data = GroupEvent::select('id')->where('group_name', $request->name)->whereNotIn('id', [$request->id])->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Nama <b>'.$request->name.'</b> telah digunakan</li>';
            }
        }

        if(empty($request->position)){
            $validation = $validation && false;
            $validation_text.= '<li>Urutan dibutuhkan</li>';
        }else{
            $check_data = GroupEvent::select('id')->where('position', $request->position)->whereNotIn('id', [$request->id])->first();
            if(!empty($check_data->id)){
                $validation = $validation && false;
                $validation_text.= '<li>Urutan <b>'.$request->position.'</b> telah digunakan</li>';
            }
        }

        if($validation){
            $results = true;

            $date = date('Y-m-d H:i:s');
            $user_id = auth()->user()->id;

            $ge = GroupEvent::where('id', $request->id)->first();

            $ge->group_name = $request->name;
            $ge->position = $request->position;
            $response = $ge->save();

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
        $detail = GroupEvent::where('id', $id)->first();

        $html = '<div class="form-group">';
        $html.=     '<label for="name">Nama</label>';
        $html.=     '<div id="name" class="detail-value">'.$detail->group_name.'</div>';
        $html.= '</div>';
        $html.= '<div class="form-group">';
        $html.=     '<label for="position">Urutan</label>';
        $html.=     '<div id="position" class="detail-value">'.$detail->position.'</div>';
        $html.= '</div>';

        $data['html'] = $html;

        return response()->json($data, 200);
    }

    public function delete(Request $request, $id){

        $delete = GroupEvent::where('id', $id)->delete();
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
