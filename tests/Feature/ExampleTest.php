<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_home_redirects_to_the_book_catalog(): void
    {
        $this->get('/')
            ->assertRedirect(route('buku.index'));
    }
}
