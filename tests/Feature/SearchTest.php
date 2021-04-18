<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SearchTest extends TestCase
{
    private $listStructure = [
        'data' => [ 0 => ['id', 'title', 'content', 'index', 'tags', 'image_urls', 'updated_at'] ],
        'meta' => ['offsetStart', 'page', 'total', 'totalPage']
    ];

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetList()
    {
        $response = $this->json('GET', '/list?q=iphone&sortBy=title&sortType=asc&page=1&perpage=5');

        $response->assertStatus(200);
        $response->assertJsonStructure($this->listStructure);
    }

    public function testSearchByKeyword()
    {
        $response = $this->json('GET', '/list?q=iphone&sortBy=title&sortType=asc&page=1&perpage=5');

        $response->assertStatus(200);
        $response->assertJsonStructure($this->listStructure);
    }

    public function testInvalidKeyword()
    {
        $response = $this->json('GET', '/list?q=###');

        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
    }

    public function testSortByTitleAsc()
    {
        $response = $this->json('GET', '/list?q=iphone&sortBy=title&sortType=asc&page=1&perpage=5');

        $response->assertStatus(200);
        $response->assertJsonStructure($this->listStructure);
    }

    public function testSortByTitleDesc()
    {
        $response = $this->json('GET', '/list?q=iPhone&sortBy=title&sortType=desc&page=1&perpage=5');

        $response->assertStatus(200);
        $response->assertJsonStructure($this->listStructure);
    }

    public function testPagination()
    {
        $response = $this->json('GET', '/list?q=iPhone&sortBy=title&sortType=asc&page=2&perpage=5');

        $response->assertStatus(200);
        $response->assertJsonStructure($this->listStructure);
    }
}
