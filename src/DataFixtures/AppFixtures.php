<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
         $products = [
            [
                'name' => 'Kit d\'hygiène recyclable',
                'shortDescription' => 'Pour une salle de bain éco-friendly',
                'fullDescription' => "Un ensemble pensé pour réduire les déchets du quotidien tout en conservant un confort optimal.
                 Ce kit d’hygiène recyclable réunit des accessoires durables, fabriqués à partir de matériaux responsables et réutilisables.
                 Chaque élément a été sélectionné pour accompagner une routine plus respectueuse de l’environnement, sans compromis sur l’efficacité.
                 Idéal pour débuter une transition vers une salle de bain zéro déchet.",
                'price' => 24.99,
                'picture' => 'kit-hygiene-recyclable.webp',
            ],
            [
                'name' => 'Shot Tropical',
                'shortDescription' => 'Fruits frais, pressés à froid',
                'fullDescription' => "Un concentré d’énergie naturelle, élaboré à partir de fruits frais pressés à froid pour préserver toutes leurs vitamines.
                 Ce shot tropical offre un équilibre parfait entre douceur et acidité, pour un boost immédiat et sain.
                 Sans additifs ni sucres ajoutés, il accompagne idéalement une routine bien-être ou un moment de fraîcheur à tout moment de la journée.",
                'price' => 4.50,
                'picture' => 'shot-tropical.webp',
            ],
            [
                'name' => 'Gourde en bois',
                'shortDescription' => '50cl, bois d’olivier',
                'fullDescription' => "Une gourde élégante et durable, façonnée en bois d’olivier issu de forêts gérées de manière responsable.
                 Sa contenance de 50cl en fait un compagnon idéal pour les déplacements quotidiens.
                 Naturellement isolante, elle conserve la fraîcheur de vos boissons tout en réduisant l’usage du plastique.
                 Un accessoire esthétique, pratique et engagé.",
                'price' => 16.90,
                'picture' => 'gourde-bois.webp',
            ],
            [
                'name' => 'Disques Démaquillants x3',
                'shortDescription' => 'Solution efficace pour vous démaquiller en douceur',
                'fullDescription' => "Ces disques démaquillants réutilisables sont conçus pour nettoyer la peau en douceur, même les zones sensibles.
                 Fabriqués à partir de fibres naturelles, ils remplacent efficacement les cotons jetables et réduisent considérablement les déchets.
                 Lavables et durables, ils conservent leur douceur lavage après lavage.
                 Une alternative écologique pour une routine beauté plus responsable.",
                'price' => 19.90,
                'picture' => 'disques-demaquillants-3.webp',
            ],
            [
                'name' => 'Bougie Lavande & Patchouli',
                'shortDescription' => 'Cire naturelle',
                'fullDescription' => "Une bougie artisanale composée de cire naturelle, parfumée aux huiles essentielles de lavande et de patchouli.
                 Elle diffuse une atmosphère apaisante, idéale pour créer un moment de détente ou accompagner une séance de relaxation.
                 Sa combustion propre limite les émissions toxiques et respecte votre intérieur.
                 Un objet décoratif et sensoriel, pensé pour le bien-être.",
                'price' => 32.00,
                'picture' => 'bougie-lavande-patchouli.webp',
            ],
            [
                'name' => 'Brosse à dent',
                'shortDescription' => 'Bois de hêtre rouge issu de forêts gérées durablement',
                'fullDescription' => "Une brosse à dents écologique fabriquée en bois de hêtre rouge certifié, provenant de forêts gérées durablement.
                 Ses poils doux assurent un brossage efficace tout en respectant les gencives.
                 Légère et ergonomique, elle constitue une alternative naturelle aux brosses à dents en plastique.
                 Un geste simple pour réduire son impact environnemental au quotidien.",
                'price' => 5.40,
                'picture' => 'brosse-a-dent.webp',
            ],
            [
                'name' => 'Kit couvert en bois',
                'shortDescription' => 'Revêtement Bio en olivier & sac de transport',
                'fullDescription' => "Un kit de couverts réutilisables en bois d’olivier, accompagné d’un sac de transport en tissu naturel.
                 Parfait pour les repas nomades, les pique-niques ou les pauses déjeuner zéro déchet.
                 Chaque pièce est résistante, légère et agréable en main.
                 Une solution durable pour remplacer les couverts jetables.",
                'price' => 12.30,
                'picture' => 'kit-couvert-bois.webp',
            ],
            [
                'name' => 'Nécessaire, déodorant Bio',
                'shortDescription' => '50ml déodorant à l’eucalyptus',
                'fullDescription' => "Déodorant Nécessaire, une formule révolutionnaire composée exclusivement d'ingrédients naturels pour une protection efficace et bienfaisante.
                 Chaque flacon de 50 ml renferme le secret d'une fraîcheur longue durée, sans compromettre votre bien-être ni l'environnement.
                 Conçu avec soin, ce déodorant allie le pouvoir antibactérien des extraits de plantes aux vertus apaisantes des huiles essentielles, assurant une sensation de confort toute la journée.
                 Grâce à sa formule non irritante et respectueuse de votre peau, Nécessaire offre une alternative saine aux déodorants conventionnels, tout en préservant l'équilibre naturel de votre corps.",
                'price' => 8.50,
                'picture' => 'necessaire-deodorant-bio.webp',
            ],
            [
                'name' => 'Savon Bio',
                'shortDescription' => 'Thé, Orange & Girofle',
                'fullDescription' => "Un savon biologique aux notes chaudes et épicées de thé, d’orange et de girofle.
                 Sa mousse onctueuse nettoie la peau en douceur tout en laissant un parfum naturel et réconfortant.
                 Formulé sans ingrédients controversés, il respecte l’équilibre cutané et convient à un usage quotidien.
                 Un soin sensoriel et authentique pour une routine plus naturelle.",
                'price' => 18.90,
                'picture' => 'savon-bio.webp',
            ],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setShortDescription($data['shortDescription']);
            $product->setFullDescription($data['fullDescription']);
            $product->setPrice($data['price']);
            $product->setPicture($data['picture']);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
