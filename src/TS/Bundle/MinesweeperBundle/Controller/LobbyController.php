<?php


namespace TS\Bundle\MinesweeperBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use TS\Bundle\MinesweeperBundle\Entity\User;

/**
 * @Route("/lobby")
 */
class LobbyController extends BaseController
{
    /**
     * @Route("/")
     * @Method("GET")
     */
    public function lobbyInfoAction()
    {
        $lobby = $this->getLobby();
        $userManager = $this->get('ts_minesweeper.user_manager');

        return new JsonResponse(
            array(
                'chat' => $lobby->getChat(),
                'users' => $userManager->getAllUsersInfo()
            ),
            200
        );
    }

    /**
     * @Route("/users/{userId}", requirements={"userId"="\d+"})
     * @Method("PUT")
     */
    public function lobbyJoinAction($userId)
    {
        $user = $this->getUser();
        if ($userId !== $user->getId()) {
            throw new AccessDeniedHttpException('This is not your user Id');
        }

        if (null === $user->getLobby()) {
            $user->setLobby($this->getLobby());
            return new Response('', 201);
        } else {
            return new Response('', 204);
        }
    }

        /**
     * @Route("/chat")
     * @Method("POST")
     */
    public function chatPostAction(Request $request)
    {
        if (!$request->request->has('message')) {
            throw new \HttpRequestException();
        }

        $lobby = $this->getLobby();

        /** @var User $user */
        $user = $this->getUser();
        $username = $user->getUsername();

        $message = $request->request->get('message');

        $lobby->addChatLine($username, $message);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(
            array(
                'chat' => $lobby->getChat(),
            ),
            200
        );
    }

    /**
     * @return \TS\Bundle\MinesweeperBundle\Entity\Lobby
     */
    private function getLobby()
    {
        $lobbies = $this->getDoctrine()->getRepository('TSMinesweeperBundle:Lobby')->findAll();

        return $lobbies[0];
    }
}
