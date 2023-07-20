<?php

namespace App\Controller\Admin;

use App\Entity\Products;
use App\Form\ProductsFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/produits', name: 'admin_products_')]
class ProductsController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
//
        return $this->render('admin/products/index.html.twig');
    }

    #[Route('/ajout', name: 'add')]
    public function add(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product = new Products();
        $productForm = $this->createForm(ProductsFormType::class, $product);
        $productForm->handleRequest($request);
        if($productForm->isSubmitted()&&$productForm->isValid()){
            $slug=$slugger->slug($product->getName());
            $product->setSlug($slug);
            $price = $product->getPrice()*100;
            $product->setPrice($price);
            $entityManager->persist($product);
            $entityManager->flush();
            $this->addFlash('success', 'produit ajouté avec succès');
            return $this->redirectToRoute('admin_products_index');
        }
        return $this->render('admin/products/add.html.twig',[
            'productForm'=>$productForm->createView()
        ]);
    }

    #[Route('/edition/{id}', name: 'edit')]
    public function edit(Products $product, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_EDIT', $product);
        $price = $product->getPrice()/100;
        $product->setPrice($price);
        $productForm = $this->createForm(ProductsFormType::class, $product);
        $productForm->handleRequest($request);
        if($productForm->isSubmitted()&&$productForm->isValid()){
            $slug=$slugger->slug($product->getName());
            $product->setSlug($slug);
            $price = $product->getPrice()*100;
            $product->setPrice($price);
            $entityManager->persist($product);
            $entityManager->flush();
            $this->addFlash('success', 'produit modifié avec succès');
            return $this->redirectToRoute('admin_products_index');
        }
        return $this->render('admin/products/edit.html.twig',[
            'productForm'=>$productForm->createView()
        ]);

    }

    #[Route('/suppression/{id}', name: 'delete')]
    public function delete(Products $product): Response
    {
        $this->denyAccessUnlessGranted('PRODUCT_DELETE', $product);
        return $this->render('admin/products/index.html.twig');
    }
}