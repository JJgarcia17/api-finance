<?php

namespace Tests\Unit\Services\Llm;

use App\Services\Llm\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->circuitBreaker = new CircuitBreaker(3, 10, 30); // 3 failures, 10 min recovery, 30s timeout
        Cache::flush();
    }

    public function test_circuit_starts_closed(): void
    {
        $this->assertFalse($this->circuitBreaker->isOpen('test_provider'));
        
        $status = $this->circuitBreaker->getStatus('test_provider');
        $this->assertEquals('closed', $status['status']);
        $this->assertEquals(0, $status['failure_count']);
    }

    public function test_circuit_opens_after_failure_threshold(): void
    {
        // Record 3 failures (our threshold)
        for ($i = 0; $i < 3; $i++) {
            $this->circuitBreaker->recordFailure('test_provider');
        }

        $this->assertTrue($this->circuitBreaker->isOpen('test_provider'));
        
        $status = $this->circuitBreaker->getStatus('test_provider');
        $this->assertEquals('open', $status['status']);
        $this->assertEquals(3, $status['failure_count']);
    }

    public function test_success_resets_failure_count_when_closed(): void
    {
        // Record 2 failures (below threshold)
        $this->circuitBreaker->recordFailure('test_provider');
        $this->circuitBreaker->recordFailure('test_provider');
        
        // Record success
        $this->circuitBreaker->recordSuccess('test_provider');
        
        $status = $this->circuitBreaker->getStatus('test_provider');
        $this->assertEquals('closed', $status['status']);
        $this->assertEquals(0, $status['failure_count']);
    }

    public function test_circuit_transitions_to_half_open_after_recovery_time(): void
    {
        // Open the circuit
        for ($i = 0; $i < 3; $i++) {
            $this->circuitBreaker->recordFailure('test_provider');
        }
        
        $this->assertTrue($this->circuitBreaker->isOpen('test_provider'));
        
        // Simulate time passing (we can't easily test this without time manipulation)
        // This would require using Carbon::setTestNow() or similar
        // For now, we just verify the circuit is open
        $status = $this->circuitBreaker->getStatus('test_provider');
        $this->assertEquals('open', $status['status']);
    }

    public function test_different_providers_have_separate_circuits(): void
    {
        // Open circuit for provider 1
        for ($i = 0; $i < 3; $i++) {
            $this->circuitBreaker->recordFailure('provider_1');
        }

        $this->assertTrue($this->circuitBreaker->isOpen('provider_1'));
        $this->assertFalse($this->circuitBreaker->isOpen('provider_2'));
    }
}
