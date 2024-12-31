<?php
declare(strict_types=1);

namespace App\Application;

use App\Entity\Party;
use App\Entity\Character;

class CombatSimulator {
    private const SIMULATION_COUNT = 1000;

    public function simulateCombat(Party $party1, Party $party2, bool $useMorale = true): array {
        $party1Victories = 0;
        $deathStats = [];
        $victoryLog = null;
        $defeatLog = null;

        foreach ($party1->getMembers() as $member) {
            $deathStats[$member->getName()] = 0;
        }

        for ($i = 0; $i < self::SIMULATION_COUNT; $i++) {
            $simParty1 = $this->cloneParty($party1);
            $simParty2 = $this->cloneParty($party2);

            $logger = new CombatLogger();
            $result = $this->simulateSingleCombat($simParty1, $simParty2, $useMorale, $logger);

            if ($result['victory']) {
                $party1Victories++;
                if ($victoryLog === null) {
                    $victoryLog = $logger->getLog();
                }
            } elseif ($defeatLog === null) {
                $defeatLog = $logger->getLog();
            }

            foreach ($result['deaths'] as $name) {
                if (isset($deathStats[$name])) {
                    $deathStats[$name]++;
                }
            }
        }

        return [
            'totalSimulations' => self::SIMULATION_COUNT,
            'party1Victories' => $party1Victories,
            'party1WinRate' => ($party1Victories / self::SIMULATION_COUNT) * 100,
            'party2Victories' => self::SIMULATION_COUNT - $party1Victories,
            'party2WinRate' => (self::SIMULATION_COUNT - $party1Victories) / self::SIMULATION_COUNT * 100,
            'deathStats' => $deathStats,
            'victoryLog' => $victoryLog,
            'defeatLog' => $defeatLog
        ];
    }

    private function simulateSingleCombat(Party $party1, Party $party2, bool $useMorale, CombatLogger $logger): array {
        $deaths = [];

        while (true) {
            $party1First = random_int(1, 2) === 1;
            $attackingParty = $party1First ? $party1 : $party2;
            $defendingParty = $party1First ? $party2 : $party1;

            $logger->addEntry(sprintf('Groupe %d a l\'initiative', $party1First ? 1 : 2));

            if (!$this->processPartyTurn($attackingParty, $defendingParty, $useMorale, $deaths, $logger)) {
                return [
                    'victory' => $defendingParty === $party2,
                    'deaths' => $deaths
                ];
            }

            $attackingParty = $party1First ? $party2 : $party1;
            $defendingParty = $party1First ? $party1 : $party2;

            if (!$this->processPartyTurn($attackingParty, $defendingParty, $useMorale, $deaths, $logger)) {
                return [
                    'victory' => $defendingParty === $party2,
                    'deaths' => $deaths
                ];
            }
        }
    }

    private function processPartyTurn(Party $attackingParty, Party $defendingParty, bool $useMorale, array &$deaths, CombatLogger $logger): bool {
        $livingDefenders = $defendingParty->getLivingMembers();
        $availableDefenders = array_filter($livingDefenders, function($defender) {
            return !($defender instanceof \App\Entity\NonPlayerCharacter && $defender->hasFled());
        });

        if (empty($availableDefenders)) {
            return false;
        }

        foreach ($attackingParty->getLivingMembers() as $attacker) {
            if ($attacker instanceof \App\Entity\NonPlayerCharacter && $attacker->hasFled()) {
                continue;
            }

            $target = $this->selectRandomTarget($availableDefenders);
            $attackResult = $attacker->attack($target);

            if ($attackResult->hit) {
                $logger->addEntry(sprintf(
                    '%s attaque %s et réussit (lancer: %d) et lui inflige %d dégâts%s',
                    $attacker->getName(),
                    $target->getName(),
                    $attackResult->roll,
                    $attackResult->damage,
                    $attackResult->critical ? ' (CRITIQUE!)' : ''
                ));

                $target->takeDamage($attackResult->damage);

                if ($target->isDead()) {
                    if (!in_array($target->getName(), $deaths)) {
                        $deaths[] = $target->getName();
                    }
                    $logger->addEntry(sprintf('%s est mort', $target->getName()));

                    if ($target instanceof \App\Entity\NonPlayerCharacter && $useMorale) {
                        $totalNPCs = count(array_filter($defendingParty->getMembers(),
                            fn($m) => $m instanceof \App\Entity\NonPlayerCharacter
                        ));
                        $deadNPCs = count(array_filter($deaths,
                            fn($name) => str_starts_with($name, $target->getName())
                        ));

                        if ($deadNPCs === (int)ceil($totalNPCs / 2)) {
                            \App\Entity\NonPlayerCharacter::checkGroupMorale($defendingParty->getLivingMembers(), $logger);
                            $logger->addEntry('Test de moral de groupe suite à 50% de pertes !');
                        } else {
                            \App\Entity\NonPlayerCharacter::checkGroupMorale($defendingParty->getLivingMembers(), $logger);
                            $logger->addEntry('Test de moral suite à une mort !');
                        }
                    }
                }

                $availableDefenders = array_filter($defendingParty->getLivingMembers(), function($defender) {
                    return !($defender instanceof \App\Entity\NonPlayerCharacter && $defender->hasFled());
                });

                if (empty($availableDefenders)) {
                    return false;
                }
            } else {
                $logger->addEntry(sprintf(
                    '%s attaque %s et rate (lancer: %d)',
                    $attacker->getName(),
                    $target->getName(),
                    $attackResult->roll
                ));
            }
        }

        return true;
    }

    private function selectRandomTarget(array $livingTargets): Character {
        $livingTargets = array_values($livingTargets);
        return $livingTargets[array_rand($livingTargets)];
    }

    private function cloneParty(Party $party): Party {
        $newParty = new Party();
        foreach ($party->getMembers() as $member) {
            $newParty->addMember(clone $member);
        }
        return $newParty;
    }
}