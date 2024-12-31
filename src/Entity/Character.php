<?php

declare(strict_types=1);

namespace App\Entity;

abstract class Character {
    protected int $hp;
    protected int $maxHp;
    protected int $ac;
    protected int $attackBonus;
    protected string $damageDice;

    protected string $name;

    protected int $deathCount = 0;

    public function __construct(
        string $name,
        int $hp,
        int $ac,
        int $attackBonus,
        string $damageDice
    ) {
        $this->name = $name;
        $this->maxHp = $hp;
        $this->hp = $hp;
        $this->ac = $ac;
        $this->attackBonus = $attackBonus;
        $this->damageDice = $damageDice;
    }

    public function getHp(): int {
        return $this->hp;
    }

    public function getMaxHp(): int {
        return $this->maxHp;
    }

    public function getAc(): int {
        return $this->ac;
    }

    public function getAttackBonus(): int {
        return $this->attackBonus;
    }

    public function getDamageDice(): string {
        return $this->damageDice;
    }

    public function takeDamage(int $damage): void {
        $this->hp = max(0, $this->hp - $damage);
    }

    public function isDead(): bool {
        return $this->hp <= 0;
    }

    public function getName(): string {
        return $this->name;
    }

    public function incrementDeathCount(): void {
        $this->deathCount++;
    }

    public function getDeathCount(): int {
        return $this->deathCount;
    }

    abstract public function attack(Character $target): AttackResult;
}