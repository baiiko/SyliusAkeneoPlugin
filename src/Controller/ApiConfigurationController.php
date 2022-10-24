<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Form\Type\ApiConfigurationType;
use Webmozart\Assert\Assert;

/**
 * @deprecated To be removed in 4.0.
 */
final class ApiConfigurationController extends AbstractController
{
    private const PAGING_SIZE = 1;

    private EntityManagerInterface $entityManager;

    private EntityRepository $apiConfigurationRepository;

    private FactoryInterface $apiConfigurationFactory;

    private TranslatorInterface $translator;

    private ClientFactoryInterface $clientFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $apiConfigurationRepository,
        FactoryInterface $apiConfigurationFactory,
        ClientFactoryInterface $clientFactory,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->apiConfigurationFactory = $apiConfigurationFactory;
        $this->translator = $translator;
        $this->clientFactory = $clientFactory;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            /** @var ApiConfiguration $apiConfiguration */
            $apiConfiguration = $this->apiConfigurationFactory->createNew();
        }

        $form = $this->createForm(ApiConfigurationType::class, $apiConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\Form\SubmitButton $testCredentialsButton */
            $testCredentialsButton = $form->get('testCredentials');

            try {
                Assert::isInstanceOf($apiConfiguration, ApiConfiguration::class);
                $client = $this->clientFactory->authenticateByPassword($apiConfiguration);
                $client->getCategoryApi()->all(self::PAGING_SIZE);

                $this->entityManager->persist($apiConfiguration);

                if (!$testCredentialsButton->isClicked()) {
                    $this->entityManager->flush();
                }

                Assert::isInstanceOf($request->getSession(), Session::class);
                $request->getSession()->getFlashBag()->add('success', $this->translator->trans('akeneo.ui.admin.authentication_successfully_succeeded'));
            } catch (\Throwable $throwable) {
                Assert::isInstanceOf($request->getSession(), Session::class);
                $request->getSession()->getFlashBag()->add('error', $throwable->getMessage());
            }
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/api_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
