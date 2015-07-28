<?php

namespace eDemy\FbBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use Facebook as FB;

use eDemy\FbBundle\Entity\FacebookObject;

class pCommand extends ContainerAwareCommand
{
    private $client;
    private $domain;
    private $likes;
    private $output, $input;
    private $p_i;
    private $session;
    private $access_token;

    protected function configure()
    {
        $this
            ->setName('edemy:fb:p')
            ->setDescription('Show Facebook p')
            //->addArgument('p_i', InputArgument::REQUIRED, 'Page ID?')
            ->addOption('page', null, InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->page = $this->input->getOption('page');
        $this->param = $this->getContainer()->get('edemy.param');
        $a_i = $this->param->getParam('a.i','eDemyFbBundle');
        $a_s = $this->param->getParam('a.s','eDemyFbBundle');
        $u_i = $this->param->getParam('u.i','eDemyFbBundle');
        $p_i = $this->param->getParam('p.i','eDemyFbBundle');
        $p_t = $this->param->getParam('p.t','eDemyFbBundle');
        $this->getSession($a_i, $a_s, $p_t);
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entity = $em->getRepository('eDemyFbBundle:FacebookObject')->findOneBy(array(
            'name' => 'servicios',
            'pageId' => $p_i,
        ));
        $image = $this->getContainer()->get('router')->generate('edemy_crawler_image', array(), true);
        if (!$entity) {
            $photo_id = $this->uploadPhoto($p_i, $image, null);
            $entity = new FacebookObject();
            $entity->setPageId($p_i);
            $entity->setName('servicios');
            $entity->setObjectId($photo_id);
            $em->persist($entity);
            $em->flush();
        } else {
            $this->deletePhoto($entity->getPageId(), $entity->getObjectId());
            $photo_id = $this->uploadPhoto($p_i, $image, null);
            $entity->setPageId($p_i);
            $entity->setName('servicios');
            $entity->setObjectId($photo_id);
            $em->flush();
        }
    }

    public function getSession($a_i, $a_s, $p_t) {
        FB\FacebookSession::setDefaultApplication($a_i, $a_s);
        $this->session = new FB\FacebookSession($p_t);
        //$this->session->validate();
    }

    public function uploadPhoto($p_i, $url, $msg) {
        if($this->session) {
            try {
                $response = (new FB\FacebookRequest(
                    $this->session, 'POST', '/' . $p_i . '/photos', array(
                        'url' 		=> $url,
                        'message' 	=> $msg,
                    )
                ))->execute()->getGraphObject();

                return $response->getProperty('id');
            } catch(FacebookRequestException $e) {
                echo "Exception occured, code: " . $e->getCode();
                echo " with message: " . $e->getMessage();
            }
        }
    }

    public function deletePhoto($p_i, $photo_id) {
        $request = new FB\FacebookRequest(
            $this->session,
            'DELETE',
            '/' . $photo_id
        );
        $response = $request->execute();
        $graphObject = $response->getGraphObject();
    }

    public function post($p_i, $msg) {
        $fb_request = new FB\FacebookRequest( $this->session, 'POST', '/'. $p_i .'/feed', array(
            'message' => $msg,
        ) );

        $page_post = $fb_request->execute()->getGraphObject()->asArray();

        return $page_post;
    }

    function SpanishDate($FechaStamp)
    {
        $a = date('Y',$FechaStamp);
        $m = date('n',$FechaStamp);
        $d = date('j',$FechaStamp);
        $diasemana = date('w',$FechaStamp);
        $diassemanaN= array("Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado");
        $mesesN=array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");

        return $diassemanaN[$diasemana]." $d de ". $mesesN[$m];
    }
}
