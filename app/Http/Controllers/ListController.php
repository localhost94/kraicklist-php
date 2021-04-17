<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListController extends Controller
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
        $rawData = $this->readFile(storage_path('app/data.gz'));
        if (!$rawData) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'File cannot be read.']]);
        }

        $search = $this->querySearch($rawData, $request->input('q'));
        if (!$search) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from existing keywords.']]);
        }

        $data = $this->queryFilter($search, $request->input('sortBy'), $request->input('sortType'));
        if (!$data) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from existing filter.']]);
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
        $rawData = [];
        foreach ($parseLine as $key => $value) {
            $rawData[$key] = json_decode($value);

            // Add index phonetics words
            $rawData[$key]->index = metaphone($rawData[$key]->title) . metaphone($rawData[$key]->content);
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
    private function queryFilter($rawData, $sortBy, $sortType = 'asc')
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
}
