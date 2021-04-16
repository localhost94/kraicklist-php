<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SearchController extends Controller
{
    /**
     * General search function
     *
     * @param $q keyword from input text search
     *
     * @return $data json list of searched data
     */
    public function search(Request $request)
    {
        $rawData = $this->readFile(storage_path('app/data.gz'));
        if (!$rawData) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'File cannot be read.']]);
        }
        $data = $this->querySearch($rawData, $request->input('q'));
        if (!$rawData) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from existing keywords.']]);
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
        $data = [];
        foreach ($rawData as $key => $value) {
            if (strpos($value->title, $keyword) !== false || strpos($value->content, $keyword) !== false) {
                $data[] = $value;
            }
        }

        return $data;
    }
}
