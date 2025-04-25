<?php

namespace App\Tests\Trait;

use App\Trait\PaginationTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class TestPagination
{
    use PaginationTrait;

    public function getPaginationParamsPublic(Request $request, int $defaultLimit = 10): array
    {
        return $this->getPaginationParams($request, $defaultLimit);
    }
}

class PaginationTraitTest extends TestCase
{
    private TestPagination $traitObject;

    protected function setUp(): void
    {
        $this->traitObject = new TestPagination();
    }

    public function testGetPaginationParamsWithDefaults(): void
    {
        $request = new Request();

        [$offset, $limit, $page] = $this->traitObject->getPaginationParamsPublic($request);

        $this->assertEquals(0, $offset);
        $this->assertEquals(10, $limit);
        $this->assertEquals(1, $page);
    }

    public function testGetPaginationParamsWithQueryParams(): void
    {
        $request = new Request(['offset' => '20', 'limit' => '5']);

        [$offset, $limit, $page] = $this->traitObject->getPaginationParamsPublic($request);

        $this->assertEquals(20, $offset);
        $this->assertEquals(5, $limit);
        $this->assertEquals(5, $page); // page = offset/limit + 1 = 20/5 + 1 = 5
    }

    public function testGetPaginationParamsWithPageParam(): void
    {
        $request = new Request(['page' => '3', 'limit' => '5']);

        [$offset, $limit, $page] = $this->traitObject->getPaginationParamsPublic($request);

        $this->assertEquals(10, $offset); // offset = (page-1) * limit = (3-1) * 5 = 10
        $this->assertEquals(5, $limit);
        $this->assertEquals(3, $page);
    }

    public function testGetPaginationParamsWithCustomDefaultLimit(): void
    {
        $request = new Request();

        [$offset, $limit, $page] = $this->traitObject->getPaginationParamsPublic($request, 25);

        $this->assertEquals(0, $offset);
        $this->assertEquals(25, $limit);
        $this->assertEquals(1, $page);
    }

    public function testGetPaginationParamsWithInvalidValues(): void
    {
        $request = new Request(['offset' => '-10', 'limit' => '-5']);

        [$offset, $limit, $page] = $this->traitObject->getPaginationParamsPublic($request);

        // Should use default values for invalid inputs
        $this->assertEquals(0, $offset);
        $this->assertEquals(10, $limit);
        $this->assertEquals(1, $page);
    }
}
