<?php

namespace TS\Bundle\MinesweeperBundle\Service;

class BoardFactory
{
    /**
     * @param int $size
     * @param int $mines
     *
     * @return array
     */
    public static function create($size, $mines)
    {
        $board = array();
        for ($i = 0; $i < $size; $i++) {
            for ($j = 0; $j < $size; $j++) {
                $board[$i][$j] = 0;
            }
        }

        // Populate mines
        foreach (range(1, $mines) as $mine) {
            do {
                $row = mt_rand(0, $size - 1);
                $col = mt_rand(0, $size - 1);
            } while ($board[$row][$col] === Symbols::MINE);

            $board[$row][$col] = Symbols::MINE;
        }

        // Calculate cell numbers
        foreach ($board as $row => $rowCells) {
            foreach ($rowCells as $col => $value) {
                if (Symbols::MINE !== $value) {
                    $board[$row][$col] = static::borderingMines($board, $row, $col);
                }
            }
        }

        return $board;
    }

    /**
     * @param array $board
     * @param int $row
     * @param int $col
     *
     * @return int
     */
    private static function borderingMines(array $board, $row, $col)
    {
        $mines = 0;

        for ($i = $row - 1; $i <= $row + 1; $i++) {
            for ($j = $col - 1; $j <= $col + 1; $j++) {
                if (isset($board[$i][$j]) && Symbols::MINE === $board[$i][$j]) {
                    $mines++;
                }
            }
        }

        return $mines;
    }
}
