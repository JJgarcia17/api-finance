<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimiterTest extends TestCase
{
    use RefreshDatabase;

    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new RateLimiter(5, 60); // 5 requests per hour for testing
        Cache::flush(); // Clean cache for each test
    }

    public function test_can_make_request_when_under_limit(): void
    {
        $this->assertTrue($this->rateLimiter->canMakeRequest('test_provider', 'user_1'));
    }

    public function test_records_request_properly(): void
    {
        $this->rateLimiter->recordRequest('test_provider', 'user_1');
        
        $remaining = $this->rateLimiter->getRemainingRequests('test_provider', 'user_1');
        $this->assertEquals(4, $remaining);
    }

    public function test_blocks_requests_when_over_limit(): void
    {
        // Make 5 requests (the limit)
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordRequest('test_provider', 'user_1');
        }

        // Should be blocked now
        $this->assertFalse($this->rateLimiter->canMakeRequest('test_provider', 'user_1'));
        $this->assertEquals(0, $this->rateLimiter->getRemainingRequests('test_provider', 'user_1'));
    }

    public function test_different_users_have_separate_limits(): void
    {
        // User 1 makes 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordRequest('test_provider', 'user_1');
        }

        // User 1 should be blocked
        $this->assertFalse($this->rateLimiter->canMakeRequest('test_provider', 'user_1'));
        
        // User 2 should still be able to make requests
        $this->assertTrue($this->rateLimiter->canMakeRequest('test_provider', 'user_2'));
    }

    public function test_different_providers_have_separate_limits(): void
    {
        // Provider 1 gets to limit
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->recordRequest('provider_1', 'user_1');
        }

        // Provider 1 should be blocked
        $this->assertFalse($this->rateLimiter->canMakeRequest('provider_1', 'user_1'));
        
        // Provider 2 should still work
        $this->assertTrue($this->rateLimiter->canMakeRequest('provider_2', 'user_1'));
    }
}
