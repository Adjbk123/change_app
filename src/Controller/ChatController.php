<?php

namespace App\Controller;

use App\Entity\Discussion;
use App\Entity\Message;
use App\Repository\DiscussionRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\FcmNotificationService;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/discussions', name: 'chat_discussions', methods: ['GET'])]
    public function discussions(DiscussionRepository $discussionRepository, \App\Repository\MessageRepository $messageRepository): JsonResponse
    {
        $user = $this->getUser();
        $discussions = $discussionRepository->createQueryBuilder('d')
            ->where('d.user1 = :user OR d.user2 = :user')
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()->getResult();
        $data = [];
        foreach ($discussions as $d) {
            $other = ($d->getUser1() === $user) ? $d->getUser2() : $d->getUser1();
            $unread = $messageRepository->countUnreadForUserAndDiscussion($user, $d);
            $data[] = [
                'id' => $d->getId(),
                'other' => $other ? $other->getNomComplet() : '',
                'avatar' => $other && $other->getAvatar() ? $other->getAvatar() : null,
                'lastMessage' => $d->getMessages()->last() ? $d->getMessages()->last()->getContenu() : '',
                'lastDate' => $d->getMessages()->last() ? $d->getMessages()->last()->getCreatedAt()->format('c') : '',
                'unread' => (int)$unread,
            ];
        }
        return $this->json($data);
    }

    #[Route('/discussion/{id}/messages', name: 'chat_messages', methods: ['GET'])]
    public function messages(Discussion $discussion, MessageRepository $messageRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        // Marquer comme lus tous les messages non lus qui ne sont pas de l'utilisateur
        $messagesToMark = $messageRepository->createQueryBuilder('m')
            ->where('m.discussion = :discussion')
            ->andWhere('m.auteur != :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('discussion', $discussion)
            ->setParameter('user', $user)
            ->getQuery()->getResult();
        $now = new \DateTimeImmutable();
        foreach ($messagesToMark as $msg) {
            $msg->setReadAt($now);
            $em->persist($msg);
        }
        if (count($messagesToMark) > 0) {
            $em->flush();
        }
        $messages = $messageRepository->findBy(['discussion' => $discussion], ['createdAt' => 'ASC']);
        $data = [];
        foreach ($messages as $m) {
            $data[] = [
                'id' => $m->getId(),
                'auteur' => $m->getAuteur() ? $m->getAuteur()->getNomComplet() : '',
                'contenu' => $m->getContenu(),
                'type' => $m->getType(),
                'createdAt' => $m->getCreatedAt()->format('c'),
                'isMe' => $user && $m->getAuteur() && $m->getAuteur()->getId() === $user->getId(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/discussion/{id}/message', name: 'chat_send_message', methods: ['POST'])]
    public function sendMessage(Discussion $discussion, Request $request, EntityManagerInterface $em, FcmNotificationService $fcmService): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $contenu = $data['contenu'] ?? '';
        $type = $data['type'] ?? 'texte';
        if (!$contenu) {
            return $this->json(['error' => 'Message vide'], 400);
        }
        $message = new Message();
        $message->setDiscussion($discussion);
        $message->setAuteur($user);
        $message->setContenu($contenu);
        $message->setType($type);
        $em->persist($message);
        $em->flush();

        // Déterminer le destinataire (l'autre utilisateur de la discussion)
        $destinataire = ($discussion->getUser1() && $discussion->getUser1()->getId() !== $user->getId()) ? $discussion->getUser1() : $discussion->getUser2();
        $fcmResult = null;
        if ($destinataire && $destinataire->getPushToken()) {
            // On capture le résultat de l'envoi FCM pour le log
            $fcmResult = $fcmService->sendPush(
                $destinataire->getPushToken(),
                'Nouveau message',
                'Vous avez reçu un message de ' . $user->getNomComplet(),
                ['discussionId' => $discussion->getId()]
            );
        }

        return $this->json([
            'success' => true,
            'fcm' => [
                'sent' => $fcmResult['sent'] ?? null,
                'status' => $fcmResult['status'] ?? null,
                'response' => $fcmResult['response'] ?? null,
                'error' => $fcmResult['error'] ?? null,
                'destinataire' => $destinataire ? $destinataire->getNomComplet() : null,
                'pushToken' => $destinataire ? $destinataire->getPushToken() : null
            ]
        ]);
    }

    #[Route('/utilisateurs', name: 'chat_utilisateurs', methods: ['GET'])]
    public function utilisateurs(UserRepository $userRepository, DiscussionRepository $discussionRepository): JsonResponse
    {
        $user = $this->getUser();
        $agence = method_exists($user, 'getAgence') ? $user->getAgence() : null;
        if (!$agence) {
            return $this->json([]);
        }
        // Récupérer les discussions existantes
        $discussions = $discussionRepository->createQueryBuilder('d')
            ->where('d.user1 = :user OR d.user2 = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();
        $already = [];
        foreach ($discussions as $d) {
            $other = ($d->getUser1() === $user) ? $d->getUser2() : $d->getUser1();
            if ($other) {
                $already[] = $other->getId();
            }
        }
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.agence = :agence')
            ->andWhere('u != :me')
            ->setParameter('agence', $agence)
            ->setParameter('me', $user)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()->getResult();
        $data = [];
        foreach ($users as $u) {
            if (!in_array($u->getId(), $already)) {
                $data[] = [
                    'id' => $u->getId(),
                    'nom' => $u->getNomComplet(),
                    'avatar' => $u->getAvatar() ? $u->getAvatar() : null,
                ];
            }
        }
        return $this->json($data);
    }

    #[Route('/discussion', name: 'chat_create_discussion', methods: ['POST'])]
    public function createDiscussion(Request $request, UserRepository $userRepository, DiscussionRepository $discussionRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $otherId = $data['user_id'] ?? null;
        if (!$otherId) {
            return $this->json(['error' => 'Utilisateur manquant'], 400);
        }
        $other = $userRepository->find($otherId);
        if (!$other) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        // Vérifier même agence
        if (method_exists($user, 'getAgence') && $user->getAgence() !== $other->getAgence()) {
            return $this->json(['error' => 'Pas la même agence'], 403);
        }
        // Vérifier si discussion existe déjà
        $discussion = $discussionRepository->createQueryBuilder('d')
            ->where('(d.user1 = :u1 AND d.user2 = :u2) OR (d.user1 = :u2 AND d.user2 = :u1)')
            ->setParameter('u1', $user)
            ->setParameter('u2', $other)
            ->getQuery()->getOneOrNullResult();
        if (!$discussion) {
            $discussion = new Discussion();
            $discussion->setUser1($user);
            $discussion->setUser2($other);
            $em->persist($discussion);
            $em->flush();
        }
        return $this->json(['id' => $discussion->getId()]);
    }

}
