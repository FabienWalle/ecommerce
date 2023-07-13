<?php

namespace App\Security\Voter;

use App\Entity\Products;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductsVoter extends Voter

{
    const EDIT = 'PRODUCT_EDIT';
    const DELETE = 'PRODUCT_DELETE';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security=$security;
    }

    protected function supports(string $attribute,$product): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE]) && $product instanceof Products;
        // si $attribute est EDIT ou DELETE, et que $product est un produit, alors true
    }

    protected function voteOnAttribute(string $attribute, $product, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if(!$user instanceof UserInterface) return false;
        if($this->security->isGranted('ROLE_ADMIN')) return true;
        switch($attribute){
            case self::EDIT:
                return $this->canEdit();
                break;
            case self::DELETE:
                return $this->canDelete();
                break;
        }

    }

    private function canEdit(): bool
    {
        return $this->security->isGranted('ROLE_PRODUCT_ADMIN');
    }

    private function canDelete(): bool
    {
        return $this->security->isGranted('ROLE_PRODUCT_ADMIN');
    }
}