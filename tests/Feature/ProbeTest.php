<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class ProbeTest extends TestCase {
    use RefreshDatabase;
    public function test_a(): void {
        Http::fake();
        $this->postJson(route('chatbot.message'), ['message' => ''])->assertStatus(422);
        echo "first ok\n";
        $this->postJson(route('chatbot.message'), ['message' => str_repeat('a', 501)])->assertStatus(422);
        echo "second ok\n";
        Http::assertNothingSent();
        echo "assert ok\n";
    }
}
