<?php

namespace TS\Bundle\MinesweeperBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use TS\Bundle\MinesweeperBundle\Entity\Lobby;
use TS\Bundle\MinesweeperBundle\Service\Symbols;

class LoadLobbyData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $lobby = new Lobby();

        $lobby->addChatLine(Symbols::CHAT_INFO, 'You are on Example Lobby');

        $manager->persist($lobby);
        $manager->flush();
    }

    public function getOrder()
    {
        return 3;
    }
}
