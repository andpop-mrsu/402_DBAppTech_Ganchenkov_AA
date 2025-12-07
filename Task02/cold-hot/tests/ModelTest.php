<?php

declare(strict_types=1);

namespace Ganchenkov\ColdHot\Tests;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

use function Ganchenkov\ColdHot\Model\generateSecretNumber;
use function Ganchenkov\ColdHot\Model\validateGuess;
use function Ganchenkov\ColdHot\Model\generateHints;
use function Ganchenkov\ColdHot\Model\sortHints;
use function Ganchenkov\ColdHot\Model\isCorrectGuess;

class ModelTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: composer-cold-hot-game, Property 1: Valid Secret Number Generation
     * Validates: Requirements 1.1
     * 
     * For any generated secret number, it SHALL be a three-digit string where 
     * all digits are unique (no repeating digits) and the first digit is not zero.
     */
    public function testGenerateSecretNumberProperty(): void
    {
        $this->forAll(
            Generator\constant(null)
        )
        ->withMaxSize(100)
        ->then(function ($_) {
            $secretNumber = generateSecretNumber();
            
            // Must be exactly 3 characters
            $this->assertSame(3, strlen($secretNumber), "Secret number must be exactly 3 digits");
            
            // Must contain only digits
            $this->assertMatchesRegularExpression('/^\d{3}$/', $secretNumber, "Secret number must contain only digits");
            
            // First digit must not be zero
            $this->assertNotEquals('0', $secretNumber[0], "First digit must not be zero");
            
            // All digits must be unique
            $digits = str_split($secretNumber);
            $uniqueDigits = array_unique($digits);
            $this->assertCount(3, $uniqueDigits, "All digits must be unique (no repeating digits)");
        });
    }

    /**
     * Feature: composer-cold-hot-game, Property 2: Input Validation Correctness
     * Validates: Requirements 2.1
     * 
     * For any input string, the validation function SHALL return true if and only if 
     * the string consists of exactly three digit characters.
     */
    public function testValidateGuessProperty(): void
    {
        // Test valid three-digit strings
        $this->forAll(
            Generator\choose(0, 9),
            Generator\choose(0, 9),
            Generator\choose(0, 9)
        )
        ->withMaxSize(100)
        ->then(function (int $d1, int $d2, int $d3) {
            $validGuess = "{$d1}{$d2}{$d3}";
            $this->assertTrue(
                validateGuess($validGuess),
                "Three-digit string '{$validGuess}' should be valid"
            );
        });

        // Test invalid strings (non-digits)
        $this->forAll(
            Generator\string()
        )
        ->withMaxSize(100)
        ->then(function (string $input) {
            // Skip if it happens to be a valid 3-digit string
            if (preg_match('/^\d{3}$/', $input)) {
                return;
            }
            $this->assertFalse(
                validateGuess($input),
                "Non-three-digit string '{$input}' should be invalid"
            );
        });
    }

    /**
     * Feature: composer-cold-hot-game, Property 3: Hint Generation Correctness
     * Validates: Requirements 2.4, 3.1, 3.2, 3.3
     * 
     * For any valid secret number and valid guess, the hint generation function SHALL 
     * produce exactly three hints where:
     * - Each digit in the guess that matches a digit in the secret at the same position produces "Горячо"
     * - Each digit in the guess that matches a digit in the secret at a different position produces "Тепло"
     * - Each digit in the guess that does not match any digit in the secret produces "Холодно"
     */
    public function testGenerateHintsProperty(): void
    {
        $this->forAll(
            Generator\constant(null)
        )
        ->withMaxSize(100)
        ->then(function ($_) {
            // Generate a valid secret number
            $secret = generateSecretNumber();
            
            // Generate a valid guess (any 3 digits)
            $guess = sprintf('%03d', rand(0, 999));
            
            $hints = generateHints($secret, $guess);
            
            // Must produce exactly 3 hints
            $this->assertCount(3, $hints, "Must produce exactly 3 hints");
            
            // Verify each hint is correct
            for ($i = 0; $i < 3; $i++) {
                $guessDigit = $guess[$i];
                $expectedHint = null;
                
                if ($guessDigit === $secret[$i]) {
                    $expectedHint = 'Горячо';
                } elseif (str_contains($secret, $guessDigit)) {
                    $expectedHint = 'Тепло';
                } else {
                    $expectedHint = 'Холодно';
                }
                
                $this->assertSame(
                    $expectedHint,
                    $hints[$i],
                    "Hint for position {$i} should be '{$expectedHint}' (secret={$secret}, guess={$guess})"
                );
            }
        });
    }

    /**
     * Feature: composer-cold-hot-game, Property 4: Alphabetical Hint Sorting
     * Validates: Requirements 3.4
     * 
     * For any array of hints, the sorting function SHALL return the hints in 
     * alphabetical order (Горячо < Тепло < Холодно in Russian alphabetical order).
     */
    public function testSortHintsProperty(): void
    {
        $validHints = ['Горячо', 'Тепло', 'Холодно'];
        
        $this->forAll(
            Generator\elements($validHints),
            Generator\elements($validHints),
            Generator\elements($validHints)
        )
        ->withMaxSize(100)
        ->then(function (string $h1, string $h2, string $h3) {
            $hints = [$h1, $h2, $h3];
            $sorted = sortHints($hints);
            
            // Must have same count
            $this->assertCount(3, $sorted, "Sorted array must have same count");
            
            // Must contain same elements
            sort($hints);
            $sortedCopy = $sorted;
            sort($sortedCopy);
            $this->assertSame($hints, $sortedCopy, "Sorted array must contain same elements");
            
            // Must be in correct order: Горячо < Тепло < Холодно
            $order = ['Горячо' => 1, 'Тепло' => 2, 'Холодно' => 3];
            for ($i = 0; $i < count($sorted) - 1; $i++) {
                $this->assertLessThanOrEqual(
                    $order[$sorted[$i + 1]],
                    $order[$sorted[$i]],
                    "Hints must be sorted: Горячо < Тепло < Холодно"
                );
            }
        });
    }
}
