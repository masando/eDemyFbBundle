<?php

namespace eDemy\FbBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

class lkCommand extends ContainerAwareCommand
{
    private $client;
    private $domain;
    private $lks;
    private $output, $input;

    protected function configure()
    {
        $this
            ->setName('edemy:fb:lk')
            ->setDescription('Show Fb Lk')
            ->addArgument('url', InputArgument::REQUIRED, 'What URL do you want to get?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->url = $this->input->getArgument('url');
        $this->lk = $this->facebook_count($this->url);
        $this->output->writeln("<info>" . $this->lk . "</info>");
    }

    function facebook_count($url){
        $url = 'https://www.facebook.com/' . $url;
        $fql  = "SELECT share_count, like_count, comment_count FROM link_stat WHERE url = '$url'";
        $fqlURL = "https://api.facebook.com/method/fql.query?format=json&query=" . urlencode($fql);
        //die(var_dump($fqlURL));
        $response = file_get_contents($fqlURL);
        $fb_count = json_decode($response);

        return $fb_count[0]->like_count;
    }

    function diff($url) {
        $otro = $this->facebook_count($url);
        $this->output->writeln("<info>" . $url[0] . ": " . $otro . " (" . (int) ($otro - $this->ref) . ")" . "</info>");
    }
}
