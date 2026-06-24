<?php
declare(strict_types=1);

namespace CyberKavach\Tests\Unit;

use CyberKavach\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/admin/categories?sort=name',
        ];
        $_GET = ['sort' => 'name', 'page' => '2'];
        $_POST = [];
    }

    public function testItFallsBackToQueryParametersWhenPostIsEmpty(): void
    {
        $request = new Request();

        self::assertSame('GET', $request->method());
        self::assertSame('/admin/categories', $request->path());
        self::assertSame('name', $request->input('sort'));
        self::assertSame('2', $request->input('page'));
        self::assertNull($request->input('missing'));
    }
}