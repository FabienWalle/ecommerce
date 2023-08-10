<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrdersDetails;
use App\Repository\ProductsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commandes', name: 'app_orders_')]
class OrdersController extends AbstractController
{
    #[Route('/ajout', name: 'add')]
    public function add(SessionInterface $session, ProductsRepository $productsRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $panier = $session->get('panier',[]);
        if($panier === []){
            $this->addFlash('message','Votre panier est vide');
            return $this->redirectToRoute('main');
        }
        $order=new Orders();

        $order->setUsers($this->getUser());
        $order->setReference(uniqid());
        // il faut créer une vraie référence (année, mois, jour, premières lettres du client, etc.)

        foreach ($panier as $item=>$quantity){
            $orderDetails = new OrdersDetails();

            $product = $productsRepository->find($item);
            // il faut vérifier si le produit existe dans la bdd
            $price = $product->getPrice();

            $orderDetails->setProducts($product);
            $orderDetails->setPrice($price);
            $orderDetails->setQuantity($quantity);

            $order->addOrdersDetail($orderDetails);
        }

        $entityManager->persist($order);
        $entityManager->flush();

        $this->addFlash('message','commande créée avec succès');

        $session->remove('panier');

        return $this->redirectToRoute('main');
    }
}

