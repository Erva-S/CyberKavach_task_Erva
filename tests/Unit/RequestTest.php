<?php
declare(strict_types=1);

namespace CyberKavach\Tests\Unit;

use CyberKavach\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/admin/categories?tab=list',
        ];
        $_GET = ['tab' => 'list'];
        $_POST = ['name' => 'Event Category', 'slug' => 'event-category'];
    }

    public function testItReadsInputsFromPostAndGet(): void
    {
        $request = new Request();

        self::assertSame('POST', $request->method());
        self::assertSame('/admin/categories', $request->path());
        self::assertSame('Event Category', $request->input('name'));
        self::assertSame('list', $request->input('tab'));
        self::assertSame([
            'tab' => 'list',
            'name' => 'Event Category',
            'slug' => 'event-category',
        ], $request->all());
    }
}