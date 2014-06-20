<?php namespace Zeropingheroes\Lanager;

use Zeropingheroes\Lanager\Models\User,
	Zeropingheroes\Lanager\Models\Role;
use App, Input, Redirect, View;
use LightOpenID;

class UsersController extends BaseController {

	protected $steamInterface;
	
	public function __construct()
	{
		$this->beforeFilter('checkResourcePermission',array('only' => array('create', 'store', 'edit', 'update', 'destroy') ));
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$users = User::visible()->orderBy('username', 'asc')->paginate(10);
		return View::make('users.list')
					->with('title','People')
					->with('users',$users);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		//
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  object  SteamUser $steamUser
	 * @return Response
	 */
	public function store($steamUser)
	{
		//
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		if( $user = User::visible()->find($id) )
		{
			return View::make('users.show')
						->with('title',$user->username)
						->with('user',$user);
		}
		else
		{
			App::abort(404, 'User not found');
		}
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		User::visible()->destroy($id);
		return Redirect::route('users.index');
	}

	/**
	 * Show the form for editing the specified user's roles.
	 *
	 * @return Redirect
	 */
	public function editRoles($id)
	{
		if( $user = User::visible()->find($id) )
		{
			$roles = Role::all();
			return View::make('users.roles')
							->with('title', $user->username.' - Roles')
							->with('user', $user)
							->with('roles', $roles);
		}
		else
		{
			App::abort(404, 'Page not found');
		}
	}

	/**
	 * Update the specified user's roles in storage.
	 *
	 * @return Redirect
	 */
	public function updateRoles($id)
	{
		if( $user = User::visible()->find($id) )
		{
			$userRoles = (is_array(Input::get('userRoles')) ? Input::get('userRoles') : array() );
			$user->roles()->sync($userRoles);
			return Redirect::route('users.roles.edit',array('user' => $user->id));
		}
		else
		{
			App::abort(404, 'Page not found');
		}
	}

}