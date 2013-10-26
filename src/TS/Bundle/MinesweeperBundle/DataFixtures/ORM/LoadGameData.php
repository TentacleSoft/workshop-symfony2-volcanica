<?php

namespace TS\Bundle\MinesweeperBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use TS\Bundle\MinesweeperBundle\Entity\Game;
use TS\Bundle\MinesweeperBundle\Service\BoardFactory;
use TS\Bundle\MinesweeperBundle\Service\Symbols;

class LoadGameData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $game = new Game();

        $board = BoardFactory::create(16, 50);
        $visibleBoard = array();
        foreach (range(0, 15) as $row) {
            foreach (range(0, 15) as $col) {
                $visibleBoard[$row][$col] = Symbols::UNKNOWN;
            }
        }

        $game->setBoard($board);
        $game->setVisibleBoard($visibleBoard);

        $game->addChatLine(
            Symbols::CHAT_INFO,
            sprintf('Players: %s, %s', $this->getReference('user1')->getUsername(), $this->getReference('user2')->getUsername())
        );

        $game->setActivePlayer($this->getReference('user1')->getId());
        $game->setScores(array(0, 0));
        $game->addPlayer($this->getReference('user1'))->addPlayer($this->getReference('user2'));

        $manager->persist($game);
        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}
