<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');

use App\Application\CombatSimulator;
use App\Entity\Party;
use App\Entity\PlayerCharacter;
use App\Entity\NonPlayerCharacter;

try {
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

    $party1 = new Party();
    foreach ($data['party1'] as $playerData) {
        $party1->addMember(new PlayerCharacter(
            $playerData['name'],
            $playerData['hp'],
            $playerData['ac'],
            $playerData['attackBonus'],
            $playerData['damageDice']
        ));
    }

    $party2 = new Party();
    foreach ($data['party2'] as $monsterData) {
        $count = $monsterData['count'] ?? 1;
        for ($i = 0; $i < $count; $i++) {
            $party2->addMember(new NonPlayerCharacter(
                $monsterData['name'] . ($count > 1 ? " #" . ($i + 1) : ""),
                $monsterData['hp'],
                $monsterData['ac'],
                $monsterData['attackBonus'],
                $monsterData['damageDice'],
                $monsterData['moral']
            ));
        }
    }

    $simulator = new CombatSimulator();
    $result = $simulator->simulateCombat($party1, $party2, $data['useMorale'] ?? true);

    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}