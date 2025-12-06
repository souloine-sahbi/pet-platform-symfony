<?php

namespace App\Tests\Controller;

use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SessionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $sessionRepository;
    private string $path = '/session/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->sessionRepository = $this->manager->getRepository(Session::class);

        foreach ($this->sessionRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Session index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'session[dateDebut]' => 'Testing',
            'session[dateFin]' => 'Testing',
            'session[statut]' => 'Testing',
            'session[description]' => 'Testing',
            'session[demande]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->sessionRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Session();
        $fixture->setDateDebut('My Title');
        $fixture->setDateFin('My Title');
        $fixture->setStatut('My Title');
        $fixture->setDescription('My Title');
        $fixture->setDemande('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Session');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Session();
        $fixture->setDateDebut('Value');
        $fixture->setDateFin('Value');
        $fixture->setStatut('Value');
        $fixture->setDescription('Value');
        $fixture->setDemande('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'session[dateDebut]' => 'Something New',
            'session[dateFin]' => 'Something New',
            'session[statut]' => 'Something New',
            'session[description]' => 'Something New',
            'session[demande]' => 'Something New',
        ]);

        self::assertResponseRedirects('/session/');

        $fixture = $this->sessionRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDateDebut());
        self::assertSame('Something New', $fixture[0]->getDateFin());
        self::assertSame('Something New', $fixture[0]->getStatut());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getDemande());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Session();
        $fixture->setDateDebut('Value');
        $fixture->setDateFin('Value');
        $fixture->setStatut('Value');
        $fixture->setDescription('Value');
        $fixture->setDemande('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/session/');
        self::assertSame(0, $this->sessionRepository->count([]));
    }
}
