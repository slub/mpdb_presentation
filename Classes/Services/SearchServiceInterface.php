<?php
namespace Slub\MpdbPresentation\Services;

use Illuminate\Support\Collection;
use Slub\MpdbCore\Domain\Model\Publisher;

interface SearchServiceInterface
{
    public function setIndex(string $index): SearchServiceInterface;

    public function setPublisher(string $publisher): SearchServiceInterface;

    public function setSearchterm(string $searchTerm): SearchServiceInterface;

    public function setUid(int $uid): SearchServiceInterface;

    public function setFrom(int $from): SearchServiceInterface;

    public function setSize(int $size): SearchServiceInterface;

    public function search(): Collection;

    public function count(): int;
}
