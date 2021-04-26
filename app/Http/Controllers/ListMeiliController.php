<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListMeiliController extends Controller
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

        $keyword = $request->input('q');
        if (!empty($keyword)) {
            $rawData = Sample::search($keyword);
        }

        if (!$rawData) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Data is not exists.']]);
        }

        $data = $rawData->paginate($request->input('perpage'));
        if (!$data) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from pagination.']]);
        }

        return response()->json($data);
    }
}
