<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Regions;

class RegionsController extends Controller
{
    public function getRegionsByProvincesId(Request $request, $id)
    {
        if(!empty($id)){
            $regions = Regions::where('province_id', $id)->get();
        }else{
            $regions = array();
        }
        return response()->json($regions, 200);
    }
}
