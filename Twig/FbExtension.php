<?php

namespace eDemy\FbBundle\Twig;

//use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FbExtension extends \Twig_Extension
{
    /** @var ContainerInterface $this->container */
    protected $container;
    
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('likes', array($this, 'likesFunction')),
            new \Twig_SimpleFunction('relatedInstagram', array($this, 'relatedInstagramFunction'), array('is_safe' => array('html'), 'pre_escape' => 'html')),
        );
    }

    public function likesFunction($url)
    {
        $url = 'https://www.facebook.com/' . $url;
        // Query in FQL
        $fql  = "SELECT share_count, like_count, comment_count ";
        $fql .= " FROM link_stat WHERE url = '$url'";

        $fqlURL = "https://api.facebook.com/method/fql.query?format=json&query=" . urlencode($fql);

        // Facebook Response is in JSON
        $response = file_get_contents($fqlURL);
        $fb_count = json_decode($response);

        return $fb_count[0]->like_count;
    }

    public function relatedInstagramFunction($entity)
    {
        $content = null;
//        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            $tags = $entity->getTags();
            foreach ($tags as $tag) {
                if (strpos($tag->getName(), "#") !== false) {
                    //@REVISAR INTERESANTE
                    $eDemyFb = $this->container->get('edemy.facebook');
                    $clientId = $eDemyFb->getParam('instagram.clientId');
//                    $userId = $eDemyFb->getParam('instagram.userId');
                    $accessToken = $eDemyFb->getParam('instagram.accessToken');
                    $userId = $this->getInstaID(substr($tag->getName(), 1), $accessToken);

                    $content = $this->container->get('edemy.main')->render(
                        'templates/facebook/instagram',
                        array(
                            'clientId' => $clientId,
                            'userId' => $userId,
                            'accessToken' => $accessToken,
                        )
                    );
                }
//            }

            return $content;
        }
    }


    function getInstaID($username, $accessToken)
    {
        $username = strtolower($username); // sanitization
        $url = "https://api.instagram.com/v1/users/search?q=" . $username . "&access_token=" . $accessToken;
        $get = file_get_contents($url);
        $json = json_decode($get);

        foreach($json->data as $user)
        {
            if($user->username == $username)
            {

                return $user->id;
            }
        }

        return '00000000'; // return this if nothing is found
    }

    public function getName()
    {
        return 'edemy_facebook_extension';
    }
}
