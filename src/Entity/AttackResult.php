<?php

declare(strict_types=1);

namespace App\Entity;

class AttackResult {
    public function __construct(
        public readonly bool $hit,
        public readonly int $damage,
        public readonly int $roll,
        public readonly bool $critical
    ) {}

    public function getDescription(): string {
        if (!$this->hit) {
            return "Raté (jet: {$this->roll})";
        }

        $critText = $this->critical ? " - CRITIQUE!" : "";
        return "Touché{$critText} (jet: {$this->roll}, dégâts: {$this->damage})";
    }
}