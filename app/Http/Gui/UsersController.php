<?php namespace Zeropingheroes\Lanager\Http\Gui;

use Zeropingheroes\Lanager\Domain\Users\UserService;
use View;
use Redirect;
use Auth;
use OAuth;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\Session;
use Zeropingheroes\Lanager\Domain\OAuth\DiscordOAuthService;
use OAuth\OAuth2\Service\BattleNet;
use OAuth\Common\Http\Uri\Uri;
use Notification;
use Input;
use Config;
use Zeropingheroes\Lanager\Domain\UserOAuths\UserOAuth;

class UsersController extends ResourceServiceController {

	/**
	 * Set the controller's service
	 */
	public function __construct()
	{
		$this->service = new UserService;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$users = $this->service->all();

		return View::make('users.index')
					->with('title','Users')
					->with('users', $users);
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$user = $this->service->single( $id );

		return View::make('users.show')
					->with('title',$user->username)
					->with('user',$user);
	}

	public function linkservice($service) {
		if (Auth::User() != null ) {
			$user = $this->service->single( Auth::User()->id );
			switch($service) {
				case "Discord":
				case "discord":
					return $this->linkToDiscord($user);
					break;
				case "Battle.Net":
				case "battlenet":
					return $this->linkToBattlenet($user);
					break;
				default:
					Notification::danger('Unable to link your profile with the requested service');
					break;
			}	
		} else {
			Notification::danger('Unable to link for a profile which is not your own');
		}
		return Redirect::route('users.show',['id' => 'me']);
	}

	protected function linkToBattlenet($user) {
		if (Config::get('lanager/battlenet.id',"") == "") 
			return Redirect::route('users.show',['id' => 'me']);
		$storage = new Session();
		$credentials= new Credentials (Config::get('lanager/battlenet.id'), Config::get('lanager/battlenet.secret'), url()."/users/link/battlenet");
		$serviceFactory = new \OAuth\ServiceFactory();
		$battlenet = $serviceFactory->createService('Battlenet', $credentials, $storage, array(),new Uri(BattleNet::API_URI_EU));
		if (Input::get('code',null) == null) {
			return Redirect::to((string)$battlenet->getAuthorizationUri());
		} else {
			$code = Input::get('code',null);
			// This was a callback request from facebook, get the token
			$token = $battlenet->requestAccessToken( $code );
			//
			// // Send a request with it
			$result = json_decode( $battlenet->request('/account/user') );
			$model=$user->OAuths('Battle.Net')->get();
			if (count($model)) {
				//We are only interested in the first as there shouldn't be more than 1!
				$model = $model[0];
			} else {
			// Create a new OAuthModel to store the service in
				$model = new UserOAuth();
			}

			//Fill in the model
			$model->service = 'Battle.Net';
			$model->user_id = $user->id;
			$model->service_id = $result->id;
			$model->username = $result->battletag;
			$model->avatar = null;
			$model->token = $token->getAccessToken();
			$model->refreshtoken = $token->getRefreshToken();
			$model->tokenexpires = date("Y-m-d H:i:s",$token->getEndOfLife());

			//Save the model
			$model->save();
			return Redirect::route('users.show',['id' => 'me']);
		}
	}


	protected function linkToDiscord($user) {
		if (Config::get('lanager/discord.id',"") == "") 
			return Redirect::route('users.show',['id' => 'me']);
		$storage = new Session();
		$credentials= new Credentials (Config::get('lanager/discord.id'), Config::get('lanager/discord.secret'), url()."/users/link/discord");
		$serviceFactory = new \OAuth\ServiceFactory();
		$serviceFactory->registerService("Discord","Zeropingheroes\Lanager\Domain\OAuth\DiscordOAuthService");
		$discord = $serviceFactory->createService('Discord', $credentials, $storage, array(DiscordOAuthService::SCOPE_IDENTIFY,DiscordOAuthService::SCOPE_INVITE));
		if (Input::get('code',null) == null) {
			return Redirect::to((string)$discord->getAuthorizationUri());
		} else {
			$code = Input::get('code',null);
			// This was a callback request from facebook, get the token
			$token = $discord->requestAccessToken( $code );
			// // Send a request with it
			// Get the user object
			$result = json_decode( $discord->request( '/users/@me' ), true );
			//Check if we have an existing discord UserOAuth (if so update);
			$model=$user->OAuths('Discord')->get();
			if (count($model)) {
				//We are only interested in the first as there shouldn't be more than 1!
				$model = $model[0];
			} else {
			// Create a new OAuthModel to store the service in
				$model = new UserOAuth();
			}

			//Fill in the model
			$model->service = 'Discord';
			$model->user_id = $user->id;
			$model->service_id = $result['id'];
			$model->username = $result['username']."#".$result['discriminator'];
			$model->avatar = "https://cdn.discordapp.com/avatars/".$result['id']."/".$result['avatar'].".png";
			$model->token = $token->getAccessToken();
			$model->refreshtoken = $token->getRefreshToken();
			$model->tokenexpires = date("Y-m-d H:i:s",$token->getEndOfLife());

			//Save the model
			$model->save();

			try {
				$result = json_decode( $discord->request('/invites/'.Config::get('lanager/discord.invite'), 'POST'), true);
			} catch (Exception $ex) {};

			return Redirect::route('users.show',['id' => 'me']);

		}

	}

}
