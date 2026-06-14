<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\TicketAttachmentService;
use PHPUnit\Framework\TestCase;

class TicketAttachmentRulesTest extends TestCase
{
    private TicketAttachmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TicketAttachmentService;
    }

    public function test_allows_known_extensions_within_size(): void
    {
        $this->assertTrue($this->service->isAllowedAttachment('pdf', 1024));
        $this->assertTrue($this->service->isAllowedAttachment('png', 1));
        $this->assertTrue($this->service->isAllowedAttachment('zip', TicketAttachmentService::MAX_FILE_BYTES));
    }

    public function test_rejects_disallowed_extensions(): void
    {
        $this->assertFalse($this->service->isAllowedAttachment('exe', 10));
        $this->assertFalse($this->service->isAllowedAttachment('sh', 10));
        $this->assertFalse($this->service->isAllowedAttachment('', 10));
    }

    public function test_rejects_empty_and_oversized_files(): void
    {
        $this->assertFalse($this->service->isAllowedAttachment('pdf', 0));
        $this->assertFalse($this->service->isAllowedAttachment('pdf', TicketAttachmentService::MAX_FILE_BYTES + 1));
    }
}
