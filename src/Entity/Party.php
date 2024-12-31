<?php

declare(strict_types=1);

namespace App\Entity;

class Party {

    /** @var array<Character> */
    private array $members = [];

    public function addMember(Character $character): void {
        $this->members[] = $character;
    }

    public function getMembers(): array {
        return $this->members;
    }

    public function getLivingMembers(): array {
        return array_filter($this->members, fn($member) => !$member->isDead());
    }

    public function isDefeated(): bool {
        return empty($this->getLivingMembers());
    }
}