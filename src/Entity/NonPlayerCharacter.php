<?php
declare(strict_types=1);

namespace App\Entity;

use App\Application\CombatLogger;

class NonPlayerCharacter extends Character
{
    private int $moral;
    private bool $hasFled = false;

    public function __construct(
        string $name,
        int    $hp,
        int    $ac,
        int    $attackBonus,
        string $damageDice,
        int    $moral
    )
    {
        parent::__construct($name, $hp, $ac, $attackBonus, $damageDice);
        $this->moral = $moral;
    }

    public function getMoral(): int
    {
        return $this->moral;
    }

    public function hasFled(): bool
    {
        return $this->hasFled;
    }

    public function checkMorale(): bool
    {
        if ($this->hasFled) {
            return false;
        }

        $moralCheck = random_int(1, 6) + random_int(1, 6);
        $this->hasFled = ($moralCheck > $this->moral);
        return !$this->hasFled;
    }

    public function attack(Character $target): AttackResult
    {
        if ($this->hasFled) {
            return new AttackResult(false, 0, 0, false);
        }

        $roll = random_int(1, 20);
        $critical = ($roll === 20);

        $hit = ($roll === 20) || ($roll + $this->attackBonus >= $target->getAc());

        if (!$hit) {
            return new AttackResult(false, 0, $roll, false);
        }

        preg_match('/(\d+)d(\d+)/', $this->damageDice, $matches);
        $diceCount = (int)$matches[1];
        $diceType = (int)$matches[2];

        $damage = 0;
        for ($i = 0; $i < $diceCount; $i++) {
            $damage += random_int(1, $diceType);
        }

        if ($critical) {
            $damage *= 2;
        }

        return new AttackResult(true, $damage, $roll, $critical);
    }

    public static function checkGroupMorale(array $npcs, CombatLogger $logger): void
    {
        foreach ($npcs as $npc) {
            if (!$npc->hasFled()) {
                $moralCheck = random_int(1, 6) + random_int(1, 6);
                $npc->hasFled = ($moralCheck > $npc->moral);
                if ($npc->hasFled) {
                    $logger->addEntry(sprintf(
                        '%s échoue son test de moral (%d > %d) et fuit !',
                        $npc->getName(),
                        $moralCheck,
                        $npc->moral
                    ));
                } else {
                    $logger->addEntry(sprintf(
                        '%s réussit son test de moral (%d <= %d)',
                        $npc->getName(),
                        $moralCheck,
                        $npc->moral
                    ));
                }
            }
        }
    }
}