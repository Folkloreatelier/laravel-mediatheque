<?php

namespace Folklore\Mediatheque\Contracts\Models;

use Exception;

interface PipelineJob
{
    public function getName(): string;

    public function getDefinition(): array;

    public function setDefinition(array $definition): void;

    public function run(): void;

    public function markStarted(): void;

    public function markEnded(): void;

    public function markFailed(Exception $e = null): void;

    public function canRun($model = null): bool;

    public function isWaitingForFile($name): bool;
}
