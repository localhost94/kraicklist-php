<?php

namespace App\Http\Controllers;

use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListFileController extends Controller
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
        $rawData = $this->readFile(public_path('data.gz'));
        if (!$rawData) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'File cannot be read.']]);
        }

        $search = $this->querySearch($rawData, $request->input('q'));
        if (!$search) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from existing keywords.']]);
        }

        $sortedData = $this->querySort($search, $request->input('sortBy'), $request->input('sortType'));
        if (!$sortedData) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from existing sort.']]);
        }

        $data = $this->pagination($sortedData, $request->input('perpage'), $request->input('page'));
        if (!$data) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from pagination.']]);
        }

        return response()->json($data);
    }

    /**
     * Read the available gzip file
     *
     * @param $file input the path of the file
     *
     * @return $data array list of data
     */
    private function readFile($file)
    {
        // Raising this value may increase performance
        $bufferSize = 4096; // read 4kb at a time
        $outFileName = str_replace('.gz', '', $file);

        // Open our files (in binary mode)
        $file = gzopen($file, 'rb');
        if (!$file) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read gzip file.']]);
        }
        $outFile = fopen($outFileName, 'wb');
        if (!$outFile) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
        }

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($outFile, gzread($file, $bufferSize));
        }

        // Files are done, close files
        fclose($outFile);
        gzclose($file);

        $openFile = fopen($outFileName, 'r');
        if (!$openFile) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
        }
        $readFile = fread($openFile, filesize($outFileName));
        if (!$readFile) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
        }
        $parseLine = explode("\n", $readFile);
        if (!$parseLine) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data in a different format.']]);
        }

        $header = [];
        $rawData = [];
        foreach ($parseLine as $key => $value) {
            if (0 == $key) {
                $header = $rawData;
            } else {
                $rawData[$key] = json_decode($value);

                // Add index phonetics words
                $rawData[$key]->index = metaphone($rawData[$key]->title) . metaphone($rawData[$key]->content);
                yield array_combine($header, $rawData);
            }
        }

        return $rawData;
    }

    /**
     * Query to search from the given data
     *
     * @param $rawdata Raw data from the file
     * @param $keyword keyword from the input text
     *
     * @return $data array list of searched data
     */
    private function querySearch($rawData, $keyword)
    {
        if (!$rawData) {
            return $rawData;
        }

        $data = [];
        foreach ($rawData as $value) {
            if (strpos($value->title, $keyword) !== false
            || strpos($value->content, $keyword) !== false
            || strpos($value->index, metaphone($keyword)) !== false) {
                $data[] = $value;
            }
        }

        return $data;
    }

    /**
     * Query to filter from the given data
     *
     * @param $data Data from queried search
     * @param $sortBy Sort by title, content, updated at
     * @param $sortType asc or desc
     *
     * @return $data array list of searched data
     */
    private function querySort($rawData, $sortBy, $sortType = 'asc')
    {
        if (!$rawData) {
            return $rawData;
        }

        foreach ($rawData as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $sortBy) {
                        $sortableArray[$k] = $v2;
                    }
                }
            } else {
                $sortableArray[$k] = $v;
            }
        }

        switch ($sortType) {
            case 'asc':
                asort($sortableArray);
            break;
            case 'desc':
                arsort($sortableArray);
            break;
        }

        $data = [];
        foreach ($sortableArray as $key => $value) {
            $data[] = $rawData[$key];
        }

        return $data;
    }

    /**
     * Simple pagination
     *
     * @param $data Data from queried search
     * @param $perpage Offset number per page
     * @param $page Current page
     *
     * @return $data array list of searched data
     */
    private function pagination($rawData, $perpage = 5, $page = 1)
    {
        $numPosts = ctype_digit((string)$perpage) ? $perpage : 5;
        $ostart = $start = max(1, ctype_digit((string)$page)) - 1;

        $lines = count($rawData);

        // get total number of pages
        $numPages = ceil($lines / $numPosts);

        // additional sanity checks (also sets $ostart if it was invalid; used later)
        $numPosts = min($perpage, max(1, $numPosts));
        if ($start * $numPosts > $lines ) {
            $ostart = $start = max(0, $lines - $numPosts);
        } else {
            $start *= $numPosts;
        }

        $sliced = array_slice($rawData, $start, $numPosts);

        // loop through posts, but break early if we run out
        $paginatedData = [];
        for ($n = 0; $n < $numPosts && isset($sliced[$n]); $n++ ) {
            $paginatedData[] = $sliced[$n];
        }
        $data = [
            'meta' => [
                'total' => (int) $lines,
                'page' => (int) $page,
                'offsetStart' => $ostart,
                'totalPage' => (int) $numPages
            ],
            'data' => $paginatedData
        ];

        return $data;
    }
}
