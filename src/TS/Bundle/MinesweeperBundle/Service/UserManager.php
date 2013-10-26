<?php

namespace TS\Bundle\MinesweeperBundle\Service;

use Doctrine\ORM\EntityManager;
use TS\Bundle\MinesweeperBundle\Entity\Game;
use TS\Bundle\MinesweeperBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use TS\Bundle\MinesweeperBundle\Exception\UserNotFoundException;

class UserManager
{
    /**
     * @var EntityRepository
     */
    private $userRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityRepository $userRepository, EntityManager $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    public function getAllUsersInfo()
    {
        $users = $this->userRepository->findAll();

        return array_map(
            function ($user) {
                return $this->processUserInfo($user);
            },
            $users
        );
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getUserInfo($userId)
    {
        $user = $this->getUser($userId);

        return $this->processUserInfo($user);
    }

    public function getUserGames($userId)
    {
        $user = $this->getUser($userId);

        $games = array_map(function (Game $game) {
            $players = array();

            /** @var User $player */
            foreach ($game->getPlayers() as $player) {
                $players[] = array(
                    'id' => $player->getId(),
                    'name' => $player->getName(),
                    'username' => $player->getUsername()
                );
            }

            return array(
                'id'           => $game->getId(),
                'players'      => $players,
                'activePlayer' => $game->getActivePlayer(),
                'scores'       => $game->getScores(),
                'board'        => $game->getVisibleBoard(),
                'chat'         => $game->getChat()
            );
        }, $user->getGames()->toArray());

        return $games;
    }

    /**
     * @param $userId
     *
     * @return User
     *
     * @throws \TS\Bundle\MinesweeperBundle\Exception\UserNotFoundException
     */
    private function getUser($userId)
    {
        throw new UserNotFoundException(sprintf('User %s not found'), $userId);
    }

    /**
     * @param User $user
     *
     * @return array
     */
    private function processUserInfo(User $user)
    {
        $active = array();
        $won = array();
        $lost = array();

        /** @var Game $game */
        foreach ($user->getGames() as $game) {
            if ($game->isOver()) {
                if ($game->getWinner()->getId() === $user->getId()) {
                    $won[] = $this->getGameInfo($game);
                } else {
                    $lost[] = $this->getGameInfo($game);
                }
            } else {
                $active[] = $this->getGameInfo($game);
            }
        }

        return array(
            'id'       => $user->getId(),
            'username' => $user->getUsername(),
            'name'     => $user->getName(),
            'games'    => array(
                'active' => $active,
                'won'    => $won,
                'lost'   => $lost,
            )
        );
    }

    private function getGameInfo(Game $game)
    {
        return array(
            'id'     => $game->getId(),
            'scores' => $game->getScores(),
        );
    }
}
