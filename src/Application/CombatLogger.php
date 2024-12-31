<?php

namespace App\Application;

class CombatLogger {
    private array $log = [];

    public function addEntry(string $message): void {
        $this->log[] = $message;
    }

    public function getLog(): array {
        return $this->log;
    }
}