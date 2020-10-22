<?php


namespace App\Controller;


use App\Entity\Contact;
use App\Entity\Property;
use App\Entity\PropertySearch;
use App\Form\ContactType;
use App\Form\PropertySearchType;
use App\Notification\ContactNotification;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PropertyController extends AbstractController
{
    /**
     * @var PropertyRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(PropertyRepository $repository, EntityManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
    }

    /**
     * @Route("/biens", name="property.index")
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        //Creation d'une entité qui ca représenter notre recherche
        $search = new PropertySearch();
        //Creation de formulaire
        $form = $this->createForm(PropertySearchType::class, $search);
        $form->handleRequest($request);

        //Gérer traitement dans le controleur


        $properties = $paginator->paginate
        (
            $this->repository->findAllVisibleQuery($search),
            $request->query->getInt('page', 1),
            12
        );
        return $this->render('property/index.html.twig',
        [
            'current_menu' => 'properties',
            'properties' => $properties,
            'form' => $form->createView()
            
        ]);
    }

    /**
     * @param Property $property
     * @return Response
     * @Route("/biens/{slug}-{id}", name="property.show", requirements={"slug": "[a-z0-9\-]*"})
     */
    public function show(Property $property, string $slug, Request $request, ContactNotification $notification): Response
    {


        if($property->getSlug() !== $slug)
        {
            return $this->redirectToRoute('property.show',
            [
                'id' => $property->getId(),
                'slug' => $property->getSlug()], 301
            );
        }

        $contact = new Contact();
        $contact->setProperty($property);
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $notification->notify($contact);
            $this->addFlash('success', 'Votre message a bien été envoyé');

            return $this->redirectToRoute('property.show',
                [
                    'id' => $property->getId(),
                    'slug' => $property->getSlug()]
            );

        }

        return $this->render('property/show.html.twig',
        [
            'current_menu'=>    'properties',
            'property'=>        $property,
            'form' =>           $form->createView()
        ]);
    }
}