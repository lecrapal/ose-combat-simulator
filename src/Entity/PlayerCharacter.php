<?php

declare(strict_types=1);

namespace App\Entity;

class PlayerCharacter extends Character {

    public function attack(Character $target): AttackResult {
        $roll = random_int(1, 20);
        $critical = ($roll === 20);

        $hit = ($roll === 20) || ($roll + $this->attackBonus >= $target->getAc());

        if (!$hit) {
            return new AttackResult(false, 0, $roll, false);
        }

        // Parse damage dice (ex: "1d8" ou "2d6")
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
}