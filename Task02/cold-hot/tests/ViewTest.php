<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

use function Ganchenkov\ColdHot\View\formatGameForList;

class ViewTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: composer-cold-hot-game, Property 7: Game List Display Completeness
     * Validates: Requirements 5.3, 6.4
     * 
     * For any game session record, the formatted output SHALL contain the game ID, 
     * date, player name, secret number, and outcome.
     */
    public function testGameListDisplayCompletenessProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 1000),                    // game ID
            Generator\elements(['Иван', 'Мария', 'Петр', 'Анна', 'Player1', 'TestUser']), // player name
            Generator\choose(100, 999),                   // secret number (3 digits)
            Generator\elements(['угадал', null, 'в процессе']), // outcome
            Generator\choose(1, 28),                      // day
            Generator\choose(1, 12),                      // month
            Generator\choose(2020, 2025)                  // year
        )
        ->withMaxSize(100)
        ->then(function (
            int $id,
            string $playerName,
            int $secretNumber,
            ?string $outcome,
            int $day,
            int $month,
            int $year
        ) {
            $secretNumberStr = (string) $secretNumber;
            $createdAt = sprintf('%04d-%02d-%02d 12:00:00', $year, $month, $day);
            
            $game = [
                'id' => $id,
                'player_name' => $playerName,
                'secret_number' => $secretNumberStr,
                'outcome' => $outcome,
                'created_at' => $createdAt
            ];
            
            $formatted = formatGameForList($game);
            
            // Output must contain game ID
            $this->assertStringContainsString(
                (string) $id,
                $formatted,
                "Formatted output must contain game ID"
            );
            
            // Output must contain date
            $this->assertStringContainsString(
                $createdAt,
                $formatted,
                "Formatted output must contain date"
            );
            
            // Output must contain player name
            $this->assertStringContainsString(
                $playerName,
                $formatted,
                "Formatted output must contain player name"
            );
            
            // Output must contain secret number
            $this->assertStringContainsString(
                $secretNumberStr,
                $formatted,
                "Formatted output must contain secret number"
            );
            
            // Output must contain outcome (or default text for null)
            $expectedOutcome = $outcome ?? 'в процессе';
            $this->assertStringContainsString(
                $expectedOutcome,
                $formatted,
                "Formatted output must contain outcome"
            );
        });
    }
}
