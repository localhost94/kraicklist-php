<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use Illuminate\Http\Request;

class ListMysqlController extends Controller
{
    /**
     * General list function
     *
     * @param $q keyword from input text list
     * @param $sortBy sort by title, content or updated at
     * @param $sortType asc or desc
     *
     * @return $data json list of searched data
     */
    public function list(Request $request)
    {
        ini_set('memory_limit', '-1');
        
        $rawData = Sample::query();
        $keyword = $request->input('q');
        if (!empty($keyword)) {
            $rawData = $rawData->selectRaw('*,MATCH (title, content) AGAINST (\''.$keyword.'\' IN NATURAL LANGUAGE MODE) AS score');
            $rawData = $rawData->whereRaw("MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$keyword]);
        }

        // $data = $rawData->orderBy('score', 'desc')->paginate($request->input('perpage') ?? 2);
        $data = $rawData->orderBy('score', 'desc')->get();
        
        return response()->json($data);
    }
}
