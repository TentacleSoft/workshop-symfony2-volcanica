<?php

namespace TS\Bundle\MinesweeperBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use TS\Bundle\MinesweeperBundle\Entity\Game;
use TS\Bundle\MinesweeperBundle\Entity\User;
use TS\Bundle\MinesweeperBundle\Exception\GameNotFoundException;

class GameManager
{
    const BOARD_SIZE = 16;
    const MINES = 49;

    /**
     * @var EntityRepository
     */
    private $gameRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityRepository $gameRepository, EntityManager $entityManager)
    {
        $this->gameRepository = $gameRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $gameId
     *
     * @throws GameNotFoundException
     *
     * @return Game
     */
    public function get($gameId)
    {
        $game = $this->gameRepository->findOneById($gameId);

        if (!$game) {
            throw new GameNotFoundException(sprintf('Game %s not found', $gameId));
        }

        return $game;
    }

    /**
     * @param User[] $players
     * @param int|null $activePlayer
     *
     * @return int
     */
    public function create(array $players, $activePlayer = null)
    {
        $game = new Game();

        $scores = array();
        foreach ($players as $player) {
            $game->addPlayer($player);
            $player->addGame($game);
            $this->entityManager->persist($player);
            $scores[] = 0;
        }

        $game->setScores($scores);

        if (null === $activePlayer) {
            $activePlayer = array_rand(array_map(function (User $player) {
                return $player->getId();
            }, $players));
        }
        $game->setActivePlayer($activePlayer);

        $game->setBoard(BoardFactory::create(static::BOARD_SIZE, static::MINES));

        $visibleBoard = array();
        foreach (range(0, 15) as $row) {
            foreach (range(0, 15) as $col) {
                $visibleBoard[$row][$col] = Symbols::UNKNOWN;
            }
        }
        $game->setVisibleBoard($visibleBoard);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $game->getId();
    }

    /**
     * Open cell
     *
     * @param int $gameId
     * @param User $player
     * @param int $row
     * @param int $col
     *
     * @throws \Exception
     */
    public function open($gameId, User $player, $row, $col)
    {
        $game = $this->get($gameId);

        $activePlayer = $game->getActivePlayer();
        if ($player->getId() !== $activePlayer) {
            throw new \Exception(sprintf('User %s is not currently active or game is already over', $activePlayer));
        }

        $players = $game->getPlayers();
        foreach ($players as $pos => $player) {
            if ($player->getId() === $activePlayer) {
                $this->openCell($game, $pos, $row, $col);

                if ($game->isOver()) {
                    $game->setWinner($player);
                }

                $this->entityManager->flush();

                return;
            }
        }

        throw new \Exception('Corrupt game, active player not in players');
    }

    /**
     * @param int $gameId
     * @param User $user
     * @param string $message
     *
     * @return Game
     */
    public function sendUserChat($gameId, User $user, $message)
    {
        $this->sendChat($gameId, $user->getUsername(), $message);
    }

    public function sendSystemChat($gameId, $message, $type)
    {
        $from = Symbols::CHAT_INFO;

        if ($type == 'error') {
            $from = Symbols::CHAT_ERROR;
        }

        $this->sendChat($gameId, $from, $message);
    }

    /**
     * @param int $gameId
     * @param int $from user id or system id
     * @param $message
     */
    private function sendChat($gameId, $from, $message)
    {
        /** @var Game $game */
        $game = $this->get($gameId);

        $game->addChatLine($from, $message);
        $this->entityManager->flush();
    }

    /**
     * @param Game $game
     * @param int $playerPos
     * @param int $row
     * @param int $col
     *
     * @return string|null Symbol opened (if any)
     */
    private function openCell(Game $game, $playerPos, $row, $col)
    {
        $board = $game->getBoard();
        $visibleBoard = $game->getVisibleBoard();

        if (!isset($board[$row][$col]) || $visibleBoard[$row][$col] !== Symbols::UNKNOWN) {
            return null;
        }

        $visibleBoard[$row][$col] = $board[$row][$col];

        if ($board[$row][$col] === Symbols::MINE) {
            $visibleBoard[$row][$col] .= $playerPos;

            $scores = $game->getScores();
            $scores[$playerPos] += 1;

            // End game (no next turn, a player has already won)
            if ($scores[$playerPos] > static::MINES / 2) {
                $game->setActivePlayer(Symbols::GAME_OVER);
            }

            $game->setScores($scores);
        } else {
            $players = $game->getPlayers();
            $nextPlayerPos = ($playerPos + 1) % count($players);

            $game->setActivePlayer($players[$nextPlayerPos]->getId());
        }

        $game->setVisibleBoard($visibleBoard);

        if (0 === $board[$row][$col]) {
            $this->openCell($game, $playerPos, $row - 1, $col - 1);
            $this->openCell($game, $playerPos, $row - 1, $col    );
            $this->openCell($game, $playerPos, $row - 1, $col + 1);
            $this->openCell($game, $playerPos, $row    , $col - 1);
            $this->openCell($game, $playerPos, $row    , $col + 1);
            $this->openCell($game, $playerPos, $row + 1, $col - 1);
            $this->openCell($game, $playerPos, $row + 1, $col    );
            $this->openCell($game, $playerPos, $row + 1, $col + 1);
        }

        return $board[$row][$col];
    }
}
