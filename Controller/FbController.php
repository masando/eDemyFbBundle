<?php

namespace eDemy\FbBundle\Controller;

use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArgvInput;

use Facebook as FB;

use eDemy\MainBundle\Controller\BaseController;
use eDemy\MainBundle\Event\ContentEvent;

class FbController extends BaseController
{
    public static function getSubscribedEvents()
    {
        return self::getSubscriptions('facebook', [], array(
            'edemy_facebook_login'      => array('onFacebookLogin', 0),
            'edemy_meta_module'         => array('onMetaModule', 0),
            'edemy_precontent_module'   => array('onPreContentModule', 0),
            'edemy_facebook_promo'     => array('onPromo', 0),
            'edemy_facebook_privacy'     => array('onFacebookPrivacy', 0),
        ));
    }

    public function likesAction()
    {
        //$input = new ArgvInput(array());
        //$output = new BufferedOutput();
        //$command = $this->get("edemy.getlikes");
        //$command->run($input, $output);
        //return $this->newResponse($output->fetch());
        //die(var_dump($output->fetch()));

        $pages = array();
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);
        $name = 'name';
        $pages[$name]['name'] = $name;
        $pages[$name]['likes'] = $this->facebook_count($name);

        //$pages = uasort($pages, $this->cmp());
        //die(var_dump($pages));
        return $this->newResponse($this->render('templates/fb/likes', array('pages' => $pages)));
    }

    public function cmp($a, $b)
    {
       return $b['likes'] > $a['likes'];
    }

    function facebook_count($url){
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

    public function onFacebookPrivacy(ContentEvent $event)
    {
        $this->addEventModule($event, 'templates/fb/fbPrivacy');
    }

    public function onFacebookLogin(ContentEvent $event)
    {
        FB\FacebookSession::setDefaultApplication('', '');
        $redirectUrl = $this->get('router')->generate('edemy_facebook_login', array(), true);
        $helper = new FB\FacebookRedirectLoginHelper($redirectUrl);
        try {
            if($this->get('session')->get('facebook_token')) {
                $session = new FB\FacebookSession($this->get('session')->get('facebook_token'));
            } else {
                $session = $helper->getSessionFromRedirect();
            }
        } catch(FB\FacebookRequestException $ex) {
        } catch(\Exception $ex) {
        }
        if (isset($session)) {
            $this->get('session')->set('facebook_token', $session->getToken());
            // graph api request for user data
            $request = new FB\FacebookRequest( $session, 'GET', '/me' );
            $response = $request->execute();
            // get response
            $graphObject = $response->getGraphObject();
            $fbid = $graphObject->getProperty('id');              // To Get Facebook ID
            $fbfullname = $graphObject->getProperty('name'); // To Get Facebook full name
            $femail = $graphObject->getProperty('email');    // To Get Facebook email ID
            $_SESSION['FBID'] = $fbid;
            $_SESSION['FULLNAME'] = $fbfullname;
            $_SESSION['EMAIL'] =  $femail;
            //checkuser($fuid,$ffname,$femail);
            //header("Location: index.php");
            $this->addEventModule($event, 'templates/fb/testFb', array(
                'fbfullname' => $fbfullname,
            ));
        } else {
            $event->addModule('<a href="' . $helper->getLoginUrl() . '">Login with Facebook</a>');
        }
    }

    public function onPromo(ContentEvent $event)
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $_route = $request->attributes->get('id');

        $required_scope     = 'public_profile, publish_actions, email'; //Permissions required
        $redirectUrl = $this->get('router')->generate('edemy_facebook_promo', array(), true);

        FB\FacebookSession::setDefaultApplication('', '');
        $helper = new FB\FacebookRedirectLoginHelper($redirectUrl);

        try {
            $session = $helper->getSessionFromRedirect();
        } catch(FacebookRequestException $ex) {
            // When Facebook returns an error
        } catch(Exception $ex) {
            // When validation fails or other local issues
        }

        if (isset($session) ) {
            $user_profile = (new FB\FacebookRequest($session, 'GET', '/me'))->execute()->getGraphObject(FB\GraphUser::className());
            //$graphObject->getProperty("email");
            echo print_r($user_profile);

            $this->addEventModule($event, 'templates/fb/fbPromo', array(
                'id' => $user_profile->getProperty('id'),
                'name' => $user_profile->getProperty('name'),
                'first_name' => $user_profile->getProperty('first_name'),
                'last_name' => $user_profile->getProperty('last_name'),
                'middle_name' => $user_profile->getMiddleName(),
                'link' => $user_profile->getLink(),
                'birthday' => $user_profile->getBirthday(),
                'location' => $user_profile->getLocation(),
                'email' => $user_profile->getEmail(),
                'gender' => $user_profile->getGender(),
//                'verified' => $user_profile->getVerified(),
            ));
        } else {
            $login_url = $helper->getLoginUrl( array( 'scope' => $required_scope ) );
            $this->addEventModule($event, 'templates/fb/fbPromo', array(
                'login_url' => $login_url,
            ));
        }


//            $request = new FB\FacebookRequest($session,'POST','/me/feed', array ('message' => 'hola', 'scope' => 'publish_actions',));
//            $response = $request->execute();
//            $graphObject = $response->getGraphObject();


//        try {
//            if($this->get('session')->get('facebook_token')) {
//                $session = new FB\FacebookSession($this->get('session')->get('facebook_token'));
//            } else {
//                $session = $helper->getSessionFromRedirect();
//            }
//        } catch(FB\FacebookRequestException $ex) {
//        } catch(\Exception $ex) {
//        }
//        if (isset($session)) {
//            $this->get('session')->set('facebook_token', $session->getToken());
//            // graph api request for user data
//            $request = new FB\FacebookRequest( $session, 'GET', '/me' );
//            $response = $request->execute();
//            // get response
//            $graphObject = $response->getGraphObject();
//            $fbid = $graphObject->getProperty('id');              // To Get Facebook ID
//            $fbfullname = $graphObject->getProperty('name'); // To Get Facebook full name
//            $femail = $graphObject->getProperty('email');    // To Get Facebook email ID
//            $_SESSION['FBID'] = $fbid;
//            $_SESSION['FULLNAME'] = $fbfullname;
//            $_SESSION['EMAIL'] =  $femail;
//            //checkuser($fuid,$ffname,$femail);
//            //header("Location: index.php");
//            $this->addEventModule($event, 'templates/testFb', array(
//                'fbfullname' => $fbfullname,
//            ));
//        } else {
//        }
//        $this->addEventModule($event, 'templates/fbSorteoPuraAlegria');
    }

    public function onFacebookLogin2(ContentEvent $event)
    {
        $this->addEventModule($event, 'templates/fb/loginFb');

        return true;
    }

    public function onPreContentModule(ContentEvent $event) {
        if($this->getRoute() != 'edemy_main_frontpage') {
            $likeurl = $this->getParam('likeurl');
            if($likeurl != 'likeurl') {
                $this->addEventModule($event, 'templates/fb/precontentFb', array(
                    'likeurl' => $likeurl,
                ));
            }
        }
        
        return true;
    }

    public function onMetaModule(ContentEvent $event) {
        $pixel_id = $this->getParam('facebook.pixel_id');
        if($pixel_id != 'facebook.pixel_id') {
            $this->addEventModule($event, 'templates/fb/meta_module', array(
                'pixel_id' => $pixel_id,
            ));
        }

        return true;
    }

    public function likes()
    {
        $input = new ArgvInput(array());
        $output = new BufferedOutput();
        $command = $this->get("edemy.get.likes");
        $command->run($input, $output);
        die(var_dump($output->fetch()));
        return $output->fetch();
    }
}
