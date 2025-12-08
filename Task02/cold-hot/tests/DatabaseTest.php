<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;
use PDO;
use PDOException;

use function Ganchenkov\ColdHot\Database\initDatabase;
use function Ganchenkov\ColdHot\Database\createGame;
use function Ganchenkov\ColdHot\Database\getGameById;
use function Ganchenkov\ColdHot\Database\getAllGames;
use function Ganchenkov\ColdHot\Database\updateGameOutcome;
use function Ganchenkov\ColdHot\Database\saveAttempt;
use function Ganchenkov\ColdHot\Database\getAttemptsByGameId;

class DatabaseTest extends TestCase
{
    use TestTrait;

    private ?PDO $pdo = null;
    private static bool $sqliteAvailable = false;

    public static function setUpBeforeClass(): void
    {
        // Check if SQLite driver is available
        self::$sqliteAvailable = in_array('sqlite', PDO::getAvailableDrivers());
    }

    protected function setUp(): void
    {
        if (!self::$sqliteAvailable) {
            $this->markTestSkipped('SQLite PDO driver is not available');
        }

        // Use in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Create tables
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS games (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                player_name TEXT NOT NULL,
                secret_number TEXT NOT NULL,
                outcome TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');
        
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER NOT NULL,
                attempt_number INTEGER NOT NULL,
                guess TEXT NOT NULL,
                hints TEXT NOT NULL,
                FOREIGN KEY (game_id) REFERENCES games(id)
            )
        ');
    }

    /**
     * Feature: composer-cold-hot-game, Property 5: Game Session Round-Trip
     * Validates: Requirements 1.3, 4.2, 5.1, 6.1
     * 
     * For any valid player name and secret number, creating a game session and then 
     * retrieving it by ID SHALL return the same player name and secret number.
     */
    public function testGameSessionRoundTripProperty(): void
    {
        $this->forAll(
            Generator\suchThat(
                fn($s) => strlen($s) > 0 && strlen($s) <= 50,
                Generator\string()
            ),
            Generator\choose(1, 9),
            Generator\choose(0, 9),
            Generator\choose(0, 9)
        )
        ->withMaxSize(100)
        ->then(function (string $playerName, int $d1, int $d2, int $d3) {
            // Ensure unique digits for secret number
            $digits = array_unique([$d1, $d2, $d3]);
            if (count($digits) < 3) {
                // Skip if digits are not unique
                return;
            }
            
            $secretNumber = "{$d1}{$d2}{$d3}";
            
            // Create game
            $gameId = createGame($this->pdo, $playerName, $secretNumber);
            
            // Retrieve game
            $game = getGameById($this->pdo, $gameId);
            
            // Verify round-trip
            $this->assertNotNull($game, "Game should be retrievable by ID");
            $this->assertSame($playerName, $game['player_name'], "Player name should match");
            $this->assertSame($secretNumber, $game['secret_number'], "Secret number should match");
            $this->assertSame($gameId, (int) $game['id'], "Game ID should match");
        });
    }

    /**
     * Feature: composer-cold-hot-game, Property 6: Attempt Round-Trip
     * Validates: Requirements 2.5, 6.3
     * 
     * For any valid game ID, attempt number, guess, and hints, saving an attempt and then 
     * retrieving attempts for that game SHALL include the saved attempt with identical values.
     */
    public function testAttemptRoundTripProperty(): void
    {
        $validHints = ['Горячо', 'Тепло', 'Холодно'];
        
        $this->forAll(
            Generator\suchThat(
                fn($s) => strlen($s) > 0 && strlen($s) <= 50,
                Generator\string()
            ),
            Generator\choose(1, 100),
            Generator\choose(0, 9),
            Generator\choose(0, 9),
            Generator\choose(0, 9),
            Generator\elements($validHints),
            Generator\elements($validHints),
            Generator\elements($validHints)
        )
        ->withMaxSize(100)
        ->then(function (
            string $playerName,
            int $attemptNumber,
            int $d1,
            int $d2,
            int $d3,
            string $h1,
            string $h2,
            string $h3
        ) {
            $secretNumber = '123'; // Fixed secret for simplicity
            $guess = "{$d1}{$d2}{$d3}";
            $hints = "{$h1} {$h2} {$h3}";
            
            // Create game first
            $gameId = createGame($this->pdo, $playerName, $secretNumber);
            
            // Save attempt
            saveAttempt($this->pdo, $gameId, $attemptNumber, $guess, $hints);
            
            // Retrieve attempts
            $attempts = getAttemptsByGameId($this->pdo, $gameId);
            
            // Verify round-trip
            $this->assertNotEmpty($attempts, "Attempts should be retrievable");
            
            // Find the saved attempt
            $found = false;
            foreach ($attempts as $attempt) {
                if ((int) $attempt['attempt_number'] === $attemptNumber) {
                    $this->assertSame($guess, $attempt['guess'], "Guess should match");
                    $this->assertSame($hints, $attempt['hints'], "Hints should match");
                    $this->assertSame($gameId, (int) $attempt['game_id'], "Game ID should match");
                    $found = true;
                    break;
                }
            }
            
            $this->assertTrue($found, "Saved attempt should be found in retrieved attempts");
        });
    }
}
